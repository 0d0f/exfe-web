<?php
require_once dirname(dirname(__FILE__))."/lib/OAuth.php";
require_once dirname(dirname(__FILE__))."/lib/TwitterOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/FacebookOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/FoursquareAPI.class.php";

class OAuthActions extends ActionController {

    public function doIndex() {
        header("location:/s/login");
    }

    public function doTwitterRedirect(){
        $_SESSION['oauth_device']=$_GET["device"];
        $_SESSION['oauth_device_callback']=$_GET["device_callback"];
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
        if (empty($_SESSION['access_token'])
            || empty($_SESSION['access_token']['oauth_token'])
            || empty($_SESSION['access_token']['oauth_token_secret'])
        ) {
            header('Location: /oAuth/clearTwitterSessions');
        }

        $accessToken = $_SESSION['access_token'];
        $accessTokenStr = packArray($accessToken);

        $twitterConn = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);


        $twitterUserInfo = $twitterConn->get('account/verify_credentials');

        if(gettype($twitterUserInfo) == "object"){
            $twitterUserInfo = (array)$twitterUserInfo;
        }
        $oAuthUserInfo = array(
            "provider"      =>"twitter",
            "id"            =>$twitterUserInfo["id"],
            "name"          =>$twitterUserInfo["name"],
            "sname"         =>$twitterUserInfo["screen_name"],
            "desc"          =>$twitterUserInfo["description"],
            "avatar"        =>str_replace('_normal', '_reasonably_small', $twitterUserInfo["profile_image_url"]),
            "oauth_token"   =>$accessTokenStr
        );
        $external_identity=$oAuthUserInfo["provider"]."_".$oAuthUserInfo["id"];

        $OAuthModel = $this->getModelByName("oAuth");
        $result = $OAuthModel->verifyOAuthUser($oAuthUserInfo);
        $identityID = $result["identityID"];
        $userID = $result["userID"];
        if(!$identityID || !$userID){
            die("OAuth error.");
        }

        //扔一个任务到队列里，去取用户的好友列表。
        $args = array(
            "screen_name"   =>$twitterUserInfo["screen_name"],
            "user_id"       =>$userID,
            "user_token"    =>$accessToken['oauth_token'],
            "user_secret"   =>$accessToken['oauth_token_secret']
        );
        $OAuthHelperHandler = $this->getHelperByName("oAuth");
        $jobToken = $OAuthHelperHandler->twitterGetFriendsList($args);

        $userData = $this->getModelByName("User","v2");

        if($_SESSION['oauth_device']=='iOS')
        {

            $signinResult=$userData->signinForAuthTokenByOAuth("twitter",$result["identityID"],$result["userID"]);
            if($signinResult["token"]!="" && intval($signinResult["user_id"]) == intval($result["userID"]))
            {
                
                header("location:".$_SESSION['oauth_device_callback']."?token=".$signinResult["token"]."&name=".$oAuthUserInfo["name"]."&userid=".$signinResult["user_id"]."&external_id=".$external_identity);
                exit(0);
            }
            header("location:".$_SESSION['oauth_device_callback']."?err=OAuth error.");
            exit(0);
        }
        

        $identityModels = $this->getModelByName("identity");
        //===========

        //先初始化一个对象。user_token和user_secret可以在数据库中找到，
        //即identities表中的oauth_token字段的内容，一个加密串，拿出来之后用unpackArray解包可得到一个数组。
        $twitterConn = new tmhOAuth(array(
          'consumer_key'    => TWITTER_CONSUMER_KEY,
          'consumer_secret' => TWITTER_CONSUMER_SECRET,
          'user_token'      => $accessToken['oauth_token'],
          'user_secret'     => $accessToken['oauth_token_secret']
        ));

        //通过friendships/exists去判断当前用户screen_name_a是否Follow screen_name_b。
        //如果已经Follow，会返回true，否则False。(String)
        $responseCode = $twitterConn->request('GET', $twitterConn->url('1/friendships/exists'), array(
            'screen_name_a'=>$twitterUserInfo["screen_name"], 'screen_name_b'=>TWITTER_OFFICE_ACCOUNT
        ));

        $identityModels->loginByIdentityId($identityID, $userID);
        if ($responseCode == 200) {
            if($twitterConn->response['response'] == 'false'){
                unset($oAuthUserInfo["oauth_token"]);
                $token = packArray($oAuthUserInfo);
                header("location:/oAuth/confirmTwitterFollowing?token=".$token);
                exit();
            }
        }
        header("location:/s/profile");
    }

    public function doConfirmTwitterFollowing(){
        $userToken = exGet("token");
        $confirm = trim(exGet("confirm"));
        if($confirm == ""){
            if($userToken == ""){
                header("location:/s/profile");
                exit;
            }
            $userInfo = unpackArray($userToken);
            if(!is_array($userInfo)){
                header("location:/s/profile");
                exit;
            }

            $this->setVar("user_name", $userInfo["name"]);
            $this->setVar("user_avatar", $userInfo["avatar"]);
            $this->setVar("exfe_office_account", TWITTER_OFFICE_ACCOUNT);
            $this->displayView();
        }else{
            if($confirm == "yes"){
                $accessToken = $_SESSION['access_token'];
                $twitterConn = new tmhOAuth(array(
                  'consumer_key'    => TWITTER_CONSUMER_KEY,
                  'consumer_secret' => TWITTER_CONSUMER_SECRET,
                  'user_token'      => $accessToken['oauth_token'],
                  'user_secret'     => $accessToken['oauth_token_secret']
                ));
                $responseCode = $twitterConn->request('POST', $twitterConn->url('1/friendships/create'), array(
                    'screen_name'=>TWITTER_OFFICE_ACCOUNT
                ));
            }
            header("location:/s/profile");
        }
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
}
