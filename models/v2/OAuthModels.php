<?php

require_once dirname(dirname(__FILE__)) . '/../lib/OAuth.php';
require_once dirname(dirname(__FILE__)) . '/../lib/TwitterOAuth.php';
require_once dirname(dirname(__FILE__)) . '/../lib/facebook.php';
require_once dirname(dirname(__FILE__)) . '/../lib/tmhOAuth.php';
require_once dirname(dirname(__FILE__)) . '/../lib/FoursquareAPI.class.php';


class OAuthModels extends DataModel {

    // twitter {

	public function getTwitterRequestToken($workflow = []) {
		$twitterConn  = new TwitterOAuth(
            TWITTER_CONSUMER_KEY,
            TWITTER_CONSUMER_SECRET
        );
        $requestToken = $twitterConn->getRequestToken(TWITTER_OAUTH_CALLBACK);
        if ($twitterConn->http_code === 200) {
        	$this->setSession(
        		'twitter',
        		$requestToken['oauth_token'],
        		$requestToken['oauth_token_secret'],
                $workflow
        	);
        	return $twitterConn->getAuthorizeURL($requestToken['oauth_token']);
        }
        $this->resetSession();
        return false;
	}


    public function getTwitterAccessToken($verifier) {
        $requestToken = $this->getSession();
        if ($verifier
         && $requestToken
         && $requestToken['external_service'] === 'twitter'
         && $requestToken['oauth_token']
         && $requestToken['oauth_token_secret']) {
            $twitterConn = new TwitterOAuth(
                TWITTER_CONSUMER_KEY,
                TWITTER_CONSUMER_SECRET,
                $requestToken['oauth_token'],
                $requestToken['oauth_token_secret']
            );
            $accessToken = $twitterConn->getAccessToken($verifier);
            if ($twitterConn->http_code === 200) {
                $this->addtoSession([
                    'oauth_token'        => $accessToken['oauth_token'],
                    'oauth_token_secret' => $accessToken['oauth_token_secret'],
                ]);
                return true;
            }
        }
        return false;
    }


    public function verifyTwitterCredentials($oauthToken, $oauthTokenSecret) {
        if ($oauthToken && $oauthTokenSecret) {
            $twitterConn = new TwitterOAuth(
                TWITTER_CONSUMER_KEY,
                TWITTER_CONSUMER_SECRET,
                $oauthToken,
                $oauthTokenSecret
            );
            $rawTwitterUserInfo = $twitterConn->get('account/verify_credentials');
            if ($rawTwitterUserInfo) {
                $hlpIdentity = $this->getHelperByName('Identity', 'v2');
                $rawTwitterUserInfo
              = gettype($rawTwitterUserInfo) === 'object'
              ? (array) $rawTwitterUserInfo : $rawTwitterUserInfo;
                return new Identity(
                    0,
                    $rawTwitterUserInfo['name'],
                    '',
                    $rawTwitterUserInfo['description'],
                    'twitter',
                    0,
                    $rawTwitterUserInfo['id'],
                    $rawTwitterUserInfo['screen_name'],
                    $hlpIdentity->getTwitterLargeAvatarBySmallAvatar(
                        $rawTwitterUserInfo['profile_image_url']
                    )
                );
            }
        }
        return null;
    }


	public function setSession($service, $token, $token_secret, $workflow = []) {
        if ($service && $token && $token_secret) {
    		$_SESSION['oauth'] = [
            	'external_service'   => $service,
            	'oauth_token'        => $token,
            	'oauth_token_secret' => $token_secret,
            	'workflow'           => $workflow,
            ];
            return true;
        }
        $this->resetSession();
        return false;
	}


    public function addtoSession($keyValues) {
        if ($keyValues && is_array($keyValues)) {
            foreach ($keyValues as $key => $value) {
                $_SESSION['oauth'][$key] = $value;
            }
            return true;
        }
        return false;
    }


    public function delfromSession($keys = []) {
        if ($keys && is_array($keys)) {
            foreach ($keys as $key) {
                unset($_SESSION['oauth'][$key]);
            }
            return true;
        }
        return false;
    }


    public function getSession() {
        return $_SESSION['oauth'];
    }


	public function resetSession() {
		unset($_SESSION['oauth']);
	}

    // }


    // facebook {

    protected $trustForwarded = false;


    protected static $DROP_QUERY_PARAMS = array(
        'code',
        'state',
        'signed_request',
    );


    protected function shouldRetainParam($param) {
        foreach (self::$DROP_QUERY_PARAMS as $drop_query_param) {
            if (strpos($param, $drop_query_param.'=') === 0) {
                return false;
            }
        }
        return true;
    }


    protected function getHttpHost() {
        if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            return $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        return $_SERVER['HTTP_HOST'];
    }


