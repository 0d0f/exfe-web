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
            // time type
            $changed["time_type"] = $crossobj['time_type'];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"begin_at", "{$crossobj["begin_at"]},{$changed["time_type"]}","");
        }
        if ($old_cross['place']['line1'] !== $crossobj['place']['line1']
         || $old_cross['place']['line2'] !== $crossobj['place']['line2']) {
            $logdata->addLog(
                'identity', $identity_id, 'change', 'cross', $cross_id, 'place',
                '', json_encode($changed['place'] = $crossobj['place'])
            );
        }
        if(sizeof($changed)==0)
            return FALSE;

        return $changed;
    }


    public function sendXChangeMsg($new_cross,$host_identity_id,$changed,$old_title)
    {
        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($host_identity_id);
        $userData=$this->getModelByName("user");
        $user=$userData->getUserProfileByIdentityId($host_identity_id);

        $exfee_identity=humanIdentity($exfee_identity,$user);
        $cross_id=$new_cross["id"];

        $link=SITE_URL.'/!'.int_to_base62($cross_id);
        $mail["link"]=$link;
        $mutelink=SITE_URL.'/mute/x?id='.int_to_base62($cross_id);
        $mail["id"]=$cross_id;
        $mail["action"]="changed";
        $mail["objtype"]="cross";
        $mail["template_name"]="changecross";
        $mail["title"]=$old_title;//$new_cross["title"];
        #$mail["exfee_name"]=$exfee_identity["name"];
        $mail["action_identity"]=array(0=>$exfee_identity);
        $mail["changed"]=$changed;
        $mail["mutelink"]=$mutelink;
        $mail["cross"]=$new_cross;
        $mail["timestamp"]=time();

        $apnargs["id"]=$cross_id;
        $apnargs["title"]=$old_title;
        $apnargs["action_identity"]=array(0=>$exfee_identity);
        $apnargs["changed"]=$changed;
        $apnargs["mutelink"]=$mutelink;
        $apnargs["cross"]=$new_cross;
        $apnargs["timestamp"]=time();
        $apnargs["job_type"]="crossupdate";

        $msghelper=$this->getHelperByName("msg");
        $msghelper->sentChangeEmail($mail);
        $msghelper->sentApnConversation($apnargs);
    }
    public function sendXInvitationChangeMsg($cross_id,$action_identity_id,$identities,$cross,$old_title)
    {
        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($action_identity_id);
        $exfee_identity=humanIdentity($exfee_identity,NULL);

        $link=SITE_URL.'/!'.int_to_base62($cross_id);
        $mail["link"]=$link;
        $mutelink=SITE_URL.'/mute/x?id='.int_to_base62($cross_id);
        $mail["id"]=$cross_id;
        $mail["action"]="changed";
        $mail["objtype"]="identity";
        $mail["template_name"]="changeidentity";
        $mail["title"]=$old_title;
        $mail["changed"]=$changed;
        $mail["action_identity"]=array(0=>$exfee_identity);
        $mail["identities"]=$identities;
        $mail["mutelink"]=$mutelink;
        $mail["cross"]=$cross;
        $mail["timestamp"]=time();

        $msghelper=$this->getHelperByName("msg");
        $msghelper->sentChangeEmail($mail);

    }


    public function logX($identity_id, $cross_id, $cross_title)
    {
        $modLog = $this->getModelByName('log');
        $modLog->addLog('identity', $identity_id, 'gather', 'cross',
                        $cross_id, '', $cross_title, '');
    }


    public function delDraft($draft_id)
    {
        $modXDraft = $this->getModelByName('XDraft');
        $modXDraft->delDraft($draft_id);
    }

}
