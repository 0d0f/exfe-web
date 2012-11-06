<?php

require_once dirname(dirname(__FILE__)) . '/lib/OAuth.php';
require_once dirname(dirname(__FILE__)) . '/lib/TwitterOAuth.php';
require_once dirname(dirname(__FILE__)) . '/lib/facebook.php';
require_once dirname(dirname(__FILE__)) . '/lib/tmhOAuth.php';
require_once dirname(dirname(__FILE__)) . '/lib/FoursquareAPI.class.php';


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
                $hlpIdentity = $this->getHelperByName('Identity');
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
        if ($service) {
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

    public function facebookRedirect($workflow) {
        $this->setSession('facebook', '', '', $workflow);
        return 'https://graph.facebook.com/oauth/authorize?client_id='
             . FACEBOOK_APP_ID         . '&redirect_uri='
             . FACEBOOK_OAUTH_CALLBACK . '&type=web_server';
    }


    public function getFacebookOAuthCode() {
        return $_GET['code'];
    }


    public function getFacebookOAuthToken($oauthCode) {
        if (!$oauthCode) {
            return null;
        }
        $objCurl = curl_init(
            'https://graph.facebook.com/oauth/access_token?client_id='
          . FACEBOOK_APP_ID         . '&redirect_uri='
          . FACEBOOK_OAUTH_CALLBACK . '&client_secret='
          . FACEBOOK_SECRET_KEY     . "&code={$oauthCode}"
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        if (!($data = curl_exec($objCurl))) {
            curl_close($objCurl);
            return null;
        }
        if (curl_getinfo($objCurl, CURLINFO_HTTP_CODE) !== 200) {
            curl_close($objCurl);
            return null;
        }
        $rtResult  = [];
        foreach (($rawResult = explode('&', $data)) as $item) {
            if (($arrItem = explode('=', $item))) {
                switch ($arrItem[0]) {
                    case 'access_token':
                        $rtResult['oauth_token']   = $arrItem[1];
                        break;
                    case 'expires':
                        $rtResult['oauth_expires'] = (int) $arrItem[1] + time();
                }
            }
        }
        curl_close($objCurl);
        return count($rtResult) > 1 ? $rtResult : null;
    }


    public function getFacebookProfile($oauthToken) {
        if (!$oauthToken) {
            return null;
        }
        $objCurl = curl_init(
            "https://graph.facebook.com/me?access_token={$oauthToken}"
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($rawIdentity = (array) json_decode($data))) {
            return new Identity(
                0,
                $rawIdentity['name'],
                '',
                array_key_exists('bio', $rawIdentity) ? $rawIdentity['bio'] : '',
                'facebook',
                0,
                $rawIdentity['id'],
                $rawIdentity['username'],
                "https://graph.facebook.com/{$rawIdentity['id']}/picture?type=large"
            );
        }
        return null;
    }


    public function getFacebookProfileByExternalUsername($external_username) {
        if (!$external_username) {
            return null;
        }
        $objCurl = curl_init("https://graph.facebook.com/{$external_username}");
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($rawIdentity = (array) json_decode($data))) {
            return new Identity(
                0,
                $rawIdentity['name'],
                '',
                array_key_exists('bio', $rawIdentity) ? $rawIdentity['bio'] : '',
                'facebook',
                0,
                $rawIdentity['id'],
                $rawIdentity['username'],
                "https://graph.facebook.com/{$rawIdentity['id']}/picture?type=large"
            );
        }
        return null;
    }

    // }


    public function handleCallback($rawIdentity, $oauthIfo, $rawOAuthToken = null) {
        // get models
        $hlpUser     = $this->getHelperByName('User');
        $hlpIdentity = $this->getHelperByName('Identity');
        // get identity object
        $objIdentity = $hlpIdentity->getIdentityByProviderAndExternalUsername(
            $rawIdentity->provider, $rawIdentity->external_username, true
        );
        // 身份不存在，创建新身份
        if (!$objIdentity) {
            $identity_id = $hlpIdentity->addIdentity(
                ['provider'          => $rawIdentity->provider,
                 'external_id'       => $rawIdentity->external_id,
                 'name'              => $rawIdentity->name,
                 'bio'               => $rawIdentity->bio,
                 'external_username' => $rawIdentity->external_username,
                 'avatar_filename'   => $rawIdentity->avatar_filename]
            );
            $objIdentity = $hlpIdentity->getIdentityById($identity_id, null, true);
        }
        if (!$objIdentity) {
            return null;
        }
        // init check
        $user_id         = 0;
        $identity_status = '';
        // verifying
        if (($user_id = @ (int) $oauthIfo['workflow']['user_id'])) {
            $identity_status = 'verifying';
        // 身份被 revoked，重新连接用户
        } else if (($user_id = $objIdentity->revoked_user_id)) {
            $identity_status = 'revoked';
        } else if (($user_id = $objIdentity->connected_user_id) > 0) {
            $identity_status = 'connected';
        // 孤立身份，创建新用户并连接到该身份
        } else if (($user_id = $hlpUser->addUser(
            '', $rawIdentity->name ?: $rawIdentity->external_username
        ))) {
            $identity_status = 'new';
        // no user_id
        } else {
            return null;
        }
        // connect user
        if ($user_id === $objIdentity->connected_user_id) {
            $identity_status = 'connected';
        } else {
            if (!$hlpUser->setUserIdentityStatus(
                $user_id, $objIdentity->id, 3
            )) {
                return null;
            }
            $objIdentity->connected_user_id = $user_id;
        }
        // 更新 OAuth Token
        switch ($objIdentity->provider) {
            case 'twitter':
                $oAuthToken = [
                    'oauth_token'        => $oauthIfo['oauth_token'],
                    'oauth_token_secret' => $oauthIfo['oauth_token_secret'],
                ];
                break;
            case 'facebook':
                $oAuthToken = [
                    'oauth_token'        => $rawOAuthToken['oauth_token'],
                    'oauth_expires'      => $rawOAuthToken['oauth_expires'],
                ];
        }
        $hlpIdentity->updateOAuthTokenById($objIdentity->id, $oAuthToken);
        // 使用该身份登录
        $rstSignin = $hlpUser->rawSignin($objIdentity->connected_user_id);
        // call Gobus { @todo upgrading by @leaskh
        if ($objIdentity->provider === 'twitter') {
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
        }
        // }
        // return
        $result = [
            'oauth_signin'    => $rstSignin,
            'identity'        => $objIdentity,
            'identity_status' => $identity_status,
        ];
        switch ($objIdentity->provider) {
            case 'twitter':
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
                $result['twitter_following'] = $twitterConn->response['response'] === 'true';
        }
        return $result;
    }







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
    */

}
