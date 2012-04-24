<?php

class UserActions extends ActionController {

    public function doIndex() {
        return;
        
        echo "Try to get an identity object:\n";
        $identityData = $this->getModelByName('Identity', 'v2');
        $identity = $identityData->getIdentityById(1);
        print_r($identity);
        
        echo "\n\n";

        echo "Try to get a user object:\n";
        $userData = $this->getModelByName('User', 'v2');
        $user = $userData->getUserById(1);
        print_r($user);
        
        echo "\n\n";
        
        echo "Try to get a exfee:\n";
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee = $exfeeData->getExfeeById(100092);
        print_r($exfee);
        
        echo "\n\n";
        
        echo "Try to get user ids by exfee:\n";
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee = $exfeeData->getUserIdsByExfeeId(100092);
        print_r($exfee);
    }


    public function doAddIdentity() {
        // get models
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($user_id = $_SESSION['signin_user']->id)) {
            apiError(401, 'no_signin', ''); // 需要登录
        }
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
        if ($modIdentity->addIdentity($provider, $external_id, $userID = 0)) {
            apiResponse(array('user_id' => $user_id, 'is_new_identity' => $isNewIdentity));
        } else {
            apiError(400, 'failed', '');
        }
    }
    

    public function doDeleteIdentity() {
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($user_id = $_SESSION['signin_user']->id)) {
            apiError(401, 'no_signin', ''); // 需要登录
        }
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


    public function doSetDefaultIdentity() {
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        if (!($user_id = $_SESSION['signin_user']->id)) {
            apiError(401, 'no_signin', ''); // 需要登录
        }
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
    
    
    public function doWebSignout() {
        $modUser = $this->getModelByName('user', 'v2');
        if ($modUser->signout()) {
            apiResponse(array('success' => true));
        } else {
            apiError(400, 'failed', ''); // 失败
        }
    }
    
    
    public function doSignin() {
        $modUser     = $this->getModelByName('user', 'v2');
        $external_id = $_POST['external_id'];
        $provider    = $_POST['provider'] ? $_POST['provider'] : 'email';
        $password    = $_POST['password'];
        $siResult    = $userData->signinForAuthToken($provider, $external_id, $password);
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
        $responobj    = array();
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
    
    
    public function doGet() {
        $modUser = $this->getModelByName('User', 'v2');
        if (!($user_id = $_SESSION['signin_user']->id)) {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        if (!($objUser = $modUser->getUserById($user_id))) {
            apiError(400, 'failed', ''); // 失败
        }
        apiResponse(array('user' => $objUser)); // 成功
    }


    public function doCrosses() {
        $params   = $this->params;
        $uid=$params["id"];

        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("user_crosses",$params["token"],array("user_id"=>$uid));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
        }

        $exfeeHelper= $this->getHelperByName('exfee', 'v2');
        $exfee_id_list=$exfeeHelper->getExfeeIdByUserid(intval($uid));
        $crossHelper= $this->getHelperByName('cross', 'v2');
        $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list);
        apiResponse(array("crosses"=>$cross_list));

        //user
    }
    
    
    public function doSetPassword() {
        $modUser = $this->getModelByName('user', 'v2');
        if (!($user_id = $_SESSION['signin_user']->id || $_SESSION['userid'])) { // @todo removing $_SESSION['userid']
            apiError(401, 'no_signin', ''); // 需要登录
        }
        if (!($curPassword = $_POST['current_password'])) {
            apiError(401, 'no_current_password', ''); // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $curPassword)) {
            apiError(403, 'invalid_password', ''); // 密码错误
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
