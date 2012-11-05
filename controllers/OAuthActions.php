<?php

class OAuthActions extends ActionController {

    public function doTwitterAuthenticate() {
        $this->doAuthenticate('facebook');
    }


    public function doAuthenticate($provider = '') {
        $workflow    = [];
        $webResponse = false;
        $provider = $provider ?: @$_GET['provider'];
        if (@$_GET['device'] && @$_GET['device_callback']) {
            $workflow    = ['callback' => [
                'oauth_device'          => strtolower(trim($_GET['device'])),
                'oauth_device_callback' => trim($_GET['device_callback']),
            ]];
            $webResponse = true;
        } else {
            if (isset($_POST['refere'])) {
                $workflow['callback']['url']  = trim($_POST['refere']);
            }
            if (isset($_POST['event'])) {
                $workflow['callback']['args'] = trim($_POST['event']);
            }
        }
        $modOauth = $this->getModelByName('OAuth');
        switch ($provider) {
            case 'twitter':
                $urlOauth = $modOauth->getTwitterRequestToken($workflow);
                break;
            case 'facebook':
                $urlOauth = $modOauth->facebookRedirect($workflow);
                break;
            default:
                apiError(400, 'no_provider', '');
        }
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
            500, "could_not_connect_to_{$provider}",
            "Could not connect to {$provider}. Refresh the page or try again later."
        );
    }


    public function doTwitterCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo
         || $oauthIfo['external_service'] !== 'twitter'
         || (isset($oauthIfo['oauth_token'])
          && $oauthIfo['oauth_token'] !== $_REQUEST['oauth_token'])) {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                header("location: /");
            }
            return;
        }
        if ($modOauth->getTwitterAccessToken($_REQUEST['oauth_verifier'])) {
            $oauthIfo = $modOauth->getSession();
            $rawIdentity = $modOauth->verifyTwitterCredentials(
                $oauthIfo['oauth_token'],
                $oauthIfo['oauth_token_secret']
            );
            if ($rawIdentity) {
                $result = $modOauth->handleCallback($rawIdentity, $oauthIfo);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'twitter']);
                        header("location: /");
                    }
                    return;
                }
                if ($isMobile) {
                    header(
                        "location: {$workflow['callback']['oauth_device_callback']}"
                      . "?token={$result['oauth_signin']['token']}"
                      . "&name={$result['identity']->name}"
                      . "&userid={$result['oauth_signin']['user_id']}"
                      . "&external_id={$result['identity']->external_id}"
                      . "&provider={$result['identity']->provider}"
                      . "&identity_status={$result['identity_status']}"
                      . "&twitter_following={$result['twitter_following']}"
                      . (isset($workflow['verification_token'])
                      ? "&verification_token={$workflow['verification_token']}"
                      : '')
                    );
                    return;
                }
                $modOauth->addtoSession([
                    'oauth_signin'       => $result['oauth_signin'],
                    'identity'           => (array) $result['identity'],
                    'provider'           => $result['identity']->provider,
                    'identity_status'    => $result['identity_status'],
                    'twitter_following'  => $result['twitter_following'],
                ]);
                header("location: /");
                return;
            }
        }
        $modOauth->resetSession();
        header('location: ' . (
            $isMobile
          ? "{$workflow['callback']['oauth_device_callback']}?err=OAutherror"
          : '/'
        ));
    }


    public function doFacebookCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo || $oauthIfo['external_service'] !== 'facebook') {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'facebook']);
                header("location: /");
            }
            return;
        }
        $oauthToken = $modOauth->getFacebookOAuthToken(
            $modOauth->getFacebookOAuthCode()
        );
        if ($oauthToken) {
            $rawIdentity = $modOauth->getFacebookProfile($oauthToken['oauth_token']);
            if ($rawIdentity) {
                $result = $modOauth->handleCallback($rawIdentity, $oauthIfo, $oauthToken);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'facebook']);
                        header("location: /");
                    }
                    return;
                }
                if ($isMobile) {
                    header(
                        "location: {$workflow['callback']['oauth_device_callback']}"
                      . "?token={$result['oauth_signin']['token']}"
                      . "&name={$result['identity']->name}"
                      . "&userid={$result['oauth_signin']['user_id']}"
                      . "&external_id={$result['identity']->external_id}"
                      . "&provider={$result['identity']->provider}"
                      . "&identity_status={$result['identity_status']}"
                      . "&twitter_following={$result['twitter_following']}"
                      . (isset($workflow['verification_token'])
                      ? "&verification_token={$workflow['verification_token']}"
                      : '')
                    );
                    return;
                }
                $modOauth->addtoSession([
                    'oauth_signin'       => $result['oauth_signin'],
                    'identity'           => (array) $result['identity'],
                    'provider'           => $result['identity']->provider,
                    'identity_status'    => $result['identity_status'],
                ]);
                header("location: /");
                return;
            }
        }
        $modOauth->resetSession();
        header('location: ' . (
            $isMobile
          ? "{$workflow['callback']['oauth_device_callback']}?err=OAutherror"
          : '/'
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
