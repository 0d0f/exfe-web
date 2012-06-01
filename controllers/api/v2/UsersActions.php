<?php

class UsersActions extends ActionController {

    public function doIndex() {
        $modUser = $this->getModelByName('User', 'v2');
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params  = $this->params;
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
            apiResponse(array('user' => $objUser));
        }
        apiError(404, 'user_not_found', 'user not found');
    }


    public function doAddIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser = $this->getModelByName('User', 'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($external_id = $_POST['external_id'])) {
            apiError(400, 'no_external_id', '');
        }
        if (!($provider = $_POST['provider'])) {
            apiError(400, 'no_provider', '');
        }
        if (!($password = $_POST['password'])) {
            apiError(401, 'no_password', ''); // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $password)) {
            apiError(403, 'invalid_password', ''); // 密码错误
        }
        if (($identity_id = $modIdentity->addIdentity($provider, $external_id, array(), $user_id))
         && ($objIdentity = $modIdentity->getIdentityById($identity_id, $user_id))) {
            apiResponse(array('identity' => $objIdentity));
        } else {
            apiError(400, 'failed', '');
        }
    }


    public function doDeleteIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($identity_id = intval($_POST['identity_id']))) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        if (!($password = $_POST['password'])) {
            apiError(403, 'password', ''); // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $password)) {
            apiError(403, 'invalid_password', ''); // 密码错误
        }
        switch ($modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) {
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


    public function doSetDefaultIdentity() {
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($identity_id = intval($_POST['identity_id']))) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        if (!($password = $_POST['password'])) {
            apiError(403, 'no_current_password', ''); // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $password)) {
            apiError(403, 'invalid_password', ''); // 密码错误
        }
        switch ($modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) {
            case 'CONNECTED':
                if ($modIdentity->setIdentityAsDefaultIdentityOfUser($identity_id, $user_id)) {
                    apiResponse(array('user_id' => $user_id, 'identity_id' => $identity_id));
                }
                break;
            default:
                apiError(400, 'invalid_relation', ''); // 用户和身份关系错误
        }
        apiError(500, 'failed', '');
    }


    public function doGetRegistrationFlag() {
        // get models
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // get inputs
        $params = $this->params;
        if (!$external_id = trim($params['external_id'])) {
            apiError(400, 'no_external_id', 'external_id must be provided');
        }
        if (!$provider = trim($params['provider'])) {
            apiError(400, 'no_provider', 'provider must be provided');
        }
        // get identity
        $identity = $modIdentity->getIdentityByProviderExternalId($provider, $external_id);
        // 身份不存在，提示注册
        if (!$identity) {
            apiResponse(array('registration_flag' => 'SIGN_UP'));
        }
        // get user info
        $user_info = $modUser->getUserIdentityInfoByIdentityId($identity->id);
        // 只有身份没有用户，需要身份
        if (!$user_info) {
            apiResponse(array('registration_flag' => 'VERIFY'));
        }
        // get flag
        switch ($user_info['status']) {
            case 'CONNECTED':
                if ($user_info['password']) {
                    apiResponse(array(
                        'registration_flag' => 'SIGN_IN',
                        'identity'          => $identity,
                    ));
                }
                apiResponse(array(
                    'registration_flag' => 'RESET_PASSWORD',
                    'identity'          => $identity,
                ));
                break;
            case 'RELATED':
                apiResponse(array('registration_flag' => 'SIGN_UP'));
                break;
            case 'VERIFYING':
            case 'REVOKED': // @todo: 存在疑问
                if ($user_info['password'] && $user_info['id_quantity'] === 1) {
                    apiResponse(array(
                        'registration_flag' => 'SIGN_IN',
                        'identity'          => $identity,
                    ));
                }
                apiResponse(array(
                    'registration_flag' => 'VERIFY',
                    'identity'          => $identity,
                ));
        }
        apiError(500, 'failed', '');
    }


    public function doVerifyIdentity() {

    }


    public function doCheckAuthorization() {
        // get models
        $checkHelper   = $this->getHelperByName('check', 'v2');
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // get inputs
        $arrTokens     = trim($_POST['tokens']) ? json_decode($_POST['tokens']) : array();
        $objStatuses   = array();
        // get status
        foreach ($arrTokens as $token) {
            $result = $checkHelper->isAPIAllow('user_edit', $token);
            $objStatuses[$token]
          = $result['check']
          ? $modUser->getUserIdentityInfoByUserId($result['uid'])
          : null;
        }
        apiResponse(array('statuses' => $objStatuses));
    }


    public function doSignin() {
        // get models
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!$external_id = $_POST['external_id']) {
            apiError(403, 'no_external_id', 'external_id must be provided');
        }
        if (!$provider = $_POST['provider']) {
            apiError(403, 'no_provider', 'provider must be provided');
        }
        // @todo: 需要根据 $provider 检查 $external_identity 有效性
        if (!$password = $_POST['password']) {
            apiError(403, 'no_password', 'password must be provided');
        }
        // $autoSignin = intval($_POST['auto_signin']) === 1; // @todo: 记住密码功能
        // adding new identity
        if (($name = trim($_POST['name'])) !== ''
        && !$modIdentity->getIdentityByProviderExternalId($provider, $external_id, true)) {
            if (!($user_id = $modUser->newUserByPassword($password))
             || !$modIdentity->addIdentity($provider, $external_id, array('name' => $name), $user_id)) {
                apiError(403, 'failed', 'failed while signing up new user');
            }
        }
        // raw signin
        $siResult = $modUser->signinForAuthToken($provider, $external_id, $password);
        if ($siResult) {
            apiResponse(array('user_id' => $siResult['user_id'], 'token' => $siResult['token']));
        }
        apiError(403, 'failed', '');
    }


    public function doSignout() {
        $modUser      = $this->getModelByName('user');
        $params       = $this->params;
        $user_id      = intval($params['id']);
        $token        = $params['token'];
        $device_token = $_POST['device_token'];
        if ($user_id && $token && $device_token) {
            $soResult = $modUser->disConnectiOSDeviceToken($user_id, $token, $device_token);
            if ($soResult) {
                apiResponse(array('logout_identity_list' => $soResult));
            }
        }
        apiError(500, 'failed', "can't disconnect this device"); // 失败
    }


    public function doRegdevicetoken() {
        // check if this token allow
        $params   = $this->params;
        $hlpCheck = $this->getHelperByName('check');
        $modUser  = $this->getModelByName('user');
        $user_id  = intval($params['id']);
        $check    = $hlpCheck->isAPIAllow('user_regdevicetoken', $params['token'], array('user_id' => $user_id));
        if (!$check['check']) {
            apiError(403, 'forbidden');
        }
        $devicetoken = $_POST['devicetoken'];
        $provider    = $_POST['provider'];
        $devicename  = $_POST['devicename'];
        $identity_id = $modUser->regDeviceToken($devicetoken, $devicename, $provider, $user_id);
        $identity_id = intval($identity_id);
        if ($identity_id) {
            apiResponse(array('device_token' => $devicetoken, 'identity_id' => $identity_id));
        } else {
            apiError(500, 'reg device token error');
        }
    }


    public function doCrosses() {
        $params = $this->params;
        $uid=$params["id"];
        $updated_at=$params["updated_at"];
        if($updated_at!='')
            $updated_at=date('Y-m-d H:i:s',strtotime($updated_at));

        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("user_crosses",$params["token"],array("user_id"=>$uid));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
        }

        $exfeeHelper= $this->getHelperByName('exfee', 'v2');
        $exfee_id_list=$exfeeHelper->getExfeeIdByUserid(intval($uid),$updated_at);
        $crossHelper= $this->getHelperByName('cross', 'v2');
        if($updated_at!='')
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,true);
        else
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,false);
        apiResponse(array("crosses"=>$cross_list));
    }


    public function doCrossList() {
        // init helpers
        $hlpCheck  = $this->getHelperByName('check', 'v2');
        $hlpExfee  = $this->getHelperByName('exfee', 'v2');
        $hlpCross  = $this->getHelperByName('cross', 'v2');
        // auth check
        $params    = $this->params;
        $user_id   = (int)$params['id'];
        $result    = $hlpCheck->isAPIAllow('user_crosses', $params['token'], array('user_id' => $user_id));
        if (!$result['check']) {
            apiError(401, 'invalid_auth');
        }
        // collecting args
        $today     = strtotime(date('Y-m-d'));
        $upcoming  = $today + 60 * 60 * 24 * 3;
        $sevenDays = $today + 60 * 60 * 24 * 7;
        $fetchArgs = array(
            'upcoming_included'  => strtolower($_GET['upcoming_included'])  === 'false' ? false : true,
            'upcoming_folded'    => strtolower($_GET['upcoming_folded'])    === 'true'  ? true  : false,
            'upcoming_more'      => strtolower($_GET['upcoming_more'])      === 'false' ? false : true,
            'sometime_included'  => strtolower($_GET['sometime_included'])  === 'false' ? false : true,
            'sometime_folded'    => strtolower($_GET['sometime_folded'])    === 'true'  ? true  : false,
            'sometime_more'      => strtolower($_GET['sometime_more'])      === 'true'  ? true  : false,
            'sevendays_included' => strtolower($_GET['sevendays_included']) === 'false' ? false : true,
            'sevendays_folded'   => strtolower($_GET['sevendays_folded'])   === 'true'  ? true  : false,
            'sevendays_more'     => strtolower($_GET['sevendays_more'])     === 'true'  ? true  : false,
            'later_included'     => strtolower($_GET['later_included'])     === 'false' ? false : true,
            'later_folded'       => strtolower($_GET['later_folded'])       === 'true'  ? true  : false,
            'later_more'         => strtolower($_GET['later_more'])         === 'true'  ? true  : false,
            'past_included'      => strtolower($_GET['past_included'])      === 'false' ? false : true,
            'past_folded'        => strtolower($_GET['past_folded'])        === 'true'  ? true  : false,
            'past_more'          => strtolower($_GET['past_more'])          === 'true'  ? true  : false,
        );
        $fetchArgs['past_quantity'] = 0;
        if (isset($_GET['past_quantity'])) {
            $fetchArgs['past_quantity'] = intval($_GET['past_quantity']);
        }
        // get exfee_ids
        $exfee_ids  = $hlpExfee->getExfeeIdByUserid($user_id);
        // get crosses
        $rawCrosses = array();
        if ($fetchArgs['upcoming_included'] || $fetchArgs['sevendays_included'] || $fetchArgs['later_included']) {
            $rawCrosses['future']   = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'future',   $today);
        }
        if ($fetchArgs['past_included']) {
            $rawCrosses['past']     = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'past',     $today);
        }
        if ($fetchArgs['sometime_included']) {
            $rawCrosses['sometime'] = $hlpCross->getCrossesByExfeeIdList($exfee_ids, 'sometime', $today);
        }
        // sort crosses
        $crosses   = array();
        $more      = array();
        $xShowing  = 0;
        $maxCross  = 20;
        $minCross  = 3;
        // sort upcoming crosses
        if ($rawCrosses['future']) {
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                $rawCrosses['future'][$cI]->timestamp = strtotime(
                    $cItem->time->begin_at->date . ' ' . ($cItem->time->begin_at->time ?: '')
                );
                if ($fetchArgs['upcoming_included'] && $rawCrosses['future'][$cI]->timestamp < $upcoming) {
                    $cItem->sort = 'upcoming';
                    array_push($crosses, $cItem);
                    $xShowing += !$fetchArgs['upcoming_folded'] ? 1 : 0;
                    unset($rawCrosses['future'][$cI]);
                }
            }
        }
        // sort sometime crosses
        if ($rawCrosses['sometime']) {
            $xQuantity = !$fetchArgs['sometime_more'] && $xShowing >= $maxCross ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['sometime'] as $cItem) {
                if ($enough) {
                    $more[] = 'sometime';
                    break;
                }
                $cItem->sort = 'sometime';
                array_push($crosses, $cItem);
                $xShowing += !$fetchArgs['sometime_folded'] ? 1 : 0;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($rawCrosses['sometime']);
        }
        // sort next-seven-days crosses
        if ($rawCrosses['future'] && $fetchArgs['sevendays_included']) {
            $xQuantity = !$fetchArgs['sevendays_more'] && $xShowing >= $maxCross ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                if ($cItem->timestamp >= $upcoming && $cItem->timestamp < $sevenDays) {
                    if ($enough) {
                        $more[] = 'sevendays';
                        break;
                    }
                    $cItem->sort = 'sevendays';
                    array_push($crosses, $cItem);
                    $xShowing += !$fetchArgs['sevenDays_folded'] ? 1 : 0;
                    unset($rawCrosses['future'][$cI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        // sort later crosses
        if ($rawCrosses['future'] && $fetchArgs['later_included']) {
            $xQuantity = !$fetchArgs['later_more'] && $xShowing >= $maxCross ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['future'] as $cI => $cItem) {
                if ($cItem['timestamp'] >= $sevendays) {
                    if ($enough) {
                        $more[] = 'later';
                        break;
                    }
                    $cItem->sort = 'later';
                    array_push($crosses, $cItem);
                    $xShowing += !$fetchArgs['later_folded'] ? 1 : 0;
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
            $xQuantity = $fetchArgs['past_more'] ? $maxCross : ($xShowing >= $maxCross ? $minCross : 0);
            $iQuantity = 0;
            $enough    = false;
            foreach ($rawCrosses['past'] as $cItem) {
                if ($fetchArgs['past_quantity']-- > 0) {
                    continue;
                }
                if ($enough) {
                    $more[] = 'past';
                    break;
                }
                $cItem->sort = 'past';
                array_push($crosses, $cItem);
                $xShowing += !$fetchArgs['past_folded'] ? 1 : 0;
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
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser = $this->getModelByName('user', 'v2');
        // collecting post data
        $user = array();
        if (isset($_POST['name'])) {
            $user['name'] = trim($_POST['name']);
        }
        if ($user) {
            if (!$modUser->updateUserById($user_id, $user)) {
                apiError(500, 'update_failed');
            }
        }
        if ($objUser = $modUser->getUserById($user_id, true, 0)) {
            apiResponse(array('user' => $objUser));
        }
        apiError(404, 'user_not_found', 'user not found');
    }


    public function doSetPassword() {
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else if (intval($_SESSION['userid'])) { // @todo removing $_SESSION['userid']
            $user_id = intval($_SESSION['userid']);
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser = $this->getModelByName('user', 'v2');
        // collecting post data
        if (!$modUser->verifyUserPassword($user_id, $_POST['current_password'], true)) {
            apiError(403, 'invalid_current_password', ''); // 密码错误
        }
        if (!($newPassword = $_POST['new_password'])) {
            apiError(400, 'no_new_password', ''); // 请输入当新密码
        }
        if ($modUser->setUserPassword($user_id, $newPassword)) {
            apiResponse(array('user_id' => $user_id)); // 成功
        }
        apiError(500, 'failed', ''); // 操作失败
    }


    public function doSendResetPasswordMail() {
        $modUser = $this->getModelByName('user',  'v2');
        $hlpUser = $this->getHelperByName('user', 'v2');
        if (!($external_id = $_POST['external_id'])) {
            apiError(401, 'no_signin', ''); // 需要输入external_id
        }
        if (!($identity_id = $modIdentity->getIdentityByProviderExternalId('email', $external_id))) {
            apiError(400, 'identity_error', ''); // 无此身份
        }
        $tkResult = $userData->getResetPasswordTokenByIdentityId($identity_id);
        if (!$tkResult) {
            apiError(400, 'failed', ''); // 出错
        }
        $strArrPack = packArray(array(
            'actions'     => 'reset_password',
            'user_id'     => $tkResult['user_id'],
            'identity_id' => $identity_id,
            'provider'    => 'email',
            'external_id' => $external_id,
            'token'       => $tkResult['token'],
        ));
        $objUser = $userData->getUserById($tkResult['user_id']);
        $idJob = $hlpUser->sendResetPasswordMail(array(
            'user'        => $objUser,
            'external_id' => $external_id,
            'provider'    => 'email',
            'token'       => $strArrPack,
        ));
        if ($idJob) {
            apiResponse(array('user_id' => $tkResult['user_id'])); // 成功
        }
        apiError(500, 'failed', ''); // 出错
    }

}
