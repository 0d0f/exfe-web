<?php

class UsersActions extends ActionController {

    public function doIndex()
    {
    }


    public function doGetProfile()
    {
        $params=$this->params;
        $uid=$params["id"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_getprofile",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $identityData=$this->getModelByName("identity");
        $userData=$this->getModelByName("user");
        $identity=$identityData->getIdentitiesByUser($uid);
        $user=$userData->getUser($uid);

        $responobj["meta"]["code"]=200;
        $responobj["response"]["identities"]=$identity;
        $responobj["response"]["user"]=$user;
        echo json_encode($responobj);
        exit(0);
    }


    public function doGetUpdate()
    {
        $params=$this->params;
        $uid=$params["id"];
        $device_identity_id=$params["ddid"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_getupdate",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $loghelper=$this->getHelperByName('log');
        $logs=$loghelper->getMergedXUpdate($uid, 'all', urldecode($params["updated_since"]), 200);

        $identityhelper=$this->getHelperByName('identity', 'v2');
        $identityhelper->cleanIdentityBadgeNumber($device_identity_id,$uid);

        $responobj["meta"]["code"]=200;
        $responobj["response"]=$logs;

        echo json_encode($responobj);
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
        $Data=$this->getModelByName("X");
        $crosses=$Data->getCrossByUserId(intval($params["id"]),urldecode($params["updated_since"]));
        if($crosses=="")
            $crosses=array();

        $conversationData=$this->getModelByName("conversation");
        $identityData=$this->getModelByName("identity");
        $invitationData=$this->getModelByName("invitation");
        $userData=$this->getModelByName("user");
        for($i=0;$i<sizeof($crosses);$i++)
        {
            $cross_id=intval($crosses[$i]["id"]);
            if($cross_id>0)
            {
                $place["line1"]=$crosses[$i]["place_line1"];
                $place["line2"]=$crosses[$i]["place_line2"];
                $place["provider"]=$crosses[$i]["place_provider"];
                $place["external_id"]=$crosses[$i]["place_external_id"];
                $place["lng"]=$crosses[$i]["place_lng"];
                $place["lat"]=$crosses[$i]["place_lat"];
                $crosses[$i]["place"]=$place;
                unset($crosses[$i]["place_line1"]);
                unset($crosses[$i]["place_line2"]);
                unset($crosses[$i]["place_provider"]);
                unset($crosses[$i]["place_external_id"]);
                unset($crosses[$i]["place_lng"]);
                unset($crosses[$i]["place_lat"]);

                $conversations = $conversationData->getConversationByTimeStr($cross_id,"cross",urldecode($params["updated_since"]));
                $crosses[$i]["conversations"]=$conversations;
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


    // upgraded
    public function doLogout()
    {
        $userData=$this->getModelByName("user");
        $params=$this->params;
        $user_id=$params["id"];
        $token=$params["token"];
        $device_token=$_POST["device_token"];
        if($device_token!="" && $token!="" && intval($user_id)>0)
        {
            $result=$userData->disConnectiOSDeviceToken($user_id,$token,$device_token);
            if($result!=null)
            {
                $responobj["meta"]["code"]=200;
                $responobj["response"]=$result;
                echo json_encode($responobj);
                exit(0);
            }
        }
        $responobj["meta"]["code"]=500;
        $responobj["meta"]["err"]="can't disconnect this device";
        echo json_encode($responobj);
        exit(0);
    }


    // upgraded
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


    // upgraded
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

}
