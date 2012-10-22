<?php

class OAuthActions extends ActionController {

    // twitter {

    public function doTwitterAuthenticate() {
        $workflow    = [];
        $webResponse = false;
        if ($_GET['device'] && $_GET['device_callback']) {
            $workflow    = ['callback' => [
                'oauth_device'          => strtolower(trim($_GET['device'])),
                'oauth_device_callback' => trim($_GET['device_callback']),
            ]];
            $webResponse = true;
        } else {
            if ($_POST['callback']) {
                $workflow['callback']['url']  = trim($_POST['callback']);
            }
            if ($_POST['args']) {
                $workflow['callback']['args'] = trim($_POST['args']);
            }
        }
        $modOauth = $this->getModelByName('OAuth');
        $urlOauth = $modOauth->getTwitterRequestToken($workflow);
        if ($urlOauth) {
            if ($webResponse) {
                header("Location: {$urlOauth}");
                return;
            }
            apiResponse(['redirect' => $urlOauth]);
        }
        $modOauth->resetSession();
        if ($webResponse) {
            header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            return;
        }
        apiError(
            500, 'could_not_connect_to_twitter',
            'Could not connect to Twitter. Refresh the page or try again later.'
        );
    }


    public function doTwitterCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        $cbckUrl  = isset($workflow['callback']['url'])
                 && $workflow['callback']['url']
                  ? $workflow['callback']['url'] : '/';
        if (!$oauthIfo || (isset($oauthIfo['oauth_token'])
         && $oauthIfo['oauth_token'] !== $_REQUEST['oauth_token'])) {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                header("location: {$cbckUrl}");
            }
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
                if (@!$oauthIfo['workflow']
                 || @$oauthIfo['workflow']['callback']
                 || @$oauthIfo['workflow']['user_id']) {
                    $modUser     = $this->getModelByName('User');
                    $modIdentity = $this->getModelByName('Identity');
                    $objIdentity = $modIdentity->getIdentityByProviderAndExternalUsername(
                        'twitter', $objTwitterIdentity->external_username, true
                    );
                    $identity_status = 'connected';
                    // 身份不存在，创建新身份并连接新用户
                    if (!$objIdentity) {
                        $identity_status = 'new';
                        $user_id = $modUser->addUser(
                            '',
                            $objTwitterIdentity->name
                         ?: $objTwitterIdentity->external_username
                        );
                        if (!$user_id) {
                            if ($isMobile) {
                                $modOauth->resetSession();
                                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                            } else {
                                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                                header("location: {$cbckUrl}");
                            }
                            return;
                        }
                        $identity_id = $modIdentity->addIdentity(
                            ['provider'          => 'twitter',
                             'external_id'       => $objTwitterIdentity->external_id,
                             'name'              => $objTwitterIdentity->name,
                             'bio'               => $objTwitterIdentity->bio,
                             'external_username' => $objTwitterIdentity->external_username,
                             'avatar_filename'   => $objTwitterIdentity->avatar_filename],
                            $user_id, 3
                        );
                        if (!$identity_id) {
                            if ($isMobile) {
                                $modOauth->resetSession();
                                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                            } else {
                                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                                header("location: {$cbckUrl}");
                            }
                            return;
                        }
                        $objIdentity = $modIdentity->getIdentityById($identity_id, null, true);
                    }
                    if (!$objIdentity) {
                        if ($isMobile) {
                            $modOauth->resetSession();
                            header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                        } else {
                            $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                            header("location: {$cbckUrl}");
                        }
                        return;
                    }
                    // 身份未连接
                    if ($objIdentity->connected_user_id <= 0) {
                        $user_id     = 0;
                        // verify
                        if (@$oauthIfo['workflow']['user_id']) {
                            $user_id = $oauthIfo['workflow']['user_id'];
                        }
                        // 身份被 revoked，重新连接用户
                        if ($objIdentity->revoked_user_id) {
                            $identity_status = 'revoked';
                            $user_id = $user_id ?: $objIdentity->revoked_user_id;
                        // 孤立身份，创建新用户并连接到该身份
                        } else if ($user_id) {
                            $identity_status = 'verifying';
                        } else {
                            $identity_status = 'new';
                            $user_id = $modUser->addUser(
                                '',
                                $objTwitterIdentity->name
                             ?: $objTwitterIdentity->external_username
                            );
                        }
                        if (!$user_id) {
                            if ($isMobile) {
                                $modOauth->resetSession();
                                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                            } else {
                                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                                header("location: {$cbckUrl}");
                            }
                            return;
                        }
                        $rstChangeStatus = $modUser->setUserIdentityStatus(
                            $user_id, $objIdentity->id, 3
                        );
                        $objIdentity->connected_user_id = $user_id;
                        if (!$rstChangeStatus) {
                            if ($isMobile) {
                                $modOauth->resetSession();
                                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                            } else {
                                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                                header("location: {$cbckUrl}");
                            }
                            return;
                        }
                    }
                    // 更新 OAuth Token
                    $modIdentity->updateOAuthTokenById($objIdentity->id, [
                        'oauth_token'        => $oauthIfo['oauth_token'],
                        'oauth_token_secret' => $oauthIfo['oauth_token_secret'],
                    ]);
                    // 使用该身份登录
                    $rstSignin = $modUser->rawSignin(
                        $objIdentity->connected_user_id
                    );
                    // call Gobus {
                    $hlpGobus = $this->getHelperByName('gobus');
                    $hlpGobus->send('user', 'GetFriends', [
                        'user_id'       => $objIdentity->connected_user_id,
                        'provider'      => 'twitter',
                        'external_id'   => $objIdentity->external_id,
                        'client_token'  => TWITTER_CONSUMER_KEY,
                        'client_secret' => TWITTER_CONSUMER_SECRET,
                        'access_token'  => $oauthIfo['oauth_token'],
                        'access_secret' => $oauthIfo['oauth_token_secret'],
                    ]);
                    // }
                    if ($isMobile) {
                        header(
                            "location: {$workflow['callback']['oauth_device_callback']}"
                          . "?token={$rstSignin['token']}"
                          . "&name={$objTwitterIdentity->name}"
                          . "&userid={$rstSignin['user_id']}"
                          . "&external_id={$objTwitterIdentity->external_id}"
                          . '&provider=twitter'
                          . "&identity_status={$identity_status}"
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
                    $modOauth->addtoSession([
                        'oauth_signin'      => $rstSignin,
                        'identity'          => $objIdentity,
                        'identity_status'   => $identity_status,
                        'twitter_following' => $twitterConn->response['response'] === 'true'
                    ]);
                    header("location: {$cbckUrl}");
                    return;
                }
            }
        }
        $modOauth->resetSession();
        header('location: ' . (
            $isMobile
          ? "{$workflow['callback']['oauth_device_callback']}?err=OAutherror"
          : $cbckUrl
        ));
    }


    public function doFollowExfe() {
        // load models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        $modOauth    = $this->getModelByName('OAuth');
        $checkHelper = $this->getHelperByName('check');
        // get args
        $identity_id = trim($_POST['identity_id']);
        // basic check
        $result      = $checkHelper->isAPIAllow('user_edit', $_GET['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // check user identity status
        switch ($modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) {
            case 'CONNECTED':
                $accessToken = $modIdentity->getOAuthTokenById($identity_id);
                $twitterConn = new tmhOAuth([
                    'consumer_key'    => TWITTER_CONSUMER_KEY,
                    'consumer_secret' => TWITTER_CONSUMER_SECRET,
                    'user_token'      => $accessToken['oauth_token'],
                    'user_secret'     => $accessToken['oauth_token_secret']
                ]);
                $twitterConn->request(
                    'POST',
                    $twitterConn->url('1/friendships/create'),
                    ['screen_name' => TWITTER_OFFICE_ACCOUNT]
                );
                apiResponse(new stdClass);
                break;
            default:
                apiError(400, 'invalid_relation', ''); // 用户和身份关系错误
        }
        apiError(500, 'failed', '');
    }

    // }


    // facebook {

    public function doFacebookAuthenticate() {
        $workflow    = [];
        $webResponse = false;
        if ($_GET['device'] && $_GET['device_callback']) {
            $workflow    = ['callback' => [
                'oauth_device'          => $_GET['device'],
                'oauth_device_callback' => $_GET['device_callback'],
            ]];
            $webResponse = true;
        }
        $modOauth = $this->getModelByName('OAuth');
        $urlOauth = $modOauth->facebookRedirect($workflow);
        if ($urlOauth) {
            if ($webResponse) {
                header("Location: {$urlOauth}");
                return;
            }
            apiResponse(array('redirect' => $urlOauth));
        }
        if ($webResponse) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Could not connect to Facebook. Refresh the page or try again later.';
            return;
        }
        apiError(
            500, 'could_not_connect_to_facebook',
            'Could not connect to Facebook. Refresh the page or try again later.'
        );
    }


    public function doFacebookCallBack() {
        $modOauth   = $this->getModelByName('OAuth');
        $oauthToken = $modOauth->getFacebookOAuthToken(
            $modOauth->getFacebookOAuthCode()
        );
        if ($oauthToken) {
            $rawIdentity = $modOauth->getFacebookProfile($oauthToken['oauth_token']);
            if ($rawIdentity) {
                $modUser     = $this->getModelByName('User');
                $modIdentity = $this->getModelByName('Identity');
                $objIdentity = $modIdentity->getIdentityByProviderAndExternalUsername(
                    'facebook', $rawIdentity->external_username, true
                );
                // 身份不存在，创建新身份并连接新用户
                if (!$objIdentity) {
                    $user_id = $modUser->addUser(
                        '', $rawIdentity->name ?: $rawIdentity->external_username
                    );
                    if (!$user_id) {
                        echo 'Can not signin with this Facebook identity, please retry later!';
                        return;
                    }
                    $identity_id = $modIdentity->addIdentity(
                        ['provider'          => 'facebook',
                         'external_id'       => $rawIdentity->external_id,
                         'name'              => $rawIdentity->name,
                         'bio'               => $rawIdentity->bio,
                         'external_username' => $rawIdentity->external_username,
                         'avatar_filename'   => $rawIdentity->avatar_filename],
                        $user_id,
                        3
                    );
                    if (!$identity_id) {
                        echo 'Can not signin with this Facebook identity, please retry later!';
                        return;
                    }
                    $objIdentity = $modIdentity->getIdentityById($identity_id, null, true);
                }
                if (!$objIdentity) {
                    echo 'Can not signin with this Facebook identity, please retry later!';
                    return;
                }
                // 身份未连接
                if ($objIdentity->connected_user_id <= 0) {
                    // 身份被 revoked，重新连接用户
                    if ($objIdentity->revoked_user_id) {
                        $user_id = $objIdentity->revoked_user_id;
                    // 孤立身份，创建新用户并连接到该身份
                    } else {
                        $user_id = $modUser->addUser(
                            '',
                            $rawIdentity->name
                         ?: $rawIdentity->external_username
                        );
                    }
                    if (!$user_id) {
                        echo 'Can not signin with this Facebook identity, please retry later!';
                        return;
                    }
                    $rstChangeStatus = $modUser->setUserIdentityStatus(
                        $user_id, $objIdentity->id, 3
                    );
                    $objIdentity->connected_user_id = $user_id;
                    if (!$rstChangeStatus) {
                        echo 'Can not signin with this Facebook identity, please retry later!';
                        return;
                    }
                }
                // 更新 OAuth Token
                $modIdentity->updateOAuthTokenById($objIdentity->id, [
                    'oauth_token'   => $oauthToken['oauth_token'],
                    'oauth_expires' => $oauthToken['oauth_expires'],
                ]);
                // 使用该身份登录
                $rstSignin = $modUser->rawSignin(
                    $objIdentity->connected_user_id
                );
                // @todo by @Leask
                // call Gobus {
                // $hlpGobus = $this->getHelperByName('gobus');
                // $hlpGobus->send('user', 'TwitterFriends', [
                //     'ClientToken'  => TWITTER_CONSUMER_KEY,
                //     'ClientSecret' => TWITTER_CONSUMER_SECRET,
                //     'AccessToken'  => $oauthIfo['oauth_token'],
                //     'AccessSecret' => $oauthIfo['oauth_token_secret'],
                // ]);
                // }
                // @todo by @Leask
                // if ($oauthIfo['workflow']['callback']['oauth_device'] === 'iOS') {
                //     header(
                //         "location: {$oauthIfo['workflow']['callback']['oauth_device_callback']}"
                //       . "?token={$rstSignin['token']}&name={$rawIdentity->name}"
                //       . "&userid={$rstSignin['user_id']}&external_id="
                //       . "{$rawIdentity->external_id}&provider=twitter"
                //     );
                //     return;
                // }
                $modOauth->addtoSession(['facebook_signin' => $rstSignin]);
                header('location: /');
                return;
            }
        }
        apiError(400, 'invalid_callback', '');
    }

    // }





    /* Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * Working on!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     *
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
