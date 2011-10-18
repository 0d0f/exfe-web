<?php

class ExfeeHelper extends ActionController
{

    public function addExfeeIdentify($cross_id, $exfee_list, $invited = null)
    {
        $identityData   = $this->getModelByName('identity');
        $invitationData = $this->getModelByName('invitation');
        $logData        = $this->getModelByName('log');

        $curExfees = array();
        $newExfees = array();
        $inviteIds = array();

        if (is_array($invited)) {
            foreach ($invited as $identItem) {
                $inviteIds[$identItem['identity_id']] = $identItem;
            }
        }

        //TODO: package as a transaction
        foreach ($exfee_list as $exfeeI => $exfeeItem) {
            $identity_id   = isset($exfeeItem['exfee_id'])       ? $exfeeItem['exfee_id']       : null;
            $confirmed     = isset($exfeeItem['confirmed'])      ? $exfeeItem['confirmed']      : 0;
            $identity      = isset($exfeeItem['exfee_identity']) ? $exfeeItem['exfee_identity'] : null;
            $identity_type = isset($exfeeItem['identity_type'])  ? $exfeeItem['identity_type']  : 'unknow';

            if (!$identity_id) {
                $identity_id  = $identityData->ifIdentityExist($identity);
                if ($identity_id === false) {
                    // TODO: add new Identity, need check this identity provider, now default "email"
                    // add identity
                    $identity_id = $identityData->addIdentityWithoutUser('email', $identity);
                }
            }

            array_push($curExfees, $identity_id);

            // update rsvp status
            if (is_array($invited)) {
                if (isset($inviteIds[$identity_id])) {
                    if ($inviteIds[$identity_id]['state'] !== $confirmed) {
                        $invitationData->rsvp($cross_id, $identity_id, $confirmed);
                        $logData->addLog('identity', $_SESSION['identity_id'], 'exfee', 'cross', $cross_id, 'rsvp', "{$identity_id}:{$confirmed}");
                    }
                    continue;
                }
                array_push($newExfees, $identity_id);
            }

            // add invitation
            $invitationData->addInvitation($cross_id, $identity_id, $confirmed);
            $logData->addLog('identity', $_SESSION['identity_id'], 'exfee', 'cross', $cross_id, 'addexfee', $identity_id);
        }

        if (is_array($invited)) {
            foreach ($inviteIds as $identity_id => $identity_item) {
                if (!in_array($identity_id, $curExfees)) {
                    $invitationData->delInvitation($cross_id, $identity_id);
                    $logData->addLog('identity', $_SESSION['identity_id'], 'exfee', 'cross', $cross_id, 'delexfee', $identity_id);
                }
            }
        }

        if (is_array($invited)) {
            return $newExfees;
        }
    }

    public function sendIdentitiesInvitation($cross_id,$identity_list)
    {

        $invitationdata=$this->getModelByName("invitation");
        $invitations=$invitationdata->getInvitation_Identities_ByIdentities($cross_id, $identity_list,false, $filter);

        $crossData=$this->getModelByName("X");
        $cross=$crossData->getCross($cross_id);
        $place_id=$cross["place_id"];
        if(intval($place_id)>0)
        {
            $placeData=$this->getModelByName("place");
            $place=$placeData->getPlace($place_id);
            $cross["place"]=$place;
        }

        require_once 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        if($invitations)
            foreach ($invitations as $invitation) {
               $args = array(
                        'title' => $cross["title"],
                        'description' => $cross["description"],
                        'begin_at' => $cross["begin_at"],
                        'place_line1' => $cross["place"]["line1"],
                        'place_line2' => $cross["place"]["line2"],
                        'cross_id' => $cross_id,
                        'cross_id_base62' => int_to_base62($cross_id),
                        'invitation_id' => $invitation["invitation_id"],
                        'token' => $invitation["token"],
                        'identity_id' => $invitation["identity_id"],
                        'provider' => $invitation["provider"],
                        'external_identity' => $invitation["external_identity"],
                        'name' => $invitation["name"],
                        'avatar_file_name' => $invitation["avatar_file_name"]
                );
                $jobId = Resque::enqueue($invitation["provider"],$invitation["provider"]."_job" , $args, true);

                $identities=$invitation["identities"];
                if($identities)
                {
                    foreach ($identities as $identity)
                    {
                        if($identity["provider"]=="iOSAPN")
                        {
                            $args["identity"]=$identity;
                            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
                        }

                    }
                }

                //echo "Queued job ".$jobId."\n\n";
            }
    }

