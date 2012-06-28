<?php

class OAuthActions extends ActionController {

    public function doTwitterAuthenticate() {
        $workflow    = [];
        $webResponse = false;
        if ($_GET['device'] && $_GET['device_callback']) {
            $workflow    = ['callback' => [
                'oauth_device'          => $_GET['device'],
                'oauth_device_callback' => $_GET['device_callback'],
            ]];
            $webResponse = true;
        }
        $modOauth = $this->getModelByName('OAuth', 'v2');
        $urlOauth = $modOauth->getTwitterRequestToken($workflow);
        if ($urlOauth) {
            if ($webResponse) {
                header("Location: {$urlOauth}");
                return;
            }
            apiResponse(array('redirect' => $urlOauth));
        }
        if ($webResponse) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Could not connect to Twitter. Refresh the page or try again later.';
            return;
        }
        apiError(
            500, 'could_not_connect_to_twitter',
            'Could not connect to Twitter. Refresh the page or try again later.'
        );
    }


    public function doTwitterRedirect() {
        $this->doTwitterAuthenticate();
    }


    public function doTwitterCallBack() {
        $modOauth = $this->getModelByName('OAuth', 'v2');
        $oauthIfo = $modOauth->getSession();
        if (!$oauthIfo || (isset($oauthIfo['oauth_token'])
         && $oauthIfo['oauth_token'] !== $_REQUEST['oauth_token'])) {
            $modOauth->resetSession();
            echo 'Error Session!';
            return;
        }
        $rstAcsToken = $modOauth->getTwitterAccessToken(
            $_REQUEST['oauth_verifier']
        );
        if ($rstAcsToken) {
            $oauthIfo = $modOauth->getSession();
            $objTwitterIdentity = $modOauth->verifyTwitterCredentials(
                $oauthIfo['oauth_token'],
                $oauthIfo['oauth_token_secret']
            );
            if ($objTwitterIdentity) {
                if (!$oauthIfo['workflow'] || $oauthIfo['workflow']['callback']) {
                    $modUser     = $this->getModelByName('User', 'v2');
                    $modIdentity = $this->getModelByName('Identity', 'v2');
                    $objIdentity = $modIdentity->getIdentityByProviderAndExternalUsername(
                        'twitter', $objTwitterIdentity->external_username, true
                    );
                    // 身份不存在，创建新身份并连接新用户
                    if (!$objIdentity) {
                        $user_id = $modUser->addUser();
                        if (!$user_id) {
                            echo 'Can not signin with this Twitter identity, please retry later!';
                            return;
                        }
                        $identity_id = $modIdentity->addIdentity(
                            'twitter',
                            $objTwitterIdentity->external_id,
                            ['name'              => $objTwitterIdentity->name,
                             'bio'               => $objTwitterIdentity->bio,
                             'external_username' => $objTwitterIdentity->external_username,
                             'avatar_filename'   => $objTwitterIdentity->avatar_filename],
                            $user_id,
                            3
                        );
                        if (!$identity_id) {
                            echo 'Can not signin with this Twitter identity, please retry later!';
                            return;
                        }
                        $objIdentity = $modIdentity->getIdentityById($identity_id, null, true);
                    }
                    if (!$objIdentity) {
                        echo 'Can not signin with this Twitter identity, please retry later!';
                        return;
                    }
                    // 身份未连接
                    if (!$objIdentity->connected_user_id) {
                        // 身份被 revoked，重新连接用户
                        if ($objIdentity->revoked_user_id) {
                            $user_id = $objIdentity->revoked_user_id;
                        // 孤立身份，创建新用户并连接到该身份
                        } else {
                            $user_id = $modUser->addUser();
                        }
                        if (!$user_id) {
                            echo 'Can not signin with this Twitter identity, please retry later!';
                            return;
                        }
                        $rstChangeStatus = $modUser->setUserIdentityStatus(
                            $user_id, $objIdentity->id, 3
                        );
                        $objIdentity->connected_user_id = $user_id;
                        if (!$rstChangeStatus) {
                            echo 'Can not signin with this Twitter identity, please retry later!';
                            return;
                        }
                    }
                    // 更新 OAuth Token
                    $modIdentity->updateOAuthTokenById($objIdentity->id, [
                        'oauth_token'        => $oauthIfo['oauth_token'],
                        'oauth_token_secret' => $oauthIfo['oauth_token_secret'],
                    ]);
                    // 使用该身份登录
                    $rstSignin = $modUser->rawSiginin(
                        $objIdentity->connected_user_id
                    );
                    // 抓取好友
                    // $args = array(
                    //     "screen_name"   =>$twitterUserInfo["screen_name"],
                    //     "user_id"       =>$userID,
                    //     "user_token"    =>$accessToken['oauth_token'],
                    //     "user_secret"   =>$accessToken['oauth_token_secret']
                    // );
                    // $jobToken = $OAuthHelperHandler->twitterGetFriendsList($args);
                    if ($oauthIfo['workflow']['callback']['oauth_device'] === 'iOS') {
                        header(
                            "location: {$oauthIfo['workflow']['callback']['oauth_device_callback']}"
                          . "?token={$rstSignin['token']}&name={$objTwitterIdentity->name}"
                          . "&userid={$rstSignin['user_id']}&external_id="
                          . "{$objTwitterIdentity->external_id}&provider=twitter"
                        );
                        return;
                    }
                    // 通过 friendships/exists 去判断当前用户 screen_name_a 是否 Follow screen_name_b
                    // true / false [String]
                    $twitterConn = new tmhOAuth([
                      'consumer_key'    => TWITTER_CONSUMER_KEY,
                      'consumer_secret' => TWITTER_CONSUMER_SECRET,
                      'user_token'      => $oauthIfo['oauth_token'],
                      'user_secret'     => $oauthIfo['oauth_token_secret'],
                    ]);
                    $twitterConn->request(
                        'GET',
                        $twitterConn->url('1/friendships/exists'),
                        ['screen_name_a' => $objIdentity->external_username,
                         'screen_name_b' => TWITTER_OFFICE_ACCOUNT]
                    );
                    if ($twitterConn->response['response'] === 'false') {
                        header('location: /oAuth/confirmTwitterFollowing');
                        return;
                    }
                    $modOauth->addtoSession(['signin' => $rstSignin]);
                    header('location: /s/profile');
                    return;
                }
                echo 'Request error!';
                return;
            }
        }
        $modOauth->resetSession();
        header('location:' .(
            $oauthIfo['workflow']['callback']['oauth_device'] === 'iOS'
         ? "{$oauthIfo['workflow']['callback']['oauth_device_callback']}?err=OAuth error."
         : '/s/profile'
        ));
    }


    public function doConfirmTwitterFollowing() {
        $modOauth = $this->getModelByName('OAuth', 'v2');
        $oauthIfo = $modOauth->getSession();
        if ($oauthIfo['signin']) {
            $confirm = trim(exGet('confirm'));
            if ($confirm === 'yes') {
                $twitterConn = new tmhOAuth(array(
                  'consumer_key'    => TWITTER_CONSUMER_KEY,
                  'consumer_secret' => TWITTER_CONSUMER_SECRET,
                  'user_token'      => $accessToken['oauth_token'],
                  'user_secret'     => $accessToken['oauth_token_secret']
                ));
                $twitterConn->request(
                    'POST',
                    $twitterConn->url('1/friendships/create'),
                    array('screen_name' => TWITTER_OFFICE_ACCOUNT)
                );
            } else {
                $modIdentity = $this->getModelByName('Identity', 'v2');
                $objIdentity = $modIdentity->getIdentityByProviderAndExternalUsername(
                    'twitter', $objTwitterIdentity->external_username, true
                );
                $this->setVar('user_name',           $modIdentity->name);
                $this->setVar('user_avatar',         $modIdentity->avatar_filename);
                $this->setVar('exfe_office_account', TWITTER_OFFICE_ACCOUNT);
                $this->displayView();
                return;
            }
        }
        header('location: /s/profile');
    }










    /* Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     *
    public function doLoginWithFacebook(){
        $facebookHandler = new FacebookOauth(array(
                'appId'  =>FACEBOOK_APP_ID,
                'secret' =>FACEBOOK_SECRET_KEY,
                'cookie' =>true
        ));
        $facebookSession = $facebookHandler->getSession();

        $facebookUserInfo = null;
        if ($facebookSession) {
            try {
                $uid = $facebookHandler->getUser();
                $facebookUserInfo = $facebookHandler->api('/me');
            } catch (FacebookApiException $e) {
                error_log($e);
            }
        }
        if (!$facebookUserInfo) {
            $params = array();
            $loginUrl = $facebookHandler->getLoginUrl($params);
            header("location:".$loginUrl);
        } else {

            if(gettype($facebookUserInfo) == "object"){
                $facebookUserInfo = (array)$facebookUserInfo;
            }
            $oAuthUserInfo = array(
                "provider"      =>"facebook",
                "id"            =>$facebookUserInfo["id"],
                "name"          =>$facebookUserInfo["name"],
                "sname"         =>$facebookUserInfo["username"],
                "desc"          =>array_key_exists("bio", $facebookUserInfo) ? $facebookUserInfo["bio"] : "",
                "avatar"        =>"https://graph.facebook.com/".$facebookUserInfo["id"]."/picture?type=large",
                "oauth_token"   =>""
            );

            $OAuthModel = $this->getModelByName("oAuth");
            $result = $OAuthModel->verifyOAuthUser($oAuthUserInfo);
            $identityID = $result["identityID"];
            $userID = $result["userID"];
            if(!$identityID || !$userID){
                die("OAuth error.");
            }

            $identityModels = $this->getModelByName("identity");
            $identityModels->loginByIdentityId($identityID, $userID);

            header("location:/s/login");
        }
    }


    public function doLoginWithGoogle(){
        $scopeArray = array(
            'https://www.google.com/m8/feeds/',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email'
        );
        $scopeString = implode(' ', $scopeArray);
        $googleAPIConf = array('client_id'      => GOOGLE_CLIENT_ID,
                               'redirect_uri'   => GOOGLE_REDIRECT_URIS,
                               'scope'          => $scopeString,
                               'response_type'  => 'code'
        );

        $googleOAuthURL = 'https://accounts.google.com/o/oauth2';
        $googleOAuthURL .= '/auth?'.http_build_query($googleAPIConf);

        header("location:".$googleOAuthURL);
    }


    public function doGoogleOAuthCallback(){
        $googleAPIConf = array('code'           =>$_REQUEST['code'],
                               'client_id'      =>GOOGLE_CLIENT_ID,
                               'client_secret'  =>GOOGLE_CLIENT_SECRET,
                               'redirect_uri'   =>GOOGLE_REDIRECT_URIS,
                               'grant_type'     =>'authorization_code'
        );

        $curlHandler = curl_init('https://accounts.google.com/o/oauth2/token');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandler, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curlHandler, CURLOPT_POST, TRUE);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $googleAPIConf);
        $tokenData = curl_exec($curlHandler);
        curl_close($curlHandler);

        $googleToken = (array)json_decode($tokenData);
        $googleUserDataURL = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=";
        $googleUserDataURL .= $googleToken["access_token"];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $googleUserDataURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $googleUserData = curl_exec($ch);
        curl_close($ch);

        $googleUserInfo = (array)json_decode($googleUserData);

        if(is_array($googleUserInfo) && array_key_exists("id", $googleUserInfo)){
            $googleUserAvatar = array_key_exists("picture", $googleUserInfo) ? $googleUserInfo["picture"]."?sz=240" : "";
            $googleUserDesc = array_key_exists("description", $googleUserInfo) ? $googleUserInfo["description"] : "";

            $oAuthUserInfo = array(
                "provider"      =>"google",
                "id"            =>$googleUserInfo["id"],
                "name"          =>$googleUserInfo["name"],
                "sname"         =>$googleUserInfo["email"],
                "desc"          =>$googleUserDesc,
                "avatar"        =>$googleUserAvatar,
                "oauth_token"   =>""
            );

            $OAuthModel = $this->getModelByName("oAuth");
            $result = $OAuthModel->verifyOAuthUser($oAuthUserInfo);
            $identityID = $result["identityID"];
            $userID = $result["userID"];
            if(!$identityID || !$userID){
                die("OAuth error.");
            }

            $identityModels = $this->getModelByName("identity");
            $identityModels->loginByIdentityId($identityID, $userID);

            header("location:/s/login");
        }else{
            header("location:/s/login");
        }
    }
    */

}
