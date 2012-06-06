<?php

class XHelper extends ActionController {

    public function addCrossDiffLog($cross_id, $identity_id, $old_cross, $crossobj) {
        $changed=array();
        $logdata=$this->getModelByName("log");
        if($old_cross["title"] !== $crossobj["title"])
        {
            $changed["title"]=$crossobj["title"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"title",$crossobj["title"],$old_cross["title"]);
        }
        if($old_cross["description"] !== $crossobj["description"])
        {
            $changed["description"]=$crossobj["description"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"description",$crossobj["description"],"");
        }
        if($old_cross['begin_at']  !== $crossobj['begin_at']
        || $old_cross['time_type'] !== $crossobj['time_type']
        || $old_cross['timezone']  !== $crossobj['timezone']) {
            $logdata->addLog(
                'identity', $identity_id, 'change', 'cross', $cross_id, 'begin_at', '',
                json_encode(array(
                    'begin_at'        => $changed['begin_at']        = $crossobj['begin_at'],
                    'time_type'       => $changed['time_type']       = $crossobj['time_type'],
                    'timezone'        => $changed['timezone']        = $crossobj['timezone'],
                    'origin_begin_at' => $changed['origin_begin_at'] = $crossobj['origin_begin_at']))
            );
        }
        if ($old_cross['place']['line1'] !== $crossobj['place']['line1']
         || $old_cross['place']['line2'] !== $crossobj['place']['line2']) {
            $logdata->addLog(
                'identity', $identity_id, 'change', 'cross', $cross_id, 'place',
                '', json_encode($changed['place'] = $crossobj['place'])
            );
        }
        if (sizeof($changed) === 0) {
            return FALSE;
        }

        return $changed;
    }


    //v1_v2_bridge
    public function updateXChange($cross_id,$by_identity_id,$cross)
    {
        $cross_updated=array();
        $updated=array("updated_at"=>date('Y-m-d H:i:s',time()),"identity_id"=>$by_identity_id);

        if($cross["title"])
            $cross_updated["title"]=$updated;
        if($cross["description"])
            $cross_updated["description"]=$updated;
        if($cross["begin_at"])
            $cross_updated["time"]=$updated;
        if($cross["place"])
            $cross_updated["place"]=$updated;
        $sql="update crosses set updated_at=now() where `id`=$cross_id;";

        $crossData=$this->getModelByName("X");
        $crossData->updateCrossUpdateTime($cross_id);
        saveUpdate($cross_id,$cross_updated);
    }


    public function sendXChangeMsg($new_cross,$host_identity_id,$changed,$old_title) {

        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($host_identity_id);
        $userData=$this->getModelByName("user");
        $user=$userData->getUserProfileByIdentityId($host_identity_id);

        $new_cross["begin_at"]   = humanDateTime($new_cross["begin_at"], $user["timezone"] ? $user["timezone"] : $new_cross['timezone']);
        if ($changed["begin_at"]) {
            $changed["begin_at"] = humanDateTime($changed["begin_at"],   $user["timezone"] ? $user["timezone"] : $new_cross['timezone']);;
        }
        $exfee_identity=humanIdentity($exfee_identity,$user);
        $cross_id=$new_cross["id"];

        $this->updateXChange($cross_id,$host_identity_id,$changed);

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
        $mail["to_identity_time_zone"]=$user["timezone"];
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

        foreach($new_cross['invitations'] as  $invitation)
        {
            foreach ($invitation['identities'] as $identity) {
                //TODO: waiting for googollee's twitter client
                //switch ($identity['provider']) {
                //    case 'twitter':
                //        $msghelper->sentTwitterChange($mail);
                //        break;
                //    case 'facebook':
                //        $msghelper->sentFacebookChange($mail);
                //}
            }
        }
    }

    public function sendXInvitationChangeMsg($cross_id,$action_identity_id,$identities,$cross,$old_title) {
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

        foreach ($cross['invitations'] as $ivItem) {
            foreach ($ivItem['identities'] as $identity) {
                switch ($identity['provider']) {
                    case 'twitter':
                        $msghelper->sentTwitterChange($mail);
                        break;
                    case 'facebook':
                        $msghelper->sentFacebookChange($mail);
                }
            }
        }

    }


    public function logX($identity_id, $cross_id, $cross_title) {
        $modLog = $this->getModelByName('log');
        $modLog->addLog('identity', $identity_id, 'gather', 'cross',
                        $cross_id, '', $cross_title, '');
    }


    public function getHistory($cross_id) {

    }


    public function delDraft($draft_id) {
        $modXDraft = $this->getModelByName('XDraft');
        $modXDraft->delDraft($draft_id);
    }

}
