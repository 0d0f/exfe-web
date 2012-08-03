<?php

require_once dirname(dirname(__FILE__)) . '/../lib/OAuth.php';
require_once dirname(dirname(__FILE__)) . '/../lib/TwitterOAuth.php';
require_once dirname(dirname(__FILE__)) . '/../lib/FacebookOAuth.php';
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


    // twitter {

    public function fb() {
        $facebookHandler = new FacebookOauth([
            'appId'  => FACEBOOK_APP_ID,
            'secret' => FACEBOOK_SECRET_KEY,
            'cookie' => true
        ]);
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
