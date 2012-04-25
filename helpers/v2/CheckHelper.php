<?php

class CheckHelper extends ActionController {
    function isAPIAllow($api,$token,$args)
    {
        $userData=$this->getModelByName("user","v2");
        $identityData=$this->getModelByName("identity","v2");
        $exfeeData=$this->getModelByName("exfee","v2");
        $crossData=$this->getModelByName("cross","v2");
        $uid=$userData->getUserIdByToken($token);
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
            {
                if($api=="cross_edit") {
                    $by_identity_id=$args["by_identity_id"];
                    if(intval($by_identity_id)>0) {
                        $r=$identityData->isIdentityBelongsUser($by_identity_id,$uid);
                        if($r===true)
                            return array("check"=>true,"uid"=>$uid,"exfee_id"=>$exfee_id,"by_identity_id"=>$by_identity_id);
                        else
                            return array("check"=>false);
                    }
                    else
                            return array("check"=>false);
                }
                return array("check"=>true,"uid"=>$uid,"exfee_id"=>$exfee_id);
            }
        }
        if($api=="conversation" || $api=="conversation_add") {
            $userids=$exfeeData->getUserIdsByExfeeId($args["exfee_id"]);
            if(in_array($uid, $userids))
                return array("check"=>true,"uid"=>$uid);
        }
        if($api=="conversation_del") {
            if($uid==$args["user_id"])
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
