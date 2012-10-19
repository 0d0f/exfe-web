<?php

class UsersActions extends ActionController {

    public function doIndex() {
        $modUser     = $this->getModelByName('User');
        $checkHelper = $this->getHelperByName('check');
        $params      = $this->params;
        if (!$params['id']) {
            apiError(400, 'no_user_id', 'user_id must be provided');
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
        $modIdentity = $this->getModelByName('identity');
        // collecting post data
        if (!($external_username = trim($_POST['external_username']))) {
            apiError(400, 'no_external_username', '');
        }
        if (!($provider = trim($_POST['provider']))) {
            apiError(400, 'no_provider', '');
        }
        // adding
        if (($adResult    = $modIdentity->addIdentity(['provider' => $provider, 'external_username' => $external_username], $user_id, 2, true))
         && ($objIdentity = $modIdentity->getIdentityById(@$adResult['identity_id'], $user_id))) {
            $rtResult     = ['identity' => $objIdentity, 'action' => 'VERIFYING'];
            if (isset($adResult['verification']['url'])) {
                $rtResult['url'] = $adResult['verification']['url'];
            }
            apiResponse($rtResult);
        } else {
            apiError(400, 'failed', '');
        }
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
            if (!($objBsToken = $modExfeAuth->getToken($strBsToken))
             || $objBsToken['is_expire']) {
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
                apiError(401, 'authenticate_timeout', 'reauthenticate is needed');
            }
            $fromUserId = $objBsToken['data']['user_id'];
        // 提交一个 Token 的时候，需要保证该 Token 是新鲜的
        // get verify token
        } else if (($verifyToken = $modExfeAuth->getToken(@$params['token']))
                && isset($verifyToken['data']['merger_info'])
                && time() - $verifyToken['data']['merger_info']['updated_time'] < 60 * 15) { // in 15 mins
            $user_id    = $verifyToken['data']['user_id'];
            $fromUserId = $verifyToken['data']['merger_info']['mergeable_user']['id'];
        } else {
            apiError(401, 'authenticate_failed', ''); // 需要重新鉴权
        }
        // get browsing identity ids
        $bsIdentityIds = @json_decode($_POST['identity_ids']);
        if (!$bsIdentityIds || !is_array($bsIdentityIds)) {
            apiError(400, 'no_identity_ids', '');
        }
        // get from user
        $fromUser = $modUser->getUserById($fromUserId, false, 0);
        if (!$fromUser) {
            apiError(400, 'error_user_status', '');
        }
        // check user identity status
        foreach ($fromUser->identities as $iI => $iItem) {
            if ($iItem->status !== 'CONNECTED'
             && $iItem->status !== 'REVOKED') {
                unset($fromUser->identities[$iI]);
            }
        }
        // merge
        $mgResult = [];
        $mgStatus = false;
        foreach ($bsIdentityIds as $bsIdentityId) {
            if (!($bsIdentityId = (int) $bsIdentityId)) {
                continue;
            }
            // merge directly
            foreach ($fromUser->identities as $iI => $iItem) {
                if ($iItem->id === $bsIdentityId) {
                    $tarStatus = 0;
                    switch ($iItem->status) {
                        case 'CONNECTED':
                            $tarStatus = 3;
                            break;
                        case 'REVOKED':
                            $tarStatus = 4;
                    }
                    if ($modUser->setUserIdentityStatus(
                        $user_id, $bsIdentityId, $tarStatus
                    )) {
                        $mgResult[$bsIdentityId] = $mgStatus = true;
                    } else {
                        $mgResult[$bsIdentityId] = false;
                    }
                    unset($fromUser->identities[$iI]);
                }
            }
        }
        // get other mergeable user identity
        $fromUser->identities = array_merge($fromUser->identities);
        // return
        if ($mgStatus) {
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
            if (!$result['fresh']) {
                apiError(401, 'authenticate_timeout', ''); // 需要重新鉴权
            }
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
            switch ($provider) {
                case 'email':
                    apiResponse(['registration_flag' => 'SIGN_UP']);
                    break;
                case 'twitter':
                    apiResponse(['registration_flag' => 'AUTHENTICATE']);
                    break;
                default:
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
                        $raw_flag['reason'] === 'NO_PASSWORD' ? 'SET_PASSWORD' : 'VERIFY',
                        $user_id, $args
                    );
                    if ($viResult) {
                        $hlpGobus = $this->getHelperByName('gobus');
                        $user     = $modUser->getUserById($user_id);
                        $hlpGobus->send('user', 'Verify', [
                            'to_identity' => $identity,
                            'user_name'   => $user->name ?: '',
                            'action'      => 'CONFIRM_IDENTITY',
                            'token'       => $viResult['token'],
                        ]);
                        $rtResult['action'] = 'VERIFYING';
                        apiResponse($rtResult);
                    }
                    break;
                case 'AUTHENTICATE':
                    $viResult = $modUser->verifyIdentity(
                        $identity, 'VERIFY', $user_id, $args
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
                        $identity, 'SET_PASSWORD', $raw_flag['user_id']
                    );
                    if ($viResult) {
                        if (isset($viResult['url'])) {
                            $rtResult['url']    = $viResult['url'];
                            $rtResult['action'] = 'REDIRECT';
                        } else {
                            $rtResult['action'] = 'VERIFYING';
                            // call Gobus {
                            $user     = $modUser->getUserById($raw_flag['user_id']);
                            $hlpGobus = $this->getHelperByName('gobus');
                            $msgs     = [
                                'to_identity' => $identity,
                                'user_name'   => $user->name ?: '',
                                'action'      => 'SET_PASSWORD',
                                'token'       => $viResult['token'],
                            ];
                            $hlpGobus->send('user', 'Verify', $msgs);
                            if (DEBUG) {
                                error_log(json_encode($msgs));
                            }
                            // }
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
                    $identity, 'VERIFY', $user_id
                );
                if ($viResult) {
                    if (isset($viResult['url'])) {
                        $rtResult['url']    = $viResult['url'];
                        $rtResult['action'] = 'REDIRECT';
                    } else {
                        $rtResult['action'] = 'VERIFYING';
                        // call Gobus {
                        $user     = $modUser->getUserById($user_id);
                        $hlpGobus = $this->getHelperByName('gobus');
                        $hlpGobus->send('user', 'Verify', [
                            'to_identity' => $identity,
                            'user_name'   => $user->name ?: '',
                            'action'      => 'CONFIRM_IDENTITY',
                            'token'       => $viResult['token'],
                        ]);
                        // }
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
        $name = mysql_real_escape_string(formatName($_POST['name']));
        // set password
        $stResult = $modUser->resetPasswordByToken($token, $password, $name);
        if ($stResult) {
            apiResponse(['authorization' => [
                'user_id' => $stResult['user_id'],
                'token'   => $stResult['token'],
            ]]);
        }
        apiError(400, 'invalid_token', 'Invalid Token');
    }


    public function doSetupUserByInvitationToken() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modExfee    = $this->getModelByName('exfee');
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
                    $modExfeAuth->expireAllTokens([
                        'token_type'   => 'verification_token',
                        'action'       => 'VERIFY',
                        'identity_id'  => $invitation['identity_id'],
                    ]);
                }
                // add new user
                $user_id = $modUser->addUser($passwd, $name);
                // connect identity to new user
                $modUser->setUserIdentityStatus(
                    $user_id, $invitation['identity_id'], 3
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
            apiError(403, 'no_external_username', 'external_username must be provided');
        }
        if (!($provider = $_POST['provider'])) {
            apiError(403, 'no_provider', 'provider must be provided');
        }
        // @todo: 需要根据 $provider 检查 $external_username 有效性
        if (!($password = $_POST['password'])) {
            apiError(403, 'no_password', 'password must be provided');
        }
        // $autoSignin = intval($_POST['auto_signin']) === 1; // @todo: 记住密码功能
        // adding new identity
        if (($name = formatName($_POST['name'])) !== ''
        && !$modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username, false, true)) {
            if (!validatePassword($password)) {
                apiError(400, 'weak_password', 'password must be longer than four');
            }
            if (!($user_id = $modUser->addUser($password, $name))
             || !$modIdentity->addIdentity(['provider' => $provider, 'external_username' => $external_username, 'name' => $name], $user_id)) {
                apiError(403, 'failed', 'failed while signing up new user');
            }
        }
        // raw signin
        $siResult = $modUser->signinForAuthToken($provider, $external_username, $password);
        if ($siResult) {
            apiResponse(['user_id' => $siResult['user_id'], 'token' => $siResult['token']]);
        }
        apiError(403, 'failed', '');
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
        // expireToken
        $modExfeAuth->expireToken($params['token']);
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
        $uid=$params["id"];
        $updated_at=$params["updated_at"];
        if($updated_at!='')
            $updated_at=date('Y-m-d H:i:s',strtotime($updated_at));

        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow("user_crosses",$params["token"],array("user_id"=>$uid));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
        }

        $exfeeHelper= $this->getHelperByName('exfee');
        $exfee_id_list=$exfeeHelper->getExfeeIdByUserid(intval($uid),$updated_at);
        $crossHelper= $this->getHelperByName('cross');
        if($updated_at!='')
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,true,$uid);
        else
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,false,$uid);
        apiResponse(array("crosses"=>$cross_list));
    }


    public function doCrossList() {
        // init helpers
        $hlpCheck   = $this->getHelperByName('check');
        $hlpExfee   = $this->getHelperByName('exfee');
        $hlpCross   = $this->getHelperByName('cross');
        // auth check
        $params     = $this->params;
        $user_id    = (int)$params['id'];
        $result     = $hlpCheck->isAPIAllow('user_crosses', $params['token'], array('user_id' => $user_id));
        if (!$result['check']) {
            apiError(401, 'invalid_auth');
        }
        // collecting args
        $today      = strtotime(date('Y-m-d'));
        $upcoming   = $today + 60 * 60 * 24 * 3;
        $sevenDays  = $today + 60 * 60 * 24 * 7;
        $categories = array('upcoming', 'sometime', 'sevendays', 'later', 'past');
        $fetchIncl  = array();
        $fetchFold  = array();
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
        $rawCrosses = array();
        if ($fetchIncl['upcoming'] || $fetchIncl['sevendays'] || $fetchIncl['later']) {
            $rawCrosses['future']   = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'future', $today);
        }
        if ($fetchIncl['past']) {
            $rawCrosses['past']     = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'past',   $today);
        }
        if ($fetchIncl['sometime']) {
            $rawCrosses['sometime'] = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'sometime');
        }
        // sort crosses
        $crosses   = array();
        $more      = array();
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
        // return
        apiResponse(array('crosses' => $crosses, 'more' => $more));
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
        if (!$modUser->verifyUserPassword($user_id, $_POST['current_password'], true)) {
            apiError(403, 'invalid_current_password', ''); // 密码错误
        }
        if (strlen($newPassword = $_POST['new_password']) === 0) {
            apiError(400, 'no_new_password', ''); // 请输入新密码
        }
        if (!validatePassword($newPassword)) {
            apiError(400, 'weak_password', 'password must be longer than four');
        }
        // set password
        if ($modUser->setUserPassword($user_id, $newPassword)) {
            // expire token
            $modExfeAuth->expireToken($params['token']);
            // get new token
            $siResult = $modUser->rawSignin($user_id);
            // return
            if ($siResult) { // 成功
                apiResponse(['user_id' => $user_id, 'token' => $siResult['token']]);
            }
        }
        apiError(500, 'failed', ''); // 操作失败
    }

}
