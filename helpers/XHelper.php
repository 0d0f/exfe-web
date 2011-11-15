<?php

class XHelper extends ActionController
{

    public function addCrossDiffLog($cross_id, $identity_id, $old_cross, $crossobj)
    {
        $changed=array();
        $logdata=$this->getModelByName("log");
        if($old_cross["title"]!=$crossobj["title"])
        {
            $changed["title"]=$crossobj["title"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"title",$crossobj["title"],"");
        }
        if($old_cross["description"]!=$crossobj["description"])
        {
            $changed["description"]=$crossobj["description"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"description",$crossobj["description"],"");
        }
        if($old_cross["begin_at"]!=$crossobj["begin_at"])
        {
            $changed["begin_at"]=$crossobj["begin_at"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"begin_at",$crossobj["begin_at"],"");
        }
        if($old_cross["place_line1"]!=$crossobj["place_line1"])
        {
            $changed["place_line1"]=$crossobj["place_line1"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"place_line1",$crossobj["place_line1"],"");
        }
        if($old_cross["place_line2"]!=$crossobj["place_line2"])
        {
            $changed["place_line2"]=$crossobj["place_line2"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"place_line2",$crossobj["place_line2"],"");
        }

        if(sizeof($changed)==0)
            return FALSE;

        return $changed;
    }

    public function sendXChangeMsg($new_cross,$host_identity_id,$changed)
    {
        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($host_identity_id);
        $exfee_identity=humanIdentity($exfee_identity,NULL);
        $cross_id=$new_cross["id"];

        $link=SITE_URL.'/!'.int_to_base62($cross_id);
        $mail["link"]=$link;
        $mail["id"]=$cross_id;
        $mail["action"]="changed";
        $mail["objtype"]="cross";
        $mail["template_name"]="changecross";
        $mail["title"]=$new_cross["title"];
        #$mail["exfee_name"]=$exfee_identity["name"];
        $mail["action_identity"]=$exfee_identity;
        $mail["changed"]=$changed;
        $mail["cross"]=$new_cross;
        $mail["timestamp"]=time();

        #$apnargs["content"]=$exfee_identity["name"]." changed ".$old_cross["title"];
        #$apnargs["cross_id"]=$cross_id;

        $msghelper=$this->getHelperByName("msg");
        $msghelper->sentChangeEmail($mail);
    }
    public function sendXInvitationChangeMsg($cross_id,$action_identity_id,$identities)
    {
        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($action_identity_id);
        $exfee_identity=humanIdentity($exfee_identity,NULL);

        $link=SITE_URL.'/!'.int_to_base62($cross_id);
        $mail["link"]=$link;
        $mail["id"]=$cross_id;
        $mail["action"]="changed";
        $mail["objtype"]="identity";
        $mail["template_name"]="changeidentity";
        $mail["changed"]=$changed;
        $mail["action_identity"]=$exfee_identity;
        $mail["identities"]=$identities;
        $mail["timestamp"]=time();

        $msghelper=$this->getHelperByName("msg");
        $msghelper->sentChangeEmail($mail);
        
    }
}
