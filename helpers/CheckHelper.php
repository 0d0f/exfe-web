<?php

class CheckHelper extends ActionController {

    function isAPIAllow($api,$token,$args)
    {
        $userData=$this->getModelByName("user");
        $uid=$userData->getUserIdByToken($token);
        if($api="user_x")
        {
            if($uid==$args["user_id"])
                return true;
        }
        return false;
    }
    function isAllow($class,$action,$args="")
    {
        $type="session";
        if($_SESSION["identity_id"]=="")
        {
            $indentityData=$this->getModelByName("identity");
            $indentityData->loginByCookie();
            $type="cookie";
        }
        if(($class=='x' && $action=='index') || $class=='rsvp' || $class=='conversion')
        {
            $token=$args["token"];
            $cross_id=$args["cross_id"];
            $invitationdata=$this->getModelByName("invitation");

            if(intval($cross_id)>0)
            {
                if($token!="")
                {
                    $result=$invitationdata->ifIdentityHasInvitationByToken($token,$cross_id);
                    if($result===true)
                        return array("allow"=>"true","type"=>"token");
                }
                if(intval($_SESSION["identity_id"])>0)
                {
                    $result=$invitationdata->ifIdentityHasInvitation($_SESSION["identity_id"],$cross_id);
                    if($result===true)
                    {
                        return array("allow"=>"true","type"=>$type);
                    }
                }
            }
            return array("allow"=>'false');
        }
    }

}
