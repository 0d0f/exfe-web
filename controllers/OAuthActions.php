<?php
require_once dirname(dirname(__FILE__))."/lib/OAuth.php";
require_once dirname(dirname(__FILE__))."/lib/TwitterOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/FacebookOAuth.php";
class OAuthActions extends ActionController {

    public function doIndex() {
        header("location:/s/login");
    }

    public function doTwitterRedirect(){
        $twitterConn = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
        $requestToken = $twitterConn->getRequestToken(TWITTER_OAUTH_CALLBACK);
        $_SESSION['oauth_token'] = $requestToken['oauth_token'];
        $_SESSION['oauth_token_secret'] = $requestToken['oauth_token_secret'];

        switch ($twitterConn->http_code) {
            case 200:
                $token = $requestToken['oauth_token'];
                $twitterOAuthURL = $twitterConn->getAuthorizeURL($token);
                header('Location:'.$twitterOAuthURL); 
                break;
            default:
                echo 'Could not connect to Twitter. Refresh the page or try again later.';
        }
    }

    public function doTwitterCallBack(){
        if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
            $_SESSION['oauth_status'] = 'oldtoken';
            header('Location:/oAuth/clearTwitterSessions');
        }

        $twitterConn = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

        $accessToken = $twitterConn->getAccessToken($_REQUEST['oauth_verifier']);
        $_SESSION['access_token'] = $accessToken;

        unset($_SESSION['oauth_token']);
        unset($_SESSION['oauth_token_secret']);

        if (200 == $twitterConn->http_code) {
            $_SESSION['status'] = 'verified';
            header('Location:/oAuth/loginWithTwitter');
        } else {
            header('Location: /oAuth/clearTwitterSessions');
        }
    }
    public function doLoginWithTwitter() {
        if (empty($_SESSION['access_token']) ||
            empty($_SESSION['access_token']['oauth_token']) ||
            empty($_SESSION['access_token']['oauth_token_secret'])
        ) {
            header('Location: /oAuth/clearTwitterSessions');
        }

        $accessToken = $_SESSION['access_token'];
        $twitterConn = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);

        $twitterUserInfo = $twitterConn->get('account/verify_credentials');

        if(gettype($twitterUserInfo) == "object"){
            $twitterUserInfo = (array)$twitterUserInfo;
        }
        $oAuthUserInfo = array(
            "provider"  =>"twitter",
            "id"        =>$twitterUserInfo["id"],
            "name"      =>$twitterUserInfo["name"],
            "sname"     =>$twitterUserInfo["screen_name"],
            "desc"      =>$twitterUserInfo["description"],
            "avatar"    =>str_replace('_normal', '_reasonably_small', $twitterUserInfo["profile_image_url"])
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

    public function doClearTwitterSessions(){
        session_start();
        session_destroy();
        header('Location:/');
    }

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
                "provider"  =>"facebook",
                "id"        =>$facebookUserInfo["id"],
                "name"      =>$facebookUserInfo["name"],
                "sname"     =>$facebookUserInfo["username"],
                "desc"      =>array_key_exists("bio", $facebookUserInfo) ? $facebookUserInfo["bio"] : "",
                "avatar"    =>"https://graph.facebook.com/".$facebookUserInfo["id"]."/picture?type=large"
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
                "provider"  =>"google",
                "id"        =>$googleUserInfo["id"],
                "name"      =>$googleUserInfo["name"],
                "sname"     =>$googleUserInfo["email"],
                "desc"      =>$googleUserDesc,
                "avatar"    =>$googleUserAvatar
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
}
