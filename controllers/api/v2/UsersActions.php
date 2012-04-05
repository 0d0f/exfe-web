<?php

class UsersActions extends ActionController {

    public function doIndex() {

    }
    
    public function doSignin() {
        $identity = $_POST['identity'];
        $password = $_POST['password'];
        
        
        
        /////////////////////////////////
        //如果已经登录。则访问这个页面时跳转到Profile页面。
        if(intval($_SESSION["userid"])>0){
            header("location:/s/profile");
        }

        //取得变量。
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        //$repassword=$_POST["retypepassword"];
        $displayname=$_POST["displayname"];
        $autosignin=$_POST["auto_signin"];
        if(intval($autosignin)==1){
            $autosignin=true;
        }

        $isNewIdentity=FALSE;

        //if($identity!="" && $password!="" && $repassword==$password && $displayname!="" )
        if($identity!="" && $password!="" && $displayname!="" )
        {
            $Data = $this->getModelByName("user");
            $userid = $Data->AddUser($password);
            $identityData = $this->getModelByName("identity");
            $provider= $_POST["provider"];
            if($provider=="")
                $provider="email";
            $identityData->addIdentity($userid,$provider,$identity,array("name"=>$displayname));
            //TODO: check return value
            $isNewIdentity=TRUE;
            $this->setVar("displayname", $displayname);

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
    
    }

}