    public function sendInvitation($cross_id, $filter)
    {
        $invitationdata=$this->getModelByName("invitation");
        $invitations=$invitationdata->getInvitation_Identities($cross_id, false, $filter);

        $crossData=$this->getModelByName("X");
        $cross=$crossData->getCross($cross_id);
        $place_id=$cross["place_id"];
        if(intval($place_id)>0)
        {
            $placeData=$this->getModelByName("place");
            $place=$placeData->getPlace($place_id);
            $cross["place"]=$place;
        }

        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        if($invitations)
            foreach ($invitations as $invitation) {
               $args = array(
                        'title' => $cross["title"],
                        'description' => $cross["description"],
                        'begin_at' => $cross["begin_at"],
                        'place_line1' => $cross["place"]["line1"],
                        'place_line2' => $cross["place"]["line2"],
                        'cross_id' => $cross_id,
                        'cross_id_base62' => int_to_base62($cross_id),
                        'invitation_id' => $invitation["invitation_id"],
                        'token' => $invitation["token"],
                        'identity_id' => $invitation["identity_id"],
                        'provider' => $invitation["provider"],
                        'external_identity' => $invitation["external_identity"],
                        'name' => $invitation["name"],
                        'avatar_file_name' => $invitation["avatar_file_name"]
                );
                $jobId = Resque::enqueue($invitation["provider"],$invitation["provider"]."_job" , $args, true);

                $identities=$invitation["identities"];
                if($identities)
                {
                    foreach ($identities as $identity)
                    {
                        if($identity["provider"]=="iOSAPN")
                        {
                            $args["identity"]=$identity;
                            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
                        }

                    }
                }

                //echo "Queued job ".$jobId."\n\n";
            }
    }
    public function sendConversationMsg($cross_id,$host_identity_id,$content)
    {
                $mailargs=array();
                $apnargs=array();

                $link=SITE_URL.'/!'.int_to_base62($cross_id);
                $mutelink=SITE_URL.'/mute/x?id='.int_to_base62($cross_id);
                $mail["link"]=$link;
                $mail["mutelink"]=$mutelink;
                $mail["template_name"]="conversation";
                $mail["action"]="post";
                $mail["content"]=$content;

                $crossData=$this->getModelByName("x");
                $cross=$crossData->getCross($cross_id);
                $mail["title"]=$cross["title"];

                $identityData=$this->getModelByName("identity");
                $exfee_identity=$identityData->getIdentityById($host_identity_id);
                $exfee_identity=humanIdentity($exfee_identity,NULL);
                $mail["exfee_name"]=$exfee_identity["name"];

                $apnargs["exfee_name"]=$exfee_identity["name"];
                $apnargs["comment"]=$content;
                $apnargs["cross_id"]=$cross_id;

                $invitationdata=$this->getmodelbyname("invitation");
                $invitation_identities=$invitationdata->getinvitation_identities($cross_id);
                if($invitation_identities)
                foreach($invitation_identities as $invitation_identity)
                {
                    $identities=$invitation_identity["identities"];
                    if($identities)
                    foreach($identities as $identity)
                    {
                        if(intval($identity["status"])==3)
                        {
                            $muteData=$this->getmodelbyname("mute");
                            $mute=$muteData->ifIdentityMute("x",$cross_id,$identity["identity_id"]);
                            if($mute===FALSE)
                            {
                                $identity=humanidentity($identity,null);
                                $msghelper=$this->gethelperbyname("msg");
                                if($identity["provider"]=="email")
                                {
                                    $mail["external_identity"]=$identity["external_identity"];
                                    $mail["provider"]=$identity["provider"];
                                    $msghelper->senttemplateemail($mail);
                                }
                                if($identity["provider"]=="iOSAPN")
                                {
                                    $apnargs["external_identity"]=$identity["external_identity"];
                                    $msghelper->sentapnconversation($apnargs);
                                }
                                else
                                {
                                }
                            }
                        }
                    }
                }
    }

}
