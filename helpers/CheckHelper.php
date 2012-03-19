<?php

class CheckHelper extends ActionController {

    function isAPIAllow($api,$token,$args)
    {
        $userData=$this->getModelByName("user");
        $uid=$userData->getUserIdByToken($token);
        if(intval($uid)==0)
            return array("check"=>false);

        if($api=="user_x" || $api=="user_regdevicetoken" || $api=="user_getupdate" || $api=="user_getprofile")
        {
            if($uid==$args["user_id"])
                return array("check"=>true,"uid"=>$uid);
        }
        if($api=="x_rsvp")
        {
            //if this uid's identity has invitation for this cross?
            $cross_id=$args["cross_id"];
            if(intval($cross_id)==0)
                return array("check"=>false);
            $invitationdata=$this->getModelByName("invitation");
            $identity_list=$invitationdata->getInvitatedIdentityByUserid($uid,$cross_id);
            if(sizeof($identity_list)>0)
                return array("check"=>true,"identity_id_list"=>$identity_list,"user_id"=>$uid);
        }
        if($api=="x_list")
        {
            //if this uid's identity has invitation for this cross?
            $ids=explode(",",$args["ids"]);
            foreach($ids as $id)
            {
                if (intval($id)==0)
                    return array("check"=>false);
            }
            $invitationdata=$this->getModelByName("invitation");
            $identity_list=$invitationdata->getInvitatedIdentityByUseridAndCrossList($uid,$ids);
            if(sizeof($identity_list)>0)
                return array("check"=>true,"identity_id_list"=>$identity_list,"user_id"=>$uid);
        }
        if($api=="x_post")
        {
            $cross_id=$args["cross_id"];
            if(intval($cross_id)==0)
                return array("check"=>false);
            $invitationdata=$this->getModelByName("invitation");
            $identity_list=$invitationdata->getInvitatedIdentityByUserid($uid,$cross_id);
            if(sizeof($identity_list)>0)
                return array("check"=>true,"identity_id_list"=>$identity_list,"user_id"=>$uid);
        }
        return array("check"=>false);
    }

    function isAllow($class,$action,$args="")
    {
        $type="session";
        if($_SESSION["userid"]=="")
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
                    if($result["allow"]==="true"){
                        return array("allow"=>"true","type"=>"token","tokenexpired"=>$result["tokenexpired"]);
                    }
                }
                if(intval($_SESSION["userid"])>0)
                {
                    //$result=$invitationdata->ifIdentityHasInvitation($_SESSION["identity_id"],$cross_id);
                    $result=$invitationdata->ifUserHasInvitation($_SESSION["userid"],$cross_id);
                    if ($result===true) {
                        return array("allow"=>"true","type"=>$type);
                    }
                }
            }
            return array("allow"=>'false');
        }
        else if($class=="mailconversion")
        {
            $cross_id=$args["cross_id"];
            $from=$args["from"];
            $invitationdata=$this->getModelByName("invitation");

            if(intval($cross_id)>0)
            {
                if($from!="")
                {
                    $result=$invitationdata->ifIdentityHasInvitationByIdentity($from,$cross_id);
                    if($result!==false)
                    {
                        $identity_id=intval($result);
                        return array("allow"=>"true","type"=>$type,"identity_id"=>$identity_id);
                    }

                }
            }
            return array("allow"=>'false');
        }
    }

}
