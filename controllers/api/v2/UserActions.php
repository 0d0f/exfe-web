<?php

class UserActions extends ActionController {

    public function doIndex() {
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
            echo json_encode(array('user_id' => $user_id, 'is_new_identity' => $isNewIdentity));
        } else {
            echo json_encode(array('error' => 'Invalid identity or password'));
        }   
    }
    
    
    public function doSignin()
    {
        $modUser     = $this->getModelByName('user');
        $external_id = $_POST['external_id'];
        $password    = $_POST['password'];
        $siResult    = $userData->loginForAuthToken($user,$password);
        echo json_encode(
            ($external_id && $password && $siResult)
           ? array('meta' => array('code' => 200), 'response' => $siResult)
           : array('meta' => array('code' => 404,  'err'      => 'login error'))
        );
    }
    
    
    ////////////////////////////////////////////working on this////////////////////////////////////////////////////
    public function doRegdevicetoken()
    {
        // check if this token allow
        $params=$this->params;
        $checkhelper=$this->getHelperByName("check");
        $uid=$params["id"];
        $check=$checkhelper->isAPIAllow("user_regdevicetoken",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $devicetoken=$_POST["devicetoken"];
        $provider=$_POST["provider"];
        $devicename=$_POST["devicename"];
        $userData=$this->getModelByName("user");
        $identity_id=$userData->regDeviceToken($devicetoken,$devicename,$provider,$uid);
        if(intval($identity_id)>0)
        {
            $responobj["meta"]["code"]=200;
            $responobj["response"]["device_token"]=$devicetoken;
            $responobj["response"]["identity_id"]=$identity_id;
        }
        else
        {
            $responobj["meta"]["code"]=500;
            $responobj["meta"]["error"]="reg device token error";
        }
        echo json_encode($responobj);
        exit(0);
        //add devicetoken with $check["uid"]
    }
    
    
    public function doGet() {
        $modUser = $this->getModelByName('User', 'v2');
        $user = $modUser->getUserById(1);
        print_r($user);
    }

}
