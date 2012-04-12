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
    }
    
    
    public function doSignin() {
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
    
    
    public function doGet() {
        $modUser = $this->getModelByName('User', 'v2');
        $user = $modUser->getUserById(1);
        print_r($user);
    }

}