    protected function getHttpProtocol() {
        if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return 'https';
            }
            return 'http';
        }
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
            return 'https';
        }
        return 'http';
    }


    protected function getCurrentUrl() {
        $protocol = $this->getHttpProtocol() . '://';
        $host = $this->getHttpHost();
        $currentUrl = $protocol.$host.$_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);
        $query = '';
        if (!empty($parts['query'])) {
            // drop known fb params
            $params = explode('&', $parts['query']);
            $retained_params = array();
            foreach ($params as $param) {
                if ($this->shouldRetainParam($param)) {
                    $retained_params[] = $param;
                }
            }
            if (!empty($retained_params)) {
                $query = '?'.implode($retained_params, '&');
            }
        }
        // use port if non default
        $port =
            isset($parts['port']) &&
            (($protocol === 'http://' && $parts['port'] !== 80) ||
             ($protocol === 'https://' && $parts['port'] !== 443))
            ? ':' . $parts['port'] : '';
        // rebuild
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }


    public function facebookRedirect() {
        return 'https://graph.facebook.com/oauth/authorize?client_id=' . FACEBOOK_APP_ID
      . '&redirect_uri=' . FACEBOOK_OAUTH_CALLBACK . '&type=web_server';
    }


    public function facebookAuthenticate() {
        // init facebook object
        $objFacebook = new Facebook([
            'appId'  => FACEBOOK_APP_ID,
            'secret' => FACEBOOK_SECRET_KEY
        ]);
        // try to get facebook user info
        if (($user_id = $objFacebook->getUser())) {
            try {
                // Proceed knowing you have a logged in user who's authenticated.
                $user_profile = $objFacebook->api('/me');
            } catch (FacebookApiException $error) {
                error_log($error);
                $user_id      = null;
                $user_profile = null;
            }
        }
        // return
        if ($user_profile) {
            $user_profile  = gettype($user_profile) === 'object'
                           ? (array) $user_profile : $user_profile;
            //
            return [
                'action'       => 'callback',
                'raw_identity' => [
                    'provider'          => 'facebook',
                    'external_id'       => $user_profile['id'],
                    'external_username' => $user_profile['username'],
                    'name'              => $user_profile['name'],
                    'avatar_filename'   => "https://graph.facebook.com/{$user_profile['id']}/picture?type=large",
                    'bio'               => array_key_exists('bio', $user_profile)
                                         ? $user_profile['bio'] : '',
                ]
            ];
        }



/////////////////////////



$loginUrl =
print_r($loginUrl);
        //
        if ($facebookUserInfo) {


            print_r($oAuthUserInfo);

            return;
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
        $loginUrl = $objFacebook->getLoginUrl();

        print_r($loginUrl);

        return;

        header("location:".$loginUrl);
    }

    // }




    /********************
     ********************  WORKING ON by Leask Huang
    public function verifyOAuthUser($oAuthUserInfo) {
        //如果当前OAuth用户已经存在。
        $identityID = intval($rows["id"]);
        $sql = "UPDATE identities SET updated_at=FROM_UNIXTIME({$currentTimeStamp}), name='{$oAuthUserName}', bio='{$oAuthUserDesc}', avatar_file_name='{$oAuthUserAvatar}', external_username='{$oAuthScreenName}', oauth_token='{$oAuthAccessToken}' WHERE id={$identityID}";
        $this->query($sql);

        $sql = "SELECT userid FROM user_identity WHERE identityid={$identityID}";
        $result = $this->getRow($sql);

        //如果已经登录，则合并账户。
        $userID = intval($_SESSION['userid']);
        if($userID > 0){
            if((int)$userID != intval($result["userid"])){
                $oldUserID = intval($result["userid"]);
                $sql = "UPDATE user_identity set `status`=1 WHERE `identityid`={$identityID} AND `userid`={$oldUserID}";
                $this->query($sql);
                $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID},{$userID}, FROM_UNIXTIME({$currentTimeStamp}), 3)";
                $this->query($sql);
            }
        }else{
            if(is_array($result)){
                $userID = intval($result["userid"]);
                $sql = "UPDATE users SET updated_at=FROM_UNIXTIME({$currentTimeStamp}), name='{$oAuthUserName}', avatar_file_name='{$oAuthUserAvatar}' WHERE id={$userID}";
                $this->query($sql);
            }else{
                $sql = "SELECT name, avatar_file_name FROM identities WHERE id={$identityID}";
                $identityInfo = $this->getRow($sql);
                $sql = "INSERT INTO users (`created_at`, `name`, `avatar_file_name`) VALUES (FROM_UNIXTIME({$currentTimeStamp}), '".$identityInfo["name"]."', '".$identityInfo["avatar_file_name"]."')";
                $result = $this->query($sql);
                $userID = intval($result["insert_id"]);
                if($userID){
                    $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID}, {$userID}, FROM_UNIXTIME($currentTimeStamp), 3)";
                    $this->query($sql);
                }
            }
        }
        return array("identityID" => $identityID, "userID" => $userID);
    }


    public function buildFriendsIndex($userID, $friendsList) {

        $redisHandler = new Redis();
        $redisHandler->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        mb_internal_encoding("UTF-8");
        foreach($friendsList as $value)
        {
            $identity = mb_strtolower($value["user_name"]);
            $identityPart = "";
            for ($i=0; $i<mb_strlen($identity); $i++)
            {
                $identityPart .= mb_substr($identity, $i, 1);
                $redisHandler->zAdd('u:'.$userID, 0, $identityPart);
            }
            $identityDetailID = $value["provider"].":".$value["customer_id"];
            $redisHandler->zAdd('u:'.$userID, 0, $identityPart."|".$identityDetailID."*");
            $identityDetail = $redisHandler->HGET("identities",$identityDetailID);
            if($identityDetail == false) {
                $identityDetail = array(
                    "external_identity" => $value["customer_id"],
                    "name"              => $value["display_name"],
                    "bio"               => $value["bio"],
                    "avatar_file_name"  => $value["avatar_img"],
                    "external_username" => $value["user_name"],
                    "provider"          => $value["provider"]
                );
                $identity = json_encode_nounicode($identityDetail);
                $redisHandler->HSET("identities", $identityDetailID, $identity);
            }

        }
    }
    */

}
