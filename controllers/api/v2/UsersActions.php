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
        if ($objUser = $modUser->getUserById($params['id'])) {
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
        if ($identity_id = $modIdentity->addIdentity($provider, $external_id, $user_id)) {
            apiResponse(array('user_id' => $user_id, 'identity_id' => $identity_id));
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


    //@todo: merge with new sign in
    public function doWebSignin() {
        // get models
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // init
        $rtResult      = array();
        $isNewIdentity = false;
        // collecting post data
        $external_id   = $_POST['external_id'];
        $provider      = $_POST['provider'] ? $_POST['provider'] : 'email';
        $password      = $_POST['password'];
        $name          = $_POST['name'];
        $autoSignin    = intval($_POST['auto_signin']) === 1;
        // adding new identity
        if ($external_id && $password && $name) {
            // @todo: 根据 $provider 检查 $external_identity 有效性
            $user_id = $modUser->newUserByPassword($password);
            // @todo: check returns
            $modIdentity->addIdentity($provider, $external_id, array('name' => $name), $user_id);
            // @todo: check returns
            $isNewIdentity = true;
        }
        // try to sign in
        if ($external_id && $password && ($user_id = $modUser->login($external_id, $password, $autosignin))) {
            apiResponse(array('user_id' => $user_id, 'is_new_identity' => $isNewIdentity));
        } else {
            apiError(403, 'invalid_identity_or_password', ''); // 失败
        }
    }


    //@todo: merge with new sign out
    public function doWebSignout() {
        $modUser = $this->getModelByName('user', 'v2');
        if ($modUser->signout()) {
            apiResponse(array('success' => true));
        } else {
            apiError(400, 'failed', ''); // 失败
        }
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


    public function doSignin() {
        $modUser     = $this->getModelByName('user', 'v2');
        $external_id = $_POST['external_id'];
        $provider    = $_POST['provider'] ? $_POST['provider'] : 'email';
        $password    = $_POST['password'];
        $siResult    = $modUser->signinForAuthToken($provider, $external_id, $password);
        if ($external_id && $password && $siResult) {
            apiResponse(array('user_id' => $siResult['user_id'], 'token' => $siResult['token']));
        } else {
            apiError(403, 'failed', ''); // 失败
        }
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


    public function doRegdevicetoken()
    {
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
        $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list);
        apiResponse(array("crosses"=>$cross_list));
    }


    public function doCrossList() {
        // auth check
        $params      = $this->params;
        $user_id     = (int)$params['id'];
        $checkHelper = $this->getHelperByName('check', 'v2');
        $result      = $checkHelper->isAPIAllow('user_crosses', $params['token'], array('user_id' => $user_id));
        if (!$result['check']) {
            apiError(401, 'invalid_auth');
        }
        // init models
        $modCross    = $this->getModelByName('cross', 'v2');
        // get crosses
        $today       = strtotime(date('Y-m-d'));
        $upcoming    = $today + 60 * 60 * 24 * 3;
        $sevenDays   = $today + 60 * 60 * 24 * 7;
        $fetchArgs   = array(
            'upcoming_included'  => $_GET['upcoming_included']  === 'false' ? false : true,
            'upcoming_folded'    => $_GET['upcoming_folded']    === 'true'  ? true  : false,
            'upcoming_more'      => $_GET['upcoming_more']      === 'false' ? false : true,
            'anytime_included'   => $_GET['anytime_included']   === 'false' ? false : true,
            'anytime_folded'     => $_GET['anytime_folded']     === 'true'  ? true  : false,
            'anytime_more'       => $_GET['anytime_more']       === 'true'  ? true  : false,
            'sevendays_included' => $_GET['sevendays_included'] === 'false' ? false : true,
            'sevendays_folded'   => $_GET['sevendays_folded']   === 'true'  ? true  : false,
            'sevendays_more'     => $_GET['sevendays_more']     === 'true'  ? true  : false,
            'later_included'     => $_GET['later_included']     === 'false' ? false : true,
            'later_folded'       => $_GET['later_folded']       === 'true'  ? true  : false,
            'later_more'         => $_GET['later_more']         === 'true'  ? true  : false,
            'past_included'      => $_GET['past_included']      === 'false' ? false : true,
            'past_folded'        => $_GET['past_folded']        === 'true'  ? true  : false,
            'past_more'          => $_GET['past_more']          === 'true'  ? true  : false,
        );
        $fetchArgs['past_quantity'] = 0;
        if (isset($_GET['past_quantity'])) {
            $fetchArgs['past_quantity'] = intval($_GET['past_quantity']);
        }
        if ($fetchArgs['upcoming_included']
         || $fetchArgs['sevendays_included']
         || $fetchArgs['later_included']) {
            $futureXs  = $modCross->fetchCross($_SESSION['userid'], $today,
                                               'yes', '`begin_at` DESC');
        }
        if ($fetchArgs['past_included']) {
            $pastXs    = $modCross->fetchCross($_SESSION['userid'], $today,
                                               'no',  '`begin_at` DESC');
        }
        if ($fetchArgs['anytime_included']) {
            $anytimeXs = $modCross->fetchCross($_SESSION['userid'], 0,
                                               'anytime', '`created_at` DESC');
        }

        // sort crosses
        $crosses   = array();
        $xShowing  = 0;
        $maxCross  = 20;
        $minCross  = 3;
        // sort upcoming crosses
        if ($fetchArgs['upcoming_included']
         || $fetchArgs['sevenDays_included']
         || $fetchArgs['later_included']) {
            foreach ($futureXs as $crossI => $crossItem) {
                $futureXs[$crossI]['timestamp']
              = strtotime($crossItem['begin_at']);
                if (!$fetchArgs['upcoming_included']) {
                    continue;
                }
                if ($futureXs[$crossI]['timestamp'] < $upcoming) {
                    $futureXs[$crossI]['sort'] = 'upcoming';
                    array_push($crosses, $futureXs[$crossI]);
                    $xShowing += !$fetchArgs['upcoming_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                }
            }
        }
        // sort anytime crosses
        if ($fetchArgs['anytime_included']) {
            $xQuantity = !$fetchArgs['anytime_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($anytimeXs as $crossItem) {
                if ($enough) {
                    array_push($crosses,
                               array('sort'=>'anytime', 'more'=>true));
                    continue;
                }
                $crossItem['sort'] = 'anytime';
                array_push($crosses, $crossItem);
                $xShowing += !$fetchArgs['anytime_folded'] ? 1 : 0;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($anytimeXs);
        }
        // sort next-seven-days cross
        if ($fetchArgs['sevenDays_included']) {
            $xQuantity = !$fetchArgs['sevenDays_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($futureXs as $crossI => $crossItem) {
                if ($crossItem['timestamp'] >= $upcoming
                 && $crossItem['timestamp'] <  $sevenDays) {
                    if ($enough) {
                        array_push($crosses,
                                   array('sort'=>'sevenDays', 'more'=>true));
                        continue;
                    }
                    $crossItem['sort'] = 'sevenDays';
                    array_push($crosses, $crossItem);
                    $xShowing += !$fetchArgs['sevenDays_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        // sort later cross
        if ($fetchArgs['later_included']) {
            $xQuantity = !$fetchArgs['later_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($futureXs as $crossItem) {
                if ($crossItem['timestamp'] >= $sevenDays) {
                    if ($enough) {
                        array_push($crosses,
                                   array('sort'=>'later', 'more'=>true));
                        continue;
                    }
                    $crossItem['sort'] = 'later';
                    array_push($crosses, $crossItem);
                    $xShowing += !$fetchArgs['later_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        unset($futureXs);
        // sort past cross
        if ($fetchArgs['past_included']) {
            $xQuantity = $fetchArgs['past_more']
                       ? $maxCross
                       : ($xShowing >= $maxCross ? $minCross : 0);
            $iQuantity = 0;
            $enough    = false;
            foreach ($pastXs as $crossItem) {
                if ($fetchArgs['past_quantity']-- > 0) {
                    continue;
                }
                if ($enough) {
                    array_push($crosses,
                               array('sort'=>'past', 'more'=>true));
                    continue;
                }
                $crossItem['sort'] = 'past';
                array_push($crosses, $crossItem);
                $xShowing += !$fetchArgs['past_folded'] ? 1 : 0;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($pastXs);
        }

        // get confirmed informations
        $crossIds = array();
        foreach ($crosses as $crossI => $crossItem) {
            if ($crossItem['id'] !== null) {
                array_push($crossIds, $crossItem['id']);
            }
        }
        $cfedInfo = $modIvit->getIdentitiesIdsByCrossIds($crossIds);

        // get related identities
        $relatedIdentityIds = array();
        foreach ($cfedInfo as $cfedInfoI => $cfedInfoItem) {
            $relatedIdentityIds[$cfedInfoItem['identity_id']] = true;
        }
        $relatedIdentities = $modIdentity->getIdentitiesByIdentityIds(
            array_keys($relatedIdentityIds)
        ) ?: array();

        // get human identities
        $humanIdentities = array();
        foreach ($relatedIdentities as $ridI => $ridItem) {
            $user = $modUser->getUserByIdentityId($ridItem['identity_id']);
            $humanIdentities[$ridItem['id']] = humanIdentity($ridItem, $user);
            //unset($humanIdentities[$ridItem['id']]['activecode']);
        }

        // Add informations into crosses
        foreach ($crosses as $crossI => $crossItem) {
            $crosses[$crossI]['base62id'] = int_to_base62($crossItem['id']);
            $crosses[$crossI]['begin_at'] = array(
                'begin_at'        => $crosses[$crossI]['begin_at'],
                'time_type'       => $crosses[$crossI]['time_type'],
                'timezone'        => $crosses[$crossI]['timezone'],
                'origin_begin_at' => $crosses[$crossI]['origin_begin_at'],
            );
            $crosses[$crossI]['exfee']     = array();
            foreach ($cfedInfo as $cfedInfoI => $cfedInfoItem) {
                if ($cfedInfoItem['cross_id'] === $crossItem['id']) {
                    $exfe = $humanIdentities[$cfedInfoItem['identity_id']];
                    $exfe['rsvp'] = $cfedInfoItem['state'];
                    array_push($crosses[$crossI]['exfee'], $exfe);
                }
            }
        }

        echo json_encode($crosses);
















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
