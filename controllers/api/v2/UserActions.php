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
    }
    
    public function doSignin() {
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        
        $external_identity = $_POST['external_identity'];
        $provider          = $_POST['provider'] ? $_POST['provider'] : 'email';
        $password          = $_POST['password'];
        $name              = $_POST['name'];
        $autosignin        = intval($_POST['auto_signin']) === 1;
        $isNewIdentity     = false;
        
        if ($external_identity !== '' && $password !== '' && $name !== '') {
            // @todo: 根据 $provider 检查 $external_identity 有效性
            $user_id = $modUser->newUserByPassword($password);
            // @todo: check returns
            $modIdentity->addIdentity($user_id, $provider, $external_identity, array('name' => $name));
            // @todo: check returns
            $isNewIdentity = true;
        }
        
        
 

        if($identity!="" && $password!="")
        {
            $Data=$this->getModelByName("identity");
            $userid=$Data->login($identity,$password,$autosignin);
            if(intval($userid)>0)
            {
                //$_SESSION["userid"]=$userid;
                if($isNewIdentity===TRUE)
                    $this->setVar("isNewIdentity", TRUE);

                //if(intval($autosignin)>0)
                //{
                //    //TODO: set cookie
                //    //set cookie
                //}

                if($_GET["url"]!="")
                    header( 'Location:'.$_GET["url"] ) ;
                else if( $isNewIdentity==TRUE)
                    $this->displayView();
                else
                    header( 'Location: /s/profile' ) ;
            } else {
                $this->displayView();
            }
        } else {
            $this->displayView();
        }
        
        /////////////////////////////////
    }
    
    public function doSignup() {
    
    }
    
    public function doGet() {
        
        $modUser = $this->getModelByName('User', 'v2');
        $user = $modUser->getUserById(1);
        print_r($user);
    }

}
