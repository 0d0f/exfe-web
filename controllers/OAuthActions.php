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
                $url = $twitterConn->getAuthorizeURL($token);
                header('Location:'.$url); 
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
    public Function doLoginWithTwitter(){
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
}
