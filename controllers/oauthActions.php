<?php

class OAuthActions extends ActionController {

    public function doTwitterAuthenticate() {
        $this->doAuthenticate('twitter');
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
            case 'dropbox':
                $urlOauth = $modOauth->dropboxRedirect($workflow);
                break;
            case 'instagram':
                $urlOauth = $modOauth->instagramRedirect($workflow);
                break;
            case 'flickr':
                $urlOauth = $modOauth->flickrRedirect($workflow);
                break;
            case 'google':
                $urlOauth = $modOauth->googleRedirect($workflow);
                break;
            case 'wechat':
                $urlOauth = $modOauth->wechatRedirect(
                    $workflow, @strtolower($_GET['base'] === 'true' ? 1 : 2)
                );
                // header("location: {$urlOauth}");
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


    public function doReverseauth() {
        // init models
        $modOauth = $this->getModelByName('OAuth');
        header('Content-Type: application/json; charset=UTF-8'); // @todo v2 only. by @leask
        // grep inputs
        $provider           = @ strtolower($_POST['provider'] ?: '');
        $oauth_token        = @ $_POST['oauth_token'];
        $oauth_token_secret = @ $_POST['oauth_token_secret'];
        $oauth_expires      = @ $_POST['oauth_expires'];
        switch ($provider) {
            case 'twitter':
                if ($oauth_token && $oauth_token_secret) {
                    $rawIdentity = $modOauth->verifyTwitterCredentials(
                        $oauth_token, $oauth_token_secret
                    );
                    if ($rawIdentity) {
                        $result = $modOauth->handleCallback($rawIdentity, [
                            'oauth_token'        => $oauth_token,
                            'oauth_token_secret' => $oauth_token_secret,
                        ]);
                        if ($result) {
                            apiResponse([
                                'user_id' => $result['oauth_signin']['user_id'],
                                'token'   => $result['oauth_signin']['token'],
                            ]);
                            return;
                        }
                    }
                }
                apiError(400, 'invalid_token', '');
                return;
            case 'facebook':
                if ($oauth_token && $oauth_expires) {
                    $rawIdentity = $modOauth->getFacebookProfile($oauth_token);
                    if ($rawIdentity) {
                        $result = $modOauth->handleCallback($rawIdentity, [], [
                            'oauth_token'   => $oauth_token,
                            'oauth_expires' => $oauth_expires,
                        ]);
                        if ($result) {
                            apiResponse([
                                'user_id' => $result['oauth_signin']['user_id'],
                                'token'   => $result['oauth_signin']['token'],
                            ]);
                            return;
                        }
                    }
                }
                apiError(400, 'invalid_token', '');
                return;
            case '':
                apiError(400, 'no_provider', '');
                return;
            default:
                apiError(400, 'unsupported_provider', '');
        }
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
                header('location: /');
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
                        header('location: /');
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
                      . '&twitter_following=' . ($result['twitter_following'] ? 'true' : 'false')
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
                header('location: /');
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
                header('location: /');
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
                        header('location: /');
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
                header('location: /');
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


    public function doDropboxCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo
         || $oauthIfo['external_service'] !== 'dropbox'
         || (isset($oauthIfo['oauth_token'])
          && $oauthIfo['oauth_token'] !== $_REQUEST['oauth_token'])) {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'dropbox']);
                header('location: /');
            }
            return;
        }
        if ($modOauth->getDropboxOAuthToken()) {
            $oauthIfo = $modOauth->getSession();
            $rawIdentity = $modOauth->getDropboxProfile(
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
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'dropbox']);
                        header('location: /');
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
                header('location: /');
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


    public function doInstagramCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo || $oauthIfo['external_service'] !== 'instagram') {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'instagram']);
                header('location: /');
            }
            return;
        }
        $profile = $modOauth->getInstagramProfile();
        if ($profile) {
            $rawIdentity = $profile['identity'];
            $oauthToken  = $profile['oauth_token'];
            if ($rawIdentity) {
                $result = $modOauth->handleCallback($rawIdentity, $oauthIfo, $oauthToken);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'instagram']);
                        header('location: /');
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
                header('location: /');
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


    public function doFlickrCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo
         || $oauthIfo['external_service'] !== 'flickr'
         || (isset($oauthIfo['oauth_token'])
          && $oauthIfo['oauth_token'] !== $_REQUEST['oauth_token'])) {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'flickr']);
                header('location: /');
            }
            return;
        }
        $profile = $modOauth->getFlickrProfile($_REQUEST['oauth_verifier']);
        if ($profile) {
            $rawIdentity = $profile['identity'];
            $oauthToken  = $profile['oauth_token'];
            if ($rawIdentity) {
                $result = $modOauth->handleCallback($rawIdentity, $oauthIfo);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'flickr']);
                        header('location: /');
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
                header('location: /');
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


    public function doGoogleCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo || $oauthIfo['external_service'] !== 'google') {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'google']);
                header('location: /');
            }
            return;
        }
        $token   = $modOauth->getGoogleOAuthToken();
        $profile = $modOauth->getGoogleProfile($token);
        if ($profile) {
            $rawIdentity = $profile['identity'];
            $oauthToken  = $profile['oauth_token'];
            if ($rawIdentity) {
                $result = $modOauth->handleCallback($rawIdentity, $oauthIfo, $oauthToken);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'google']);
                        header('location: /');
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
                header('location: /');
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


    public function doWechatCallBack() {
        $modOauth = $this->getModelByName('OAuth');
        $oauthIfo = $modOauth->getSession();
        $workflow = $oauthIfo ?  $oauthIfo['workflow'] : null;
        $isMobile = $workflow ? ($workflow['callback']
                 && $workflow['callback']['oauth_device']
                 && $workflow['callback']['oauth_device_callback']) : false;
        if (!$oauthIfo || $oauthIfo['external_service'] !== 'wechat') {
            if ($isMobile) {
                $modOauth->resetSession();
                header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
            } else {
                $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'wechat']);
                header('location: /');
            }
            return;
        }
        if (($token = $modOauth->getWechatAccessToken($_REQUEST['code']))) {
            $rawIdentity = $modOauth->getWechatProfile($token['openid'], $token);
            if ($rawIdentity) {
                $modWechat = $this->getModelByName('Wechat');
                $rawIdentity = $modWechat->makeIdentityBy($rawIdentity);
                $result = $modOauth->handleCallback($rawIdentity, [], $token);
                if (!$result) {
                    if ($isMobile) {
                        $modOauth->resetSession();
                        header("location: {$workflow['callback']['oauth_device_callback']}?err=OAutherror");
                    } else {
                        $modOauth->addtoSession(['oauth_signin' => false, 'provider' => 'wechat']);
                        header('location: /');
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
                header('location: ' . $workflow['callback']['url']);
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
        $params      = $this->params;
        $identity_id = trim($_POST['identity_id']);
        // basic check
        $result      = $checkHelper->isAPIAllow('user_edit', $params['token']);
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
                    $twitterConn->url('1.1/friendships/create'),
                    ['screen_name' => TWITTER_OFFICE_ACCOUNT]
                );
                apiResponse(new stdClass);
                break;
            default:
                apiError(400, 'invalid_relation', ''); // 用户和身份关系错误
        }
        apiError(500, 'failed', '');
    }

}
