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
        if($old_cross["description"]!=$crossobj["desc"])
        {
            $changed["description"]=$crossobj["desc"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"description",$crossobj["desc"],"");
        }
        if($old_cross["begin_at"]!=$crossobj["start_time"])
        {
            $changed["begin_at"]=$crossobj["begin_at"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"begin_at",$crossobj["start_time"],"");
        }
        if(sizeof($changed)==0)
            return FALSE;

        return $changed;

#sentTemplateEmail($mail)
#sentApnConversation($args)

        //merge diff and send msg

    }

    public function sendXChangeMsg($cross_id,$host_identity_id,$changed,$old_cross)
    {
        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($host_identity_id);
        $exfee_identity=humanIdentity($exfee_identity,NULL);

        $link=SITE_URL.'/!'.int_to_base62($cross_id);
        $mail["link"]=$link;
        $mail["template_name"]="changecross";
        $mail["action"]="changed";
        $mail["title"]=$old_cross["title"];
        $mail["exfee_name"]=$exfee_identity["name"];

        $apnargs["content"]=$exfee_identity["name"]." changed ".$old_cross["title"];
        $apnargs["cross_id"]=$cross_id;

                $invitationdata=$this->getmodelbyname("invitation");
                $invitation_identities=$invitationdata->getInvitation_Identities($cross_id);
                if($invitation_identities)
                foreach($invitation_identities as $invitation_identity)
                {
                    $identities=$invitation_identity["identities"];
                    if($identities)
                    foreach($identities as $identity)
                    {
                        if(intval($identity["status"])==3)
                        {
                                $identity=humanidentity($identity,null);
                                $msghelper=$this->gethelperbyname("msg");
                                if($identity["provider"]=="email")
                                {
                                    $mail["external_identity"]=$identity["external_identity"];
                                    $mail["provider"]=$identity["provider"];
                                //    $msghelper->senttemplateemail($mail);
                                }
                                if($identity["provider"]=="iOSAPN")
                                {
                                    $apnargs["external_identity"]=$identity["external_identity"];
                                //   $msghelper->sentapnchangecross($apnargs);
                                }
                                else
                                {
                                }
                        }
                    }
                }

    }

}
