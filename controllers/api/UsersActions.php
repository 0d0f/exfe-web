<?php
class UsersActions extends ActionController {

    public function doIndex()
    {

    }
    public function doLogin()
    {
        $userData=$this->getModelByName("user");
        $user=$_POST["user"];
        $password=$_POST["password"];
        $result=$userData->loginForAuthToken($user,$password);
        if($result)
        {
            $responobj["meta"]["code"]=200;
            $responobj["response"]=$result;;
        }
        else
        {
            $responobj["meta"]["code"]=404;
            $responobj["meta"]["err"]="login error";
        }

        echo json_encode($responobj);
        exit(0);
    }
    public function doX()
    {
        //check if this token allow 
        $params=$this->params;
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_x",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        //print "user/x";
        //print $params["id"];
        //print "<br/>";
        //print $params["updated_since"];

        $Data=$this->getModelByName("X");
        $crosses=$Data->getCrossByUserId(intval($params["id"]),intval($params["updated_since"]));
        
        $conversationData=$this->getModelByName("conversation");
        $identityData=$this->getModelByName("identity");
        $invitationData=$this->getModelByName("invitation");
        $userData=$this->getModelByName("user");
        for($i=0;$i<sizeof($crosses);$i++)
        {
            $cross_id=intval($crosses[$i]["id"]);
            if($cross_id>0)
            {
                $conversations=$conversationData->getConversion($cross_id,'cross',10);
                $crosses[$i]["conversation"]=$conversations;
                $identity=$identityData->getIdentityById(intval($crosses[$i]["host_id"]));
                $user=$userData->getUserByIdentityId(intval($crosses[$i]["host_id"]));
                $crosses[$i]["host"]=humanIdentity($identity,$user);
                $invitations=$invitationData->getInvitation_Identities($crosses[$i]["id"],true);
                $crosses[$i]["invitations"]=$invitations;
                //invitations
            }
        }



        $responobj["meta"]["code"]=200;
        $responobj["response"]["crosses"]=$crosses;
        echo json_encode($responobj);
        
        //get x by id and updated_since
        
    }
    
    public function doRegdevicetoken()
    {
        //check if this token allow 
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
        print_r($check);
        $devicetoken=$_POST["devicetoken"];
        $provider=$_POST["provider"];
        $userData=$this->getModelByName("user");
        $identity_id=$userData->regDeviceToken($devicetoken,$provider,$uid);
        echo $identity_id;
        //add devicetoken with $check["uid"]
    }

}
