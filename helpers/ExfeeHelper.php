<?php

class ExfeeHelper extends ActionController
{

    public function addExfeeIdentify($cross_id, $exfee_list, $invited = null)
    {
        $identityData   = $this->getModelByName('identity');
        $invitationData = $this->getModelByName('invitation');

        $curExfees = array();

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
            if (is_array($invited) && in_array($identity_id, $invited)) {
                $invitationData->rsvp($cross_id, $identity_id, $confirmed);
                continue;
            }

            // add invitation
            $invitationData->addInvitation($cross_id, $identity_id, $confirmed);
        }

        if (is_array($invited)) {
            foreach ($invited as $identity_id) {
                if (!in_array($identity_id, $curExfees)) {
                    $invitationData->delInvitation($cross_id, $identity_id);
                }
            }
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
        Resque::setBackend('127.0.0.1:6379');
        if($invitations)
            foreach ($invitations as $invitation) {
               $args = array(
                        'title' => $cross["title"],
                        'description' => $cross["description"],
                        'begin_at' => $cross["begin_at"],
                        'place_line1' => $cross["place"]["line1"],
                        'place_line2' => $cross["place"]["line2"],
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

}
