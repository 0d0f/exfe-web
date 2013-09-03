<?php

$libPath = dirname(dirname(__FILE__)) . '/lib/';

require_once "{$libPath}/twitteroauth/OAuth.php";
require_once "{$libPath}/twitteroauth/twitteroauth.php";
require_once "{$libPath}facebook.php";
require_once "{$libPath}Instagram.php";
require_once "{$libPath}tmhOAuth.php";
require_once "{$libPath}FoursquareAPI.class.php";
require_once "{$libPath}google_api_client/Google_Client.php";
require_once "{$libPath}google_api_client/contrib/Google_Oauth2Service.php";
require_once "{$libPath}google_api_client/contrib/Google_PlusService.php";


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
                    0, $rawTwitterUserInfo['name'], '',
                    $rawTwitterUserInfo['description'],
                    'twitter', 0, $rawTwitterUserInfo['id'],
                    $rawTwitterUserInfo['screen_name'],
                    $hlpIdentity->getTwitterAvatarBySmallAvatar(
                        $rawTwitterUserInfo['profile_image_url']
                    ), '', '', 0, false,
                    strtolower(trim($rawTwitterUserInfo['lang']))
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


    public function getTwitterProfileByExternalUsername($external_username) {
        $hlpIdentity = $this->getHelperByName('Identity');
        if (!$external_username) {
            return null;
        }
        $twitterConn = new TwitterOAuth(
            TWITTER_CONSUMER_KEY,
            TWITTER_CONSUMER_SECRET,
            TWITTER_ACCESS_TOKEN,
            TWITTER_ACCESS_TOKEN_SECRET
        );
        $twitterUser = $twitterConn->get('users/show', ['screen_name' => $external_username]);
        if ($twitterUser && @$twitterUser->id) {
            return new Identity(
                0, $twitterUser->name, '', $twitterUser->description, 'twitter',
                0, $twitterUser->id, $twitterUser->screen_name,
                $hlpIdentity->getTwitterAvatarBySmallAvatar(
                    $twitterUser->profile_image_url
                )
            );
        }
        return null;
    }

    // }


    // facebook {

    public function facebookRedirect($workflow) {
        $this->setSession('facebook', '', '', $workflow);
        return 'https://graph.facebook.com/oauth/authorize'
             . '?client_id='    . FACEBOOK_APP_ID
             . '&redirect_uri=' . FACEBOOK_OAUTH_CALLBACK
             . '&type=web_server'
             . '&scope=user_photos,email,user_birthday,user_online_presence,status_update,photo_upload,video_upload,create_note,share_item,publish_stream';
    }


    public function getFacebookOAuthCode() {
        return $_GET['code'];
    }


    public function getFacebookPermissions($oauthToken) {
        if (!$oauthToken) {
            return null;
        }
        $objCurl = curl_init(
            "https://graph.facebook.com/me/permissions?access_token={$oauthToken}"
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        // anti-gfw by @leask {
        if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
            curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
            curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
            if (PROXY_TYPE === 'socks') {
                curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        // }
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($data = (array) json_decode($data)) && isset($data['data'])) {
            $data = $data['data'];
            if (sizeof($data) > 0) {
                return array_keys((array) $data[0]);
            }
        }
        return null;
    }


    public function getFacebookOAuthToken($oauthCode) {
        if (!$oauthCode) {
            return null;
        }
        $objCurl = curl_init(
            'https://graph.facebook.com/oauth/access_token'
          . '?client_id='     . FACEBOOK_APP_ID
          . '&redirect_uri='  . FACEBOOK_OAUTH_CALLBACK
          . '&client_secret=' . FACEBOOK_SECRET_KEY
          . "&code={$oauthCode}"
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        // anti-gfw by @leask {
        if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
            curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
            curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
            if (PROXY_TYPE === 'socks') {
                curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        // }
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
        // anti-gfw by @leask {
        if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
            curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
            curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
            if (PROXY_TYPE === 'socks') {
                curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        // }
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($rawIdentity = json_decode($data, true)) && !isset($rawIdentity['error'])) {
            $hlpTime  = $this->getHelperByName('Time');
            $hlpIdent = $this->getHelperByName('Identity');
            $timezone = $hlpTime->convertFacebookTimezone($rawIdentity['timezone']);
            $timezone = $hlpTime->getTimezoneNameByRaw($timezone);
            $avatar   = $hlpIdent->getFacebookAvatar($rawIdentity['id']);
            return new Identity(
                0, $rawIdentity['name'], '',
                array_key_exists('bio', $rawIdentity) ? $rawIdentity['bio'] : '',
                'facebook', 0, $rawIdentity['id'], $rawIdentity['username'],
                $avatar, '', '', 0, false,
                strtolower(trim($rawIdentity['locale'])), $timezone
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
        // anti-gfw by @leask {
        if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
            curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
            curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
            if (PROXY_TYPE === 'socks') {
                curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        // }
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($rawIdentity = json_decode($data, true)) && !isset($rawIdentity['error'])) {
            $hlpTime  = $this->getHelperByName('Time');
            $hlpIdent = $this->getHelperByName('Identity');
            $timezone = $hlpTime->convertFacebookTimezone($rawIdentity['timezone']);
            $timezone = $hlpTime->getTimezoneNameByRaw($timezone);
            $avatar   = $hlpIdent->getFacebookAvatar($rawIdentity['id']);
            return new Identity(
                0, $rawIdentity['name'], '',
                array_key_exists('bio', $rawIdentity) ? $rawIdentity['bio'] : '',
                'facebook', 0, $rawIdentity['id'], $rawIdentity['username'],
                $avatar, '', '', 0, false,
                strtolower(trim($rawIdentity['locale'])), $timezone
            );
        }
        return null;
    }

    // }


    // dropbox {

    public function dropboxRedirect($workflow) {
        // get oauth token
        $objCurl = curl_init('https://api.dropbox.com/1/oauth/request_token');
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        // anti-gfw by @leask {
        if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
            curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
            curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
            if (PROXY_TYPE === 'socks') {
                curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        // }
        curl_setopt($objCurl, CURLOPT_HTTPHEADER, [
            'Authorization: '
          . 'OAuth oauth_version="1.0", '
          . 'oauth_signature_method="PLAINTEXT", '
          . 'oauth_consumer_key="' . DROPBOX_APP_KEY    . '", '
          . 'oauth_signature="'    . DROPBOX_APP_SECRET . '&"'
        ]);
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        // oauth_token=<request-token>&oauth_token_secret=<request-token-secret>
        if ($data) {
            $data        = explode('&', $data);
            $oauth_token = [];
            if (sizeof($data) > 1) {
                foreach ($data as $item) {
                    $item = explode('=', $item);
                    if (sizeof($item) === 2) {
                        $oauth_token[$item[0]] = $item[1];
                    } else {
                        return false;
                    }
                }
                // get oauth url
                $this->setSession(
                    'dropbox',
                    $oauth_token['oauth_token'],
                    $oauth_token['oauth_token_secret'],
                    $workflow
                );
                return "https://www.dropbox.com/1/oauth/authorize?oauth_token={$oauth_token['oauth_token']}&oauth_callback=" . urlencode(DROPBOX_OAUTH_CALLBACK);
            }
        }
        return false;
    }


    public function getDropboxOAuthToken() {
        $requestToken = $this->getSession();
        if ($requestToken
         && $requestToken['external_service'] === 'dropbox'
         && $requestToken['oauth_token']
         && $requestToken['oauth_token_secret']) {
            // get oauth token
            $objCurl = curl_init('https://api.dropbox.com/1/oauth/access_token');
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
            // anti-gfw by @leask {
            if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
                curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
                curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
                if (PROXY_TYPE === 'socks') {
                    curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                }
            }
            // }
            curl_setopt($objCurl, CURLOPT_HTTPHEADER, [
                'Authorization: '
              . 'OAuth oauth_version="1.0", '
              . 'oauth_signature_method="PLAINTEXT", '
              . 'oauth_consumer_key="' . DROPBOX_APP_KEY                     . '", '
              . 'oauth_token="'        . $requestToken['oauth_token']        . '", '
              . 'oauth_signature="'    . DROPBOX_APP_SECRET                  . '&'
                                       . $requestToken['oauth_token_secret'] . '"'
            ]);
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            // oauth_token=<access-token>&oauth_token_secret=<access-token-secret>&uid=<user-id>
            if ($data) {
                $data        = explode('&', $data);
                $oauth_token = [];
                if (sizeof($data) > 1) {
                    foreach ($data as $item) {
                        $item = explode('=', $item);
                        if (sizeof($item) === 2) {
                            $oauth_token[$item[0]] = $item[1];
                        } else {
                            return false;
                        }
                    }
                    $this->addtoSession([
                        'oauth_token'        => $oauth_token['oauth_token'],
                        'oauth_token_secret' => $oauth_token['oauth_token_secret'],
                    ]);
                    return true;
                }
            }
        }
        return false;
    }


    public function getDropboxProfile($oauthToken, $oauthTokenSecret) {
        if ($oauthToken && $oauthTokenSecret) {
            // get oauth token
            $objCurl = curl_init('https://api.dropbox.com/1/account/info');
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
            // anti-gfw by @leask {
            if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
                curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
                curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
                if (PROXY_TYPE === 'socks') {
                    curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                }
            }
            // }
            curl_setopt($objCurl, CURLOPT_HTTPHEADER, [
                'Authorization: '
              . 'OAuth oauth_version="1.0", '
              . 'oauth_signature_method="PLAINTEXT", '
              . 'oauth_consumer_key="' . DROPBOX_APP_KEY    . '", '
              . 'oauth_token="'        . $oauthToken        . '", '
              . 'oauth_signature="'    . DROPBOX_APP_SECRET . '&'
                                       . $oauthTokenSecret  . '"'
            ]);
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            // pack identity
            if ($data && ($data = json_decode($data, true))) {
                return new Identity(
                    0, $data['display_name'], '', '', 'dropbox',
                    0, $data['uid'], $data['email'], ''
                );
            }
        }
        return false;
    }

    // }


    // instagram {

    public function instagramRedirect($workflow) {
        $this->setSession('instagram', '', '', $workflow);
        $instagram = new Instagram(
            INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET, null
        );
        return $instagram->authorizeUrl(
            INSTAGRAM_REDIRECT_URI,
            ['basic', 'comments', 'likes', 'relationships']
        );
    }


    public function getInstagramProfile() {
        $instagram = new Instagram(
            INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET, null
        );
        $profile = $instagram->getAccessToken(
            $_GET['code'], INSTAGRAM_REDIRECT_URI
        );
        if ($profile && isset($profile->access_token) && isset($profile->user)) {
            return [
                'identity'    => new Identity(
                    0, $profile->user->full_name, '', $profile->user->bio,
                    'instagram', 0, $profile->user->id,
                    $profile->user->username,
                    [
                        'original' => $profile->user->profile_picture,
                        '320_320'  => $profile->user->profile_picture,
                        '80_80'    => $profile->user->profile_picture,
                    ]
                ),
                'oauth_token' => ['oauth_token' => $profile->access_token],
            ];
        }
        return null;
    }

    // }


    // flickr {

    public function requestFlickr($url, $args = [], $oauth_token_secret = '') {
        if ($url) {
            $args['oauth_nonce']            = crc32(time()); // rand(1, 99999999)
            $args['oauth_timestamp']        = time();
            $args['oauth_consumer_key']     = FLICKR_KEY;
            $args['oauth_signature_method'] = 'HMAC-SHA1';
            $args['oauth_version']          = '1.0';
            $args['oauth_callback']         = FLICKR_OAUTH_CALLBACK;
            ksort($args);
            $signature = base64_encode(hash_hmac(
                'sha1',
                'GET&' . urlencode($url) . '&' . urlencode(http_build_query($args)),
                urlencode(FLICKR_SECRET) . '&' . urlencode($oauth_token_secret ?: ''),
                true
            ));
            $args['oauth_signature']        = urlencode($signature);
            $header = ['Authorization: OAuth realm=""'];
            foreach ($args as $name => $value) {
                if (strncmp($name, 'oauth_', 6) === 0 || strncmp($name, 'xoauth_', 7) === 0) {
                    $header[] = $name . '="' . $value . '"';
                }
            }
            $objCurl = curl_init($url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
            // anti-gfw by @leask {
            if (PROXY_TYPE && PROXY_ADDR && PROXY_PORT) {
                curl_setopt($objCurl, CURLOPT_PROXY,     PROXY_ADDR);
                curl_setopt($objCurl, CURLOPT_PROXYPORT, PROXY_PORT);
                if (PROXY_TYPE === 'socks') {
                    curl_setopt($objCurl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                }
            }
            // }
            curl_setopt($objCurl, CURLOPT_HTTPHEADER, [implode(', ', $header)]);
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            if ($data) {
                return $data;
            }
        }
        return null;
    }


    public function flickrRedirect($workflow) {
        $rawResult = $this->requestFlickr(
            'http://www.flickr.com/services/oauth/request_token'
        );
        if ($rawResult) {
            $rawResult = explode('&', $rawResult);
            $data      = [];
            foreach ($rawResult as $rI => $rItem) {
                $rItem = explode('=', $rItem);
                $data[$rItem[0]] = urldecode($rItem[1]);
            }
            if (isset($data['oauth_callback_confirmed'])
             && isset($data['oauth_token'])
             && isset($data['oauth_token_secret'])) {
                // get oauth url
                $this->setSession(
                    'flickr',
                    $data['oauth_token'],
                    $data['oauth_token_secret'],
                    $workflow
                );
                return "http://www.flickr.com/services/oauth/authorize?oauth_token={$data['oauth_token']}";
            }
        }
        return null;
    }


    public function getFlickrProfile($verifier) {
        $requestToken = $this->getSession();
        if ($verifier
         && $requestToken
         && $requestToken['external_service'] === 'flickr'
         && $requestToken['oauth_token']
         && $requestToken['oauth_token_secret']) {
            $rawResult = $this->requestFlickr(
                'http://www.flickr.com/services/oauth/access_token',
                ['oauth_verifier' => $verifier,
                 'oauth_token'    => $requestToken['oauth_token']],
                $requestToken['oauth_token_secret']
            );
            if ($rawResult) {
                $rawResult = explode('&', $rawResult);
                $data = [];
                foreach ($rawResult as $rI => $rItem) {
                    $rItem = explode('=', $rItem);
                    $data[$rItem[0]] = urldecode($rItem[1]);
                }
                if (isset($data['fullname'])
                 && isset($data['oauth_token'])
                 && isset($data['oauth_token_secret'])
                 && isset($data['user_nsid'])
                 && isset($data['username'])) {
                    $avatar = "http://www.flickr.com/buddyicons/{$data['user_nsid']}.jpg";
                    return [
                        'identity'    => new Identity(
                            0, $data['fullname'], '', '', 'flickr',
                            0, $data['user_nsid'], $data['username'],
                            ['original' => $avatar, '320_320' => $avatar, '80_80' => $avatar]
                        ),
                        'oauth_token' => [
                            'oauth_token'        => $data['oauth_token'],
                            'oauth_token_secret' => $data['oauth_token_secret'],
                        ],
                    ];
                }
            }
        }
        return false;
    }

    // }


    // google {

    public function getGoogleClient() {
        $client = new Google_Client();
        $client->setApplicationName(GOOGLE_APP_NAME);
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URIS);
        $client->setScopes([
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/plus.login',
            'https://www.googleapis.com/auth/userinfo.profile',
        ]);
        return $client;
    }


    public function googleRedirect($workflow) {
        $client = $this->getGoogleClient();
        $plus = new Google_PlusService($client);
        $authUrl = $client->createAuthUrl();
        if ($authUrl) {
            $this->setSession('google', '', '', $workflow);
            return $authUrl;
        }
        return false;
    }


    public function getGoogleTokenFrom($client) {
        $token = $client->getAccessToken();
        $token = @json_decode($token, true);
        if ($token) {
            $token['oauth_expires'] = $token['created'] + $token['expires_in'];
            return $token;
        }
        return null;
    }


    public function getGoogleOAuthToken() {
        $client = $this->getGoogleClient();
        $client->authenticate();
        return $this->getGoogleTokenFrom($client);
    }


    public function refreshGoogleToken($token, $changedOnly = false) {
        if ($token) {
            if (($token['oauth_expires'] - GOOGLE_TOKEN_LIFE) <= time()) {
                $client = $this->getGoogleClient();
                $client->setAccessType('offline');
                $client->setAccessToken(json_encode($token));
                $client->refreshToken($token['refresh_token']);
                $token = $this->getGoogleTokenFrom($client);
            } else if ($changedOnly) {
                return null;
            }
            return $token;
        }
        return null;
    }


    public function getGoogleProfile($token) {
        $client = $this->getGoogleClient();
        $token  = $this->refreshGoogleToken($token);
        if ($token) {
            $client->setAccessToken(json_encode($token));
            $plus   = new Google_PlusService($client);
            $oauth2 = new Google_Oauth2Service($client);
            $plusProfile   = $plus->people->get('me');
            $googleProfile = $oauth2->userinfo_v2_me->get();
            if ($plusProfile && $googleProfile) {
                $hlpIdentity = $this->getHelperByName('Identity');
                $avatar = $hlpIdentity->getGoogleAvatarBySmallAvatar(
                    $plusProfile['image']['url']
                );
                return [
                    'identity'    => new Identity(
                        0, $plusProfile['displayName'],
                        $plusProfile['name']['givenName'],
                        $plusProfile['aboutMe'] ?: $plusProfile['tagline'],
                        'google', 0, $plusProfile['id'],
                        $googleProfile['email'], $avatar, '', '', 0, false,
                        strtolower(trim($plusProfile['locale']))
                    ),
                    'oauth_token' => $token,
                ];
            }
        }
        return null;
    }

    // }


    // wechat {

    public function wechatRedirect($workflow, $step = 2) {
        $this->setSession('wechat', '', '', $workflow);
        switch ($step) {
            case 1:
                $scope = 'snsapi_base';
                break;
            case 2:
                $scope = 'snsapi_userinfo';
                break;
            default:
                return null;
        }
        return 'https://open.weixin.qq.com/connect/oauth2/authorize'
             . '?appid='        . WECHAT_OFFICIAL_ACCOUNT_APPID
             . '&redirect_uri=' . WECHAT_REDIRECT_URI
             . '&response_type=code'
             . "&scope={$scope}"
             . "&state={$step}"
             . '#wechat_redirect';
    }


    public function getWechatAccessToken($oauthCode) {
        if (!$oauthCode) {
            return null;
        }
        $objCurl = curl_init(
            'https://api.weixin.qq.com/sns/oauth2/access_token'
          . '?appid='  . WECHAT_OFFICIAL_ACCOUNT_APPID
          . '&secret=' . WECHAT_OFFICIAL_ACCOUNT_SECRET
          . '&code='   . $oauthCode
          . '&grant_type=authorization_code'
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($data = (array) json_decode($data)) && isset($data['access_token'])) {
            return $data;
        }
        return null;
    }


    public function refreshWechatAccessToken() {
        // https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APPID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN
    }


    public function getWechatProfile($openid, $token) {
        if ($token) {
            $objCurl = curl_init(
                'https://api.weixin.qq.com/sns/userinfo'
              . '?access_token=' . $token['access_token']
              . '&openid='       . $openid
            );
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            if ($data && ($data = (array) json_decode($data)) && isset($data['openid'])) {
                return $data;
            }
            return null;
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
            $rawIdentity->provider, $rawIdentity->external_username
        );
        // 身份不存在，创建新身份
        if (!$objIdentity) {
            $identity_id = $hlpIdentity->addIdentity([
                'provider'          => $rawIdentity->provider,
                'external_id'       => $rawIdentity->external_id,
                'name'              => $rawIdentity->name,
                'bio'               => $rawIdentity->bio,
                'external_username' => $rawIdentity->external_username,
                'avatar'            => $rawIdentity->avatar,
                'locale'            => @$rawIdentity->locale,
                'timezone'          => @$rawIdentity->timezone,
            ]);
            $objIdentity = $hlpIdentity->getIdentityById($identity_id);
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
        // 身份 connected 或者被 revoked，重新连接用户
        } else if (($user_id = $objIdentity->connected_user_id) > 0) {
            $identity_status = isset($objIdentity->status) && $objIdentity->status === 'REVOKED'
                             ? 'revoked' : 'connected';
        // 孤立身份，创建新用户并连接到该身份
        } else if (($user_id = $hlpUser->addUser(
            '',
            $rawIdentity->name ?: $rawIdentity->external_username,
            $rawIdentity->bio  ?: $rawIdentity->bio
        ))) {
            $identity_status = 'new';
        // no user_id
        } else {
            return null;
        }
        // connect user
        if ($user_id !== $objIdentity->connected_user_id || $identity_status !== 'connected') {
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
            case 'dropbox':
            case 'flickr':
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
                break;
            case 'instagram':
                $oAuthToken = [
                    'oauth_token'        => $rawOAuthToken['oauth_token'],
                ];
                break;
            case 'google':
            case 'wechat':
                $oAuthToken = $rawOAuthToken;
        }
        $hlpIdentity->updateOAuthTokenById($objIdentity->id, $oAuthToken);
        // 使用该身份登录
        $rstSignin = $hlpUser->rawSignin($objIdentity->connected_user_id);
        // call Gobus {
        $hlpQueue = $this->getHelperByName('Queue');
        $hlpQueue->updateIdentity($objIdentity, $oAuthToken);
        $hlpQueue->updateFriends($objIdentity,  $oAuthToken);
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
                    $twitterConn->url('1.1/friendships/exists'),
                    ['screen_name_a' => $objIdentity->external_username,
                     'screen_name_b' => TWITTER_OFFICE_ACCOUNT]
                );
                $result['twitter_following'] = $twitterConn->response['response'] === 'true';
        }
        return $result;
    }

}
