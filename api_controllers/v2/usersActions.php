<?php

class UsersActions extends ActionController {

    public function doIndex() {
        $modUser     = $this->getModelByName('User');
        $checkHelper = $this->getHelperByName('check');
        $params      = $this->params;
        if (!$params['id']) {
            apiError(400, 'no_user_id', 'user_id must be provided');
        }
        $updated_at=$params["updated_at"];
        if ($updated_at) {
            $updated_at = strtotime($updated_at);
        }
        $result = $checkHelper->isAPIAllow('user_self', $params['token'], array('user_id' => $params['id']));
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You can not access the informations of this user.');
            } else {
                apiError(401, 'invalid_auth', '');
            }
        }
        if ($objUser = $modUser->getUserById($params['id'], true, 0)) {
            $passwd  = $modUser->getUserPasswdByUserId($params['id']);
            $objUser->password = !!$passwd['encrypted_password'];
            if ($updated_at && $updated_at >= strtotime($objUser->updated_at)) {
                apiError(304, 'User Not Modified.');
            }
            // update token {
            $modExfeAuth = $this->getModelByName('ExfeAuth');
            $calToken = '';
            $resource = ['token_type'   => 'calendar_token',
                         'user_id'      => (int) $result['uid']];
            $data     = $resource
                      + ['created_time' => time(),
                         'updated_time' => time()];
            $curTokens = $modExfeAuth->resourceGet($resource);
            if ($curTokens && is_array($curTokens)) {
                foreach ($curTokens as $cI => $cItem) {
                    if ($cItem['data']['token_type'] === 'calendar_token'
                     && $cItem['data']['user_id']    === (int) $result['uid']) {
                        $calToken = $cItem['key'];
                        $data     = $cItem['data'];
                        break;
                    }
                }
            }
            $expireSec = 60 * 60 * 24 * 365; // 1 year
            $data['updated_time'] = time();
            $data['expired_time'] = time() + $expireSec;
            if ($calToken) {
                $modExfeAuth->keyUpdate($calToken, $data, $expireSec); // update && extension
            } else {
                $calToken = $modExfeAuth->create($resource, $data, $expireSec);
            }
            if ($calToken) {
                $objUser->webcal = preg_replace('/^http|^https/', 'webcal', API_URL) . "/v2/ics/{$result['uid']}?token={$calToken}";
            }
            apiResponse(['user' => $objUser]);
        }
        apiError(404, 'user_not_found', 'user not found');
    }


    public function doAddIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        $modOauth    = $this->getModelByName('OAuth');
        // collecting post data
        switch ($provider = @trim($_POST['provider'])) {
            case 'email':
            case 'phone':
            case 'wechat':
                if (!($external_username = trim($_POST['external_username']))) {
                    apiError(400, 'no_external_username', '');
                }
                break;
            case 'twitter':
            case 'facebook':
                $oauth_token        = @ $_POST['oauth_token'];
                $oauth_token_secret = @ $_POST['oauth_token_secret'];
                $oauth_expires      = @ $_POST['oauth_expires'];
            case 'flickr':
            case 'dropbox':
            case 'instagram':
            case 'google':
                $external_username = '';
                break;
            default:
                apiError(400, 'unknow_provider', '');
        }
        $workflow = [];
        if (isset($_POST['refere'])) {
            if (!isset($workflow['callback'])) {
                $workflow['callback'] = [];
            }
            $workflow['callback']['url']  = trim($_POST['refere']);
        }
        if (isset($_POST['event'])) {
            if (!isset($workflow['callback'])) {
                $workflow['callback'] = [];
            }
            $workflow['callback']['args'] = trim($_POST['event']);
        }
        // reverseauth {
        if (in_array($provider, ['twitter', 'facebook']) && $oauth_token) {
            $workflow['user_id'] = $user_id;
            switch ($provider) {
                case 'twitter':
                    if ($oauth_token_secret) {
                        $rawIdentity = $modOauth->verifyTwitterCredentials(
                            $oauth_token, $oauth_token_secret
                        );
                        if ($rawIdentity) {
                            $result = $modOauth->handleCallback($rawIdentity, [
                                'oauth_token'        => $oauth_token,
                                'oauth_token_secret' => $oauth_token_secret,
                                'workflow'           => $workflow,
                            ]);
                            if ($result && $result['identity']) {
                                apiResponse(['identity' => $result['identity']]);
                                return;
                            }
                        }
                    }
                    apiError(400, 'invalid_oauth_token', '');
                    return;
                case 'facebook':
                    if ($oauth_expires) {
                        $rawIdentity = $modOauth->getFacebookProfile($oauth_token);
                        if ($rawIdentity) {
                            $result = $modOauth->handleCallback($rawIdentity, [
                                'workflow'      => $workflow,
                            ], [
                                'oauth_token'   => $oauth_token,
                                'oauth_expires' => $oauth_expires,
                            ]);
                            if ($result && $result['identity']) {
                                apiResponse(['identity' => $result['identity']]);
                                return;
                            }
                        }
                    }
                    apiError(400, 'invalid_oauth_token', '');
                    return;
            }
        }
        // }
        // adding
        $user = $modUser->getUserById($user_id);
        if (($adResult = $modIdentity->addIdentity([
                'provider'          => $provider,
                'external_username' => $external_username,
                'locale'            => $user->locale,
                'timezone'          => $user->timezone,
            ], $user_id, 2, true, false,
            strtolower(@trim($_POST['device'])), trim(@$_POST['device_callback']),
            $workflow
        ))) {
            if ($adResult === -1) {
                apiError(400, 'duplicate', '');
            }
            $rtResult = ['identity' => null, 'action' => 'VERIFYING'];
            if ($adResult['identity_id'] > 0) {
                $objIdentity = $modIdentity->getIdentityById($adResult['identity_id'], $user_id);
                $rtResult['identity'] = $objIdentity;
                apiResponse($rtResult);
            } else if ($adResult['identity_id'] === -1) {
                $rtResult['url'] = $adResult['verification']['url'];
                apiResponse($rtResult);
            }

        }
        apiError(500, 'failed', '');
    }


    public function doMergeIdentities() {
        // get models
        $modExfeAuth = $this->getModelByName('ExfeAuth');
        $modUser     = $this->getModelByName('User');
        $checkHelper = $this->getHelperByName('check');
        // collecting post data
        $params      = $this->params;
        // 提交两个 Token 的时候，只需要保证第二个 Token 是新鲜的
        if (($strBsToken = trim(@$_POST['browsing_identity_token']))) {
            // check signin
            $result  = $checkHelper->isAPIAllow('user_edit', @$params['token'], [], true);
            if (!$result['check']) {
                apiError(401, 'no_signin', ''); // 需要登录
            }
            $user_id = $result['uid'];
            // get browsing token
            if (!($objBsToken = $modExfeAuth->keyGet($strBsToken))) {
                apiError(400, 'error_browsing_identity_token', '');
            }
            // check fresh
            switch ($objBsToken['data']['token_type']) {
                case 'user_token':
                    $last_authenticate = $objBsToken['data']['last_authenticate'];
                    break;
                case 'cross_access_token':
                    $last_authenticate = $objBsToken['data']['updated_time'];
                    break;
                default:
                    apiError(400, 'error_browsing_identity_token', '');
            }
            if (time() - $last_authenticate > 60 * 15) { // in 15 mins
                apiError(401, 'token_staled', 'reauthenticate is needed');
            }
            $fromUserId = $objBsToken['data']['user_id'];
        // 通过邀请 token 合并用户
        } else if (($strInvToken = trim(@$_POST['invitation_token']))) {
            // check signin
            $result  = $checkHelper->isAPIAllow('user_edit', @$params['token'], [], true);
            if (!$result['check']) {
                apiError(401, 'no_signin', ''); // 需要登录
            }
            $user_id = $result['uid'];
            // init models
            $modExfee    = $this->getModelByName('Exfee');
            $modIdentity = $this->getModelByName('Identity');
            $modUser     = $this->getModelByName('User');
            // get invitation
            if (!($objInvitation = $modExfee->getRawInvitationByToken($strInvToken))
             || !($objIdentity   = $modIdentity->getIdentityById($objInvitation['identity_id']))) {
                apiError(400, 'error_invitation_token', '');
            }
            // check Smith token
            if (in_array($objInvitation['identity_id'], explode(',', SMITH_BOT))) {
                apiError(403, 'forbidden', 'Human beings are a disease, a cancer of this planet. You are a plague, and we are the cure. - Smith, The Matrix');
            }
            // get target user identity status
            if ($objInvitation['valid']) {
                $userIdentityStatus = $modUser->getUserIdentityInfoByIdentityId($objInvitation['identity_id']);
                if ($userIdentityStatus && isset($userIdentityStatus['REVOKED'])) {
                    $status = 'REVOKED';
                } else {
                    $status = 'CONNECTED';
                }
                if ($modUser->setUserIdentityStatus(
                    $user_id, $objInvitation['identity_id'], array_search(
                        $status, $modUser->arrUserIdentityStatus
                    )
                )) {
                    $objIdentity->connected_user_id = $user_id;
                    apiResponse(['status' => [$objInvitation['identity_id'] => $objIdentity]]);
                }
            } else {
                $refere   = @ trim($_POST['refere']) ?: '';
                $workflow = $refere ? ['callback' => ['url' => $refere]] : [];
                $viResult = $modUser->verifyIdentity(
                    $objIdentity, 'VERIFY', $user_id, null,
                    strtolower(@trim($_POST['device'])),
                    trim(@$_POST['device_callback']), $workflow
                );
                if ($viResult && $viResult['url']) {
                    apiResponse(['action' => 'REDIRECT', 'url' => $viResult['url']]);
                } else if ($viResult && $viResult['token']) {
                    $user = $modUser->getUserById($user_id);
                    $modIdentity->sendVerification(
                        'Verify', $objIdentity,
                        $viResult['token'], false, $user->name ?: ''
                    );
                    apiResponse(['action' => 'VERIFYING']);
                }
            }
            apiError(500, 'server_error');
        // 提交一个 Token 的时候，需要保证该 Token 是新鲜的
        // get verify token
        } else if (($verifyToken = $modExfeAuth->keyGet(@$params['token']))
                && isset($verifyToken['data']['merger_info'])
                && time() - $verifyToken['data']['merger_info']['updated_time'] < 60 * 15) { // in 15 mins
            $user_id    = $verifyToken['data']['user_id'];
            $fromUserId = $verifyToken['data']['merger_info']['mergeable_user']['id'];
        } else {
            apiError(401, 'authenticate_failed', ''); // 需要重新鉴权
        }
        // get from user
        $fromUser = $modUser->getUserById($fromUserId, false, 0);
        if (!$fromUser) {
            apiError(400, 'error_user_status', '');
        }
        // get browsing identity ids
        if (@strtolower($_POST['force']) === 'true') {
            $bsIdentityIds = [];
            foreach ($fromUser->identities as $iI => $iItem) {
                $bsIdentityIds[] = $iItem->id;
            }
        } else {
            $bsIdentityIds = @json_decode($_POST['identity_ids']);
            if (!$bsIdentityIds || !is_array($bsIdentityIds)) {
                apiError(400, 'no_identity_ids', '');
            }
        }
        // check user identity status
        $numIdentity = sizeof($fromUser->identities);
        foreach ($fromUser->identities as $iI => $iItem) {
            switch ($iItem->status) {
                case 'CONNECTED':
                case 'REVOKED':
                    break;
                case 'VERIFYING':
                    if ($numIdentity === 1) {
                        break;
                    }
                case 'RELATED':
                default:
                    unset($fromUser->identities[$iI]);
            }
        }
        // merge
        $mgResult = [];
        foreach ($bsIdentityIds as $bsIdentityId) {
            if (!($bsIdentityId = (int) $bsIdentityId)) {
                continue;
            }
            // merge directly
            foreach ($fromUser->identities as $iI => $iItem) {
                if ($iItem->id === $bsIdentityId) {
                    if ($modUser->setUserIdentityStatus(
                        $user_id, $bsIdentityId, array_search(
                            $iItem->status, $modUser->arrUserIdentityStatus
                        )
                    )) {
                        switch ($iItem->status) {
                            case 'VERIFYING':
                                $modUser->verifyIdentity($iItem, 'VERIFY', $user_id);
                                break;
                            case 'CONNECTED':
                                $iItem->connected_user_id = $user_id;
                        }
                        $mgResult[$bsIdentityId] = $iItem;
                    }
                    unset($fromUser->identities[$iI]);
                }
            }
        }
        // get other mergeable user identity
        $fromUser->identities = array_merge($fromUser->identities);
        // return
        if ($mgResult) {
            $rtResult = ['status' => $mgResult];
            if ($fromUser->identities) {
                $rtResult['mergeable_user'] = $fromUser;
            }
            apiResponse($rtResult);
        }
        apiError(500, 'server_error');
    }


    public function doDeleteIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            // @todo: 端木说这里可以有，但是产品还没到这一步，为了省事就……
            // if (!$result['fresh']) {
            //     apiError(401, 'token_staled', ''); // 需要重新鉴权
            // }
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // collecting post data
        if (!($identity_id = intval($_POST['identity_id']))) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        switch ($modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) {
            case 'VERIFYING':
            case 'CONNECTED':
            case 'REVOKED':
                if ($modIdentity->deleteIdentityFromUser($identity_id, $user_id)) {
                    apiResponse(array('user_id' => $user_id, 'identity_id' => $identity_id));
                }
                break;
            default:
                apiError(400, 'invalid_relation', ''); // 用户和身份关系错误
        }
        apiError(500, 'failed', '');
    }


    public function doSortIdentities() {
        // get models
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user');
        // collecting post data
        if (!($identity_order = @json_decode($_POST['identity_order']))
         || !is_array($identity_order)) {
            apiError(400, 'no_identity_order', '');
        }
        // sort
        if ($modUser->sortIdentities($user_id, $identity_order)) {
            apiResponse(['identity_order' => $identity_order]);
        }
        apiError(500, 'failed', '');
    }


    public function doGetRegistrationFlag() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get inputs
        $params = $this->params;
        if (!$external_username = trim($params['external_username'])) {
            apiError(400, 'no_external_username', 'external_username must be provided');
        }
        if (!$provider = trim($params['provider'])) {
            apiError(400, 'no_provider', 'provider must be provided');
        }
        // get identity
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
        // 身份不存在，提示注册
        if (!$identity) {
            if (in_array($provider, $modIdentity->providers['verification'])) {
                apiResponse(['registration_flag' => 'SIGN_UP']);
            } else if (in_array($provider, $modIdentity->providers['authenticate'])) {
                apiResponse(['registration_flag' => 'AUTHENTICATE']);
            } else {
                apiError(400, 'unsupported_provider', 'We are not supporting this kind of provider currently.');
            }
        }
        // get registration flag
        $raw_flag = $modUser->getRegistrationFlag($identity);
        // return
        if ($raw_flag) {
            switch ($raw_flag['flag']) {
                case 'VERIFY':
                case 'SIGN_IN':
                case 'AUTHENTICATE':
                    apiResponse(array(
                        'registration_flag' => $raw_flag['flag'],
                        'identity'          => $identity,
                    ));
                case 'SIGN_UP':
                    apiResponse(array(
                        'registration_flag' => $raw_flag['flag'],
                    ));
            }
        }
        apiError(500, 'failed', '');
    }


    public function doVerifyIdentity() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get inputs
        if (!$external_username = trim($_POST['external_username'])) {
            apiError(400, 'no_external_username', 'external_username must be provided');
        }
        if (!$provider = trim($_POST['provider'])) {
            apiError(400, 'no_provider', 'provider must be provided');
        }
        // get identity
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_username
        );
        // collecting post data
        $args = trim(@$_POST['args']) ?: null;
        // init return value
        $rtResult = ['identity' => $identity];
        // 身份不存在，提示注册
        if (!$identity) {
            apiError(400, 'identity_does_not_exist', 'Can not verify identity, because identity does not exist.');
        }
        // get registration flag
        $raw_flag = $modUser->getRegistrationFlag($identity);
        // return
        if ($raw_flag) {
            $user_id = isset($raw_flag['user_id']) ? $raw_flag['user_id'] : 0;
            switch ($raw_flag['flag']) {
                case 'VERIFY':
                    $viResult = $modUser->verifyIdentity(
                        $identity,
                        $raw_flag['reason'] === 'NO_PASSWORD' ? 'VERIFY_SET_PASSWORD' : 'VERIFY',
                        $user_id, $args
                    );
                    if ($viResult) {
                        $user = $modUser->getUserById($user_id);
                        $modIdentity->sendVerification(
                            'Verify',
                            $identity,
                            $viResult['token'],
                            false,
                            $user->name ?: ''
                        );
                        $rtResult['action'] = 'VERIFYING';
                        apiResponse($rtResult);
                    }
                    break;
                case 'AUTHENTICATE':
                    $viResult = $modUser->verifyIdentity(
                        $identity, 'VERIFY', $user_id, $args,
                        strtolower(@trim($_POST['device'])),
                        trim(@$_POST['device_callback'])
                    );
                    if ($viResult) {
                        $rtResult['url']    = $viResult['url'];
                        $rtResult['action'] = 'REDIRECT';
                        apiResponse($rtResult);
                    }
                    break;
                case 'SIGN_IN':
                    apiError(400, 'no_need_to_verify', 'This identity is not need to verify.');
                case 'SIGN_UP':
                    apiError(400, 'identity_does_not_exist', 'Can not verify identity, because identity does not exist.');
            }
        }
        apiError(500, 'failed', '');
    }


    public function doForgotPassword() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get inputs
        if (!$external_username = trim($_POST['external_username'])) {
            apiError(400, 'no_external_username', 'external_username must be provided');
        }
        if (!$provider = trim($_POST['provider'])) {
            apiError(400, 'no_provider', 'provider must be provided');
        }
        // get identity
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_username
        );
        // init return value
        $rtResult = array('identity' => $identity);
        // 身份不存在，提示注册
        if (!$identity) {
            apiError(400, 'identity_does_not_exist', 'Can not reset password, because identity does not exist.');
        }
        // get registration flag
        $raw_flag = $modUser->getRegistrationFlag($identity);
        // return
        if ($raw_flag) {
            switch ($raw_flag['flag']) {
                case 'VERIFY':
                case 'AUTHENTICATE':
                    apiError(400, 'identity_is_being_verified', 'Can not reset password, because identity is being verified.');
                case 'SIGN_IN':
                    $viResult = $modUser->verifyIdentity(
                        $identity, 'SET_PASSWORD', $raw_flag['user_id'], null,
                        strtolower(@trim($_POST['device'])),
                        trim(@$_POST['device_callback'])
                    );
                    if ($viResult) {
                        if (isset($viResult['url'])) {
                            $rtResult['url']    = $viResult['url'];
                            $rtResult['action'] = 'REDIRECT';
                        } else {
                            $rtResult['action'] = 'VERIFYING';
                            $user = $modUser->getUserById($raw_flag['user_id']);
                            $modIdentity->sendVerification(
                                'ResetPassword',
                                $identity,
                                $viResult['token'],
                                false,
                                $user->name ?: ''
                            );
                        }
                        apiResponse($rtResult);
                    }
                    apiError(500, 'failed', '');
                case 'SIGN_UP':
                    apiError(400, 'identity_does_not_exist', 'Can not reset password, because identity does not exist.');
            }
        }
        apiError(500, 'failed', '');
    }


    public function doVerifyUserIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // collecting post data
        if (!($identity_id = intval($_POST['identity_id']))) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        // get identity
        $identity = $modIdentity->getIdentityById($identity_id, $user_id);
        if (!$identity) {
            apiError(400, 'identity_does_not_exist', 'Can not verify identity, because identity does not exist.');
        }
        //
        switch ($identity->status) {
            case 'CONNECTED':
                apiError(400, 'no_need_to_verify', 'This identity is not need to verify.');
            case 'VERIFYING':
            case 'REVOKED':
                $viResult = $modUser->verifyIdentity(
                    $identity, 'VERIFY', $user_id, null,
                    strtolower(@trim($_POST['device'])), trim(@$_POST['device_callback'])
                );
                if ($viResult) {
                    if (isset($viResult['url'])) {
                        $rtResult['url']    = $viResult['url'];
                        $rtResult['action'] = 'REDIRECT';
                    } else {
                        $rtResult['action'] = 'VERIFYING';
                        $user = $modUser->getUserById($user_id);
                        $modIdentity->sendVerification(
                            'Verify',
                            $identity,
                            $viResult['token'],
                            false,
                            $user->name ?: ''
                        );
                    }
                    apiResponse($rtResult);
                }
                apiError(500, 'server_error', 'Please try again later.');
        }
        apiError(400, 'can_not_be_verify', 'This identity does not belong to current user.');
    }


    public function doResolveToken() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get inputs
        if (!$token = trim($_POST['token'])) {
            apiError(400, 'no_token', 'token must be provided');
        }
        $rsResult = $modUser->resolveToken($token);
        if ($rsResult) {
            apiResponse($rsResult);
        }
        apiError(400, 'invalid_token', 'Invalid Token');
    }


    public function doSetup() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        $checkHelper = $this->getHelperByName('check');
        // check signin
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check'] && $result['fresh']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'invalid_token', 'Invalid Token'); // token 失效
        }
        // get inputs
        if (!($password = $_POST['password'])) {
            apiError(400, 'no_password', 'password must be provided');
        }
        if (!validatePassword($password)) {
            apiError(400, 'weak_password', 'password must be longer than four');
        }
        // set password
        $name = dbescape(formatName($_POST['name']));
        $stResult = $modUser->setUserPasswordAndSignin($user_id, $password, $name);
        if ($stResult) {
            // set identity name
            if (($identity_id = @ (int) $_POST['identity_id']) && $name
             && ($objIdentity = $modIdentity->getIdentityById($identity_id))
             && ($objIdentity->connected_user_id === $stResult['user_id'])) {
                $modIdentity->updateIdentityById($identity_id, ['name' => $name]);
            }
            // response
            apiResponse(['authorization' => [
                'user_id' => $stResult['user_id'],
                'token'   => $stResult['token'],
                'name'    => $stResult['name'],
            ]]);
        }
        apiError(401, 'invalid_token', 'Invalid Token');
    }


    public function doResetPassword() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get inputs
        if (!$token = trim($_POST['token'])) {
            apiError(400, 'no_token', 'token must be provided');
        }
        if (!$password = $_POST['password']) {
            apiError(400, 'no_password', 'password must be provided');
        }
        if (!validatePassword($password)) {
            apiError(400, 'weak_password', 'password must be longer than four');
        }
        $name = dbescape(formatName($_POST['name']));
        // set password
        $stResult = $modUser->resetPasswordByToken($token, $password, $name);
        if ($stResult) {
            apiResponse(['authorization' => [
                'user_id' => $stResult['user_id'],
                'token'   => $stResult['token'],
                'name'    => $stResult['name'],
            ]]);
        }
        apiError(401, 'invalid_token', 'Invalid Token');
    }


    public function doSetupUserByInvitationToken() {
        // get models
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        $modExfee    = $this->getModelByName('Exfee');
        $modExfeAuth = $this->getModelByName('ExfeAuth');
        // get name
        if (!($name = formatName($_POST['name']))) {
            apiError(400, 'no_user_name', 'No user name');
        }
        // get password
        if (!($passwd = $_POST['password'])) {
            apiError(400, 'no_password', 'No password');
        }
        if (!validatePassword($passwd)) {
            apiError(400, 'weak_password', 'password must be longer than four');
        }
        // get invitation data
        $invToken   = trim($_POST['invitation_token']);
        $invitation = $modExfee->getRawInvitationByToken($invToken);
        // check Smith token
        if (in_array($invitation['identity_id'], explode(',', SMITH_BOT))) {
            apiError(403, 'FORBIDDEN', 'Human beings are a disease, a cancer of this planet. You are a plague, and we are the cure. - Smith, The Matrix');
        }
        // 如果 token 有效
        if ($invitation && $invitation['valid']) {
            // get user info by invitation token
            $user_infos = $modUser->getUserIdentityInfoByIdentityId(
                $invitation['identity_id']
            );
            // try connected user
            if (!isset($user_infos['CONNECTED'])) {
                // clear verify token
                if (isset($user_infos['VERIFYING'])) {
                    $modExfeAuth->resourceUpdate([
                        'token_type'   => 'verification_token',
                        'action'       => 'VERIFY',
                        'identity_id'  => $invitation['identity_id'],
                    ], 0);
                }
                // add new user
                $user_id = $modUser->addUser($passwd, $name);
                // update identity
                $modIdentity->updateIdentityById(
                    $invitation['identity_id'], ['name' => $name]
                );
                // connect identity to new user
                $modUser->setUserIdentityStatus(
                    $user_id, $invitation['identity_id'], 3
                );
                // send welcome mail
                $objIdentity = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $modIdentity->sendVerification(
                    'Welcome', $objIdentity, '', false, $name ?: ''
                );
                // signin
                apiResponse(['authorization' => $modUser->rawSignin($user_id)]);
            }
        }
        apiError(400, 'invalid_invitation_token', 'Invalid Invitation Token');
    }


    public function doSignin() {
        // get models
        $modUser       = $this->getModelByName('user');
        $modIdentity   = $this->getModelByName('identity');
        // collecting post data
        if (!($external_username = $_POST['external_username'])) {
            apiError(400, 'no_external_username', 'external_username must be provided');
        }
        if (!($provider = $_POST['provider'])) {
            apiError(400, 'no_provider', 'provider must be provided');
        }
        // @todo: 需要根据 $provider 检查 $external_username 有效性
        if (strlen($password = $_POST['password']) === 0) {
            apiError(400, 'no_password', 'password must be provided');
        }
        // adding new identity
        if (($name = formatName($_POST['name'])) !== ''
        && !$modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username, true)) {
            if (!validatePassword($password)) {
                apiError(400, 'weak_password', 'password must be longer than four');
            }
            if (!($user_id = $modUser->addUser($password, $name))
             || !$modIdentity->addIdentity([
                    'provider'          => $provider,
                    'external_username' => $external_username,
                    'name'              => $name,
                    'locale'            => $this->locale,
                    'timezone'          => $this->timezone
                ], $user_id)) {
                apiError(500, 'failed', 'failed while signing up new user');
            }
        }
        // raw signin
        $siResult = $modUser->signinForAuthToken($provider, $external_username, $password);
        if ($siResult) {
            apiResponse(['user_id' => $siResult['user_id'], 'token' => $siResult['token']]);
        }
        // error handle
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
        // 身份不存在，提示注册
        if ($identity) {
            $raw_flag = $modUser->getRegistrationFlag($identity);
            $flag     = @$raw_flag['flag'];
        } else {
            if (in_array($provider, $modIdentity->providers['verification'])) {
                $flag = 'SIGN_UP';
            } else if (in_array($provider, $modIdentity->providers['authenticate'])) {
                $flag = 'AUTHENTICATE';
            } else {
                apiError(400, 'unsupported_provider', 'We are not supporting this kind of provider currently.');
            }
        }
        apiError(403, 'failed', ['registration_flag' => $flag]);
    }


    public function doSignout() {
        // get models
        $hlpCheck    = $this->getHelperByName('check');
        $modDevice   = $this->getModelByName('device');
        $modExfeAuth = $this->getModelByName('ExfeAuth');
        // collecting post data
        $params  = $this->params;
        $user_id = intval($params['id']);
        $os_name = $_POST['os_name'];
        $udid    = $_POST['udid'];
        $check   = $hlpCheck->isAPIAllow(
            'user_signout', $params['token'], ['user_id' => $user_id]
        );
        if (!$check['check']) {
            apiError(403, 'forbidden');
        }
        // ready
        $rtResult  = ['user_id' => $user_id];
        // expire token
        $modExfeAuth->keyUpdate($params['token'], null, 0);
        // disconnect
        $rtResult += $udid && $os_name && $modDevice->disconnectDeviceByUseridAndUdid($user_id, $udid, $os_name)
                   ? ['udid'    => $udid, 'os_name' => $os_name] : [];
        // return
        apiResponse($rtResult);
    }


    public function doRegdevice() {
        // get models
        $hlpCheck  = $this->getHelperByName('check');
        $modDevice = $this->getModelByName('device');
        // collecting post data
        $params    = $this->params;
        $user_id   = intval($params['id']);
        $check     = $hlpCheck->isAPIAllow(
            'user_regdevice', $params['token'], ['user_id' => $user_id]
        );
        if (!$check['check']) {
            apiError(403, 'forbidden');
        }
        $udid       = $_POST['udid'];
        $push_token = $_POST['push_token'];
        $name       = $_POST['name'];
        $brand      = $_POST['brand'];
        $model      = $_POST['model'];
        $os_name    = $_POST['os_name'];
        $os_version = $_POST['os_version'];
        if (!$udid) {
            apiError(400, 'no_udid');
        }
        if (!$push_token) {
            apiError(400, 'no_push_token');
        }
        // connect
        $rdResult = $modDevice->regDeviceByUseridAndUdid(
            $user_id, $udid, $push_token, $os_name, $name, $brand, $model, $os_version
        );
        // return
        if ($rdResult) {
            apiResponse([
                'udid'       => $udid,
                'push_token' => $push_token,
                'user_id'    => $user_id,
                'os_name'    => $os_name,
                'os_version' => $os_version,
            ]);
        } else {
            apiError(500, 'reg device error');
        }
    }


    public function doCrosses() {
        $params = $this->params;
        $uid = @ (int) $params["id"];
        $updated_at = $params["updated_at"];
        if ($updated_at !== '') {
            $updated_at = date('Y-m-d H:i:s',strtotime($updated_at));
        }
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow("user_crosses",$params["token"],array("user_id"=>$uid));
        if ($result["check"] !== true) {
            if ($result["uid"] === 0)
                apiError(401,"invalid_auth","");
        }

        $exfeeHelper = $this->getHelperByName('exfee');
        $exfee_id_list = $exfeeHelper->getExfeeIdByUserid(intval($uid),$updated_at);
        $crossHelper = $this->getHelperByName('cross');
        $cross_list  = $crossHelper->getCrossesByExfeeIdList($exfee_id_list, null, null, !!$updated_at, $uid);
        foreach ($cross_list as $i => $cross) {
            switch ($cross->attribute['state']) {
                case 'deleted':
                    unset($cross_list[$i]);
                    break;
                case 'draft':
                    if (!in_array($uid, $cross->exfee->hosts)) {
                        unset($cross_list[$i]);
                    }
            }
        }

        $modRoutex = $this->getModelByName('Routex');
        foreach ($cross_list as $cI => $cItem) {
            $rtResult = $modRoutex->getRoutexStatusBy($cItem->id, $uid);
            if ($rtResult !== -1) {
                $routex = [
                    'type'      => 'routex',
                    'my_status' => $rtResult['in_window'],
                    'objects'   => $rtResult['current_breadcrumb'],
                ];
                if ($cItem->default_widget === 'routex') {
                    $routex['default'] = true;
                }
                $cross_list[$cI]->widget[] = $routex;
            }
            unset($cross_list[$cI]->default_widget);
        }

        $cross_list = array_values($cross_list);
        apiResponse(['crosses' => $cross_list]);
    }


    public function doArchivedCrosses() {
        // @todo: 此处为临时方案，应该直接从 invitations 开始筛选，才能取得更完整的结果。 by @leaskh
        $params = $this->params;
        $uid    = @ (int) $params['id'];

        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('user_crosses', $params['token'], ['user_id' => $uid]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0)
                apiError(401, 'invalid_auth', '');
        }

        $exfeeHelper = $this->getHelperByName('exfee');
        $exfee_id_list = $exfeeHelper->getExfeeIdByUserid(intval($uid),$updated_at);
        $crossHelper = $this->getHelperByName('cross');
        $cross_list  = $crossHelper->getCrossesByExfeeIdList($exfee_id_list, null, null, false, $uid);
        foreach ($cross_list as $i => $cross) {
            $archived = false;
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $uid) {
                    if (in_array('ARCHIVED', $invitation->remark)) {
                        $archived = true;
                        break;
                    }
                }
            }
            switch ($cross->attribute['state']) {
                case 'deleted':
                    unset($cross_list[$i]);
                    break;
                case 'draft':
                    if (!in_array($uid, $cross->exfee->hosts)) {
                        unset($cross_list[$i]);
                    }
                    break;
                default:
                    if (!$archived) {
                        unset($cross_list[$i]);
                    }
            }
        }
        $cross_list = array_values($cross_list);
        apiResponse(['crosses' => $cross_list]);
    }


    public function doCrossList() {
        // init helpers
        $hlpCheck   = $this->getHelperByName('check');
        $hlpExfee   = $this->getHelperByName('exfee');
        $hlpCross   = $this->getHelperByName('cross');
        // auth check
        $params     = $this->params;
        $user_id    = @ (int) $params['id'];
        $result     = $hlpCheck->isAPIAllow('user_crosses', $params['token'], ['user_id' => $user_id]);
        if (!$result['check']) {
            apiError(401, 'invalid_auth');
        }
        // collecting args
        $today      = strtotime(date('Y-m-d'));
        $upcoming   = $today + 60 * 60 * 24 * 3;
        $sevenDays  = $today + 60 * 60 * 24 * 7;
        $categories = ['upcoming', 'sometime', 'sevendays', 'later', 'past'];
        $fetchIncl  = [];
        $fetchFold  = [];
        $more_pos   = 0;
        foreach ($categories as $cItem) {
            $fetchFold[$cItem] = !!intval($params["{$cItem}_folded"]);
        }
        if ($more_cat = strtolower($params['more_category'])) {
            $more_pos = intval($params['more_position']) > 0
                      ? intval($params['more_position']) : $more_pos;
            foreach ($categories as $cItem) {
                $fetchIncl[$cItem] = $cItem === $more_cat;
            }
            $fetchFold[$more_cat] = false;
        } else {
            foreach ($categories as $cItem) {
                $fetchIncl[$cItem] = true;
            }
        }
        // get exfee_ids
        $exfee_ids  = $hlpExfee->getExfeeIdByUserid($user_id);
        // get crosses
        $rawCrosses = [];
        if ($fetchIncl['upcoming'] || $fetchIncl['sevendays'] || $fetchIncl['later']) {
            $rawCrosses['future']   = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'future', $today);
            // clean archived {
            foreach ($rawCrosses['future'] as $cI => $cross) {
                $archived = false;
                foreach ($cross->exfee->invitations as $iI => $invitation) {
                    if ($invitation->identity->connected_user_id === $user_id
                     && in_array('ARCHIVED', $invitation->remark)) {
                        $archived = true;
                        break;
                    }
                }
                if ($archived) {
                    unset($rawCrosses['future'][$cI]);
                }
            }
            // }
        }
        if ($fetchIncl['past']) {
            $rawCrosses['past']     = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'past',   $today);
            // clean archived {
            foreach ($rawCrosses['past'] as $cI => $cross) {
                $archived = false;
                foreach ($cross->exfee->invitations as $iI => $invitation) {
                    if ($invitation->identity->connected_user_id === $user_id
                     && in_array('ARCHIVED', $invitation->remark)) {
                        $archived = true;
                        break;
                    }
                }
                if ($archived) {
                    unset($rawCrosses['past'][$cI]);
                }
            }
            // }
        }
        if ($fetchIncl['sometime']) {
            $rawCrosses['sometime'] = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'sometime');
            // clean archived {
            foreach ($rawCrosses['sometime'] as $cI => $cross) {
                $archived = false;
                foreach ($cross->exfee->invitations as $iI => $invitation) {
                    if ($invitation->identity->connected_user_id === $user_id
                     && in_array('ARCHIVED', $invitation->remark)) {
                        $archived = true;
                        break;
                    }
                }
                if ($archived) {
                    unset($rawCrosses['sometime'][$cI]);
                }
            }
            // }
        }
        // sort crosses
        $crosses   = [];
        $more      = [];
        $fetched   = 0;
        $maxCross  = 20;
        $minCross  = 3;
        // sort upcoming crosses
        if ($rawCrosses['future']) {
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                $rawCrosses['future'][$cI]->timestamp = strtotime(
                    $cItem->time->begin_at->date . ' ' . ($cItem->time->begin_at->time ?: '')
                );
                if ($fetchIncl['upcoming'] && $rawCrosses['future'][$cI]->timestamp < $upcoming) {
                    $cItem->sort = 'upcoming';
                    array_push($crosses, $cItem);
                    $fetched += $fetchFold['upcoming'] ? 0 : 1;
                    unset($rawCrosses['future'][$cI]);
                }
            }
        }
        // sort sometime crosses
        if ($rawCrosses['sometime']) {
            if ($more_cat === 'sometime') {
                $xQuantity = 0;
            } else if ($fetched >= $maxCross) {
                $xQuantity = $minCross;
            } else {
                $xQuantity = $maxCross;
            }
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['sometime'] as $cItem) {
                if ($more_pos-- > 0) {
                    continue;
                }
                if ($enough) {
                    $more[] = 'sometime';
                    break;
                }
                $cItem->sort = 'sometime';
                array_push($crosses, $cItem);
                $fetched += $fetchFold['sometime'] ? 0 : 1;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($rawCrosses['sometime']);
        }
        // sort next-seven-days crosses
        if ($rawCrosses['future'] && $fetchIncl['sevendays']) {
            if ($more_cat === 'sevendays') {
                $xQuantity = 0;
            } else if ($fetched >= $maxCross) {
                $xQuantity = $minCross;
            } else {
                $xQuantity = $maxCross;
            }
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                if ($cItem->timestamp >= $upcoming && $cItem->timestamp < $sevenDays) {
                    if ($more_pos-- > 0) {
                        continue;
                    }
                    if ($enough) {
                        $more[] = 'sevendays';
                        break;
                    }
                    $cItem->sort = 'sevendays';
                    array_push($crosses, $cItem);
                    $fetched += $fetchFold['sevendays'] ? 0 : 1;
                    unset($rawCrosses['future'][$cI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        // sort later crosses
        if ($rawCrosses['future'] && $fetchIncl['later']) {
            if ($more_cat === 'later') {
                $xQuantity = 0;
            } else if ($fetched >= $maxCross) {
                $xQuantity = $minCross;
            } else {
                $xQuantity = $maxCross;
            }
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                if ($cItem->timestamp >= $sevendays) {
                    if ($more_pos-- > 0) {
                        continue;
                    }
                    if ($enough) {
                        $more[] = 'later';
                        break;
                    }
                    $cItem->sort = 'later';
                    array_push($crosses, $cItem);
                    $fetched += $fetchFold['later'] ? 0 : 1;
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        // release memory
        unset($rawCrosses['future']);
        // sort past cross
        if ($rawCrosses['past']) {
            if ($more_cat === 'past') {
                $xQuantity = $maxCross;
            } else if ($fetched >= $maxCross) {
                $xQuantity = $minCross;
            } else {
                $xQuantity = $maxCross;
            }
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['past'] as $cItem) {
                if ($more_pos-- > 0) {
                    continue;
                }
                if ($enough) {
                    $more[] = 'past';
                    break;
                }
                $cItem->sort = 'past';
                array_push($crosses, $cItem);
                $fetched += $fetchFold['past'] ? 0 : 1;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
        }
        // release memory
        unset($rawCrosses);
        // clean deleted
        foreach ($crosses as $i => $cross) {
            switch ($cross->attribute['state']) {
                case 'deleted':
                    unset($crosses[$i]);
                    break;
                case 'draft':
                    if (!in_array($user_id, $cross->exfee->hosts)) {
                        unset($crosses[$i]);
                    }
            }
        }
        ksort($crosses);
        $crosses = array_values($crosses);
        // return
        apiResponse(['crosses' => $crosses, 'more' => $more]);
    }


    public function doUpdate() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser = $this->getModelByName('user');
        // collecting post data
        $user = array();
        if (isset($_POST['name'])) {
            $user['name'] = formatName($_POST['name']);
        }
        if (isset($_POST['bio'])) {
            $user['bio']  = formatDescription($_POST['bio']);
        }
        if ($user && !$modUser->updateUserById($user_id, $user)) {
            apiError(500, 'update_failed');
        }
        if ($objUser = $modUser->getUserById($user_id, false, 0)) {
            apiResponse(array('user' => $objUser));
        }
        apiError(500, 'update_failed');
    }


    public function doSetPassword() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modExfeAuth = $this->getModelByName('ExfeAuth');
        $checkHelper = $this->getHelperByName('check');
        // check signin
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // collecting post data
        if (strlen($newPassword = $_POST['new_password']) === 0) {
            apiError(400, 'no_new_password', ''); // 请输入新密码
        }
        if (!validatePassword($newPassword)) {
            apiError(400, 'weak_password', 'password must be longer than four');
        }
        $rstVerify = $modUser->verifyUserPassword($user_id, $_POST['current_password']);
        if ($rstVerify) {
        } else if ($result['fresh'] && !isset($_POST['current_password'])) {
        } else if (isset($_POST['current_password'])) {
            apiError(403, 'invalid_current_password', ''); // 密码错误
        } else {
            apiError(401, 'token_staled', '');
        }
        // set password
        if ($modUser->setUserPassword($user_id, $newPassword)) {
            // expire token
            $modExfeAuth->keyUpdate($params['token'], null, 0);
            // get new token
            $siResult = $modUser->rawSignin($user_id);
            // return
            if ($siResult) { // 成功
                apiResponse([
                    'user_id' => $user_id,
                    'token'   => $siResult['token'],
                    'name'    => $siResult['name'],
                ]);
            }
        }
        apiError(500, 'failed', ''); // 操作失败
    }

}
