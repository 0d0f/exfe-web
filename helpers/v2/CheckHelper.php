<?php

class CheckHelper extends ActionController {
    function isAPIAllow($api,$token,$args)
    {
        $userData=$this->getModelByName("user","v2");
        $exfeeData=$this->getModelByName("exfee","v2");
        $crossData=$this->getModelByName("cross","v2");
        $uid=$userData->getUserIdByToken($token);
        $access_type="";
        if(intval($uid)>0)
            $access_type="token";
        else {
            $access_type="session";
        }

        if(intval($uid)===0)
        {
            return array("check"=>false,"uid"=>0);
        }

        if($api=="cross"|| $api=="cross_edit") {
            $exfee_id=$crossData->getExfeeByCrossId($args["cross_id"]);
            $userids=$exfeeData->getUserIdsByExfeeId($exfee_id);
            if(in_array($uid, $userids))
                return array("check"=>true,"uid"=>$uid);
        }
        else if($api=="cross_add" ){
                return array("check"=>true,"uid"=>$uid);
        }
        else if($api=="user") {
        }
        else if($api=="user_crosses") {
            if($uid==$args["user_id"])
                return array("check"=>true,"uid"=>$uid);
        }
        else if($api=="user_signin" || $api=="user_signup") {
                return array("check"=>true);
        }

        return array("check"=>false);
        
    }

}
