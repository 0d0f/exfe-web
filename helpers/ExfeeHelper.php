<?php

class ExfeeHelper extends ActionController {

    public function addExfeeIdentify($cross_id, $exfee_list, $my_identity_id = 0 , $invited = null, $host_identity_id = 0) {
        $identityData   = $this->getModelByName('identity');
        $invitationData = $this->getModelByName('invitation');
        $relationData   = $this->getModelByName('relation');
        $logData        = $this->getModelByName('log');
        $crossData      = $this->getModelByName('x');

        $curExfees = array();
        $newExfees = array();
        $delExfees = array();
        $allExfees = array();
        $inviteIds = array();
        $nedUpdate = false;

        if (is_array($invited)) {
            foreach ($invited as $identItem) {
                $inviteIds[$identItem['identity_id']] = $identItem;
            }
        }

        $addrelation = false;
        //TODO: package as a transaction
        if ($exfee_list) {
            foreach ($exfee_list as $exfeeI => $exfeeItem) {
                if (!isset($exfeeItem['identity_type'])) {
                    continue;
                } else if ($exfeeItem['identity_type'] === 'email') {
                    $identity_ext_name = isset($exfeeItem['exfee_identity'])
                                       ? $exfeeItem['exfee_identity'] : null;
                } else if (($exfeeItem['identity_type'] === 'twitter'
                         || $exfeeItem['identity_type'] === 'facebook')
                   && isset($exfeeItem['exfee_ext_name'])) {
                    $identity_ext_name = $exfeeItem['exfee_ext_name'];
                } else {
                    continue;
                }
                $identity        = $identity_ext_name;
                $identity_id     = isset($exfeeItem['exfee_id'])         ? intval($exfeeItem['exfee_id'])  : null;
                $identity_name   = isset($exfeeItem['exfee_name'])       ? $exfeeItem['exfee_name']        : null;
                $identity_bio    = isset($exfeeItem['bio'])              ? $exfeeItem['bio']               : null;
                $identity_avatar = isset($exfeeItem['avatar_file_name']) ? $exfeeItem['avatar_file_name']  : null;
                $confirmed       = isset($exfeeItem['confirmed'])        ? intval($exfeeItem['confirmed']) : 0;
                $identity_type   = $exfeeItem['identity_type'];

                if (!$identity_id) {
                    $identity_id = $identityData->ifIdentityExist($identity, $identity_type);
                    if ($identity_id) {
                        $identity_id = $identity_id['id'];
                    } else {
                        // add identity
                        $identity_id = $identityData->addIdentityWithoutUser(
                            $identity_type,
                            $identity,
                            array('name'              => $identity_name,
                                  'external_username' => $identity_ext_name,
                                  'bio'               => $identity_bio,
                                  'avatar_file_name'  => $identity_avatar)
                        );
                    }
                }

                array_push($curExfees, $identity_id);
                $allExfees[$identity_id] = $confirmed;

                // update rsvp status
                if (is_array($invited)) {
                    if (isset($inviteIds[$identity_id])) {
                        if (intval($inviteIds[$identity_id]['state']) !== $confirmed) {
                            $result=$invitationData->rsvp($cross_id, $identity_id, $confirmed);
                            $invitation_id=$result["id"];
                            $logData->addLog('identity', $_SESSION['identity_id'], 'rsvp', 'cross', $cross_id, '', "{$identity_id}:{$confirmed}","{\"id\":$invitation_id}");
                            $nedUpdate = true;
                        }
                        continue;
                    }

                    $newExfees[$identity_id] = $confirmed;
                    //array_push($newExfees, $identity_id);
                }

                // add invitation
                $invitation_id=$invitationData->addInvitation($cross_id, $identity_id, $confirmed, $my_identity_id, $host_identity_id);
                $r=$relationData->saveRelations($_SESSION['userid'], $identity_id);
                if ($r > 0) {
                    $addrelation = true;
                }

                $logData->addLog('identity', $_SESSION['identity_id'], 'exfee', 'cross', $cross_id, 'addexfee', $identity_id);
                $logData->addLog('identity', $_SESSION['identity_id'], 'rsvp', 'cross', $cross_id, '', "{$identity_id}:{$confirmed}","{\"id\":$invitation_id}");
                $nedUpdate = true;
            }
        }

        if ($addrelation) {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->zRemrangebyrank("u:{$_SESSION['userid']}", 0, -1);
        }

        if (is_array($invited)) {
            foreach ($inviteIds as $identity_id => $identity_item) {
                if (!in_array($identity_id, $curExfees)) {
                    $invitationData->delInvitation($cross_id, $identity_id);
                    $logData->addLog('identity', $_SESSION['identity_id'], 'exfee', 'cross', $cross_id, 'delexfee', $identity_id);
                    $nedUpdate = true;
                    $delExfees[$identity_id] = $confirmed;
                    //array_push($delExfees, $identity_id);
                }
            }
        }

        if ($nedUpdate) {
            $crossData->updateCrossUpdatedAt($cross_id);

            saveUpdate(
                $cross_id,
                array('exfee' => array('updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $my_identity_id))
            );
        }

        if (is_array($invited)) {
            return array('newexfees' => $newExfees,
                         'allexfees' => $allExfees,
                         'delexfees' => $delExfees);
        }
    }

    public function sendIdentitiesInvitation($cross_id,$identity_list,$allexfee)
    {
        $userid=$_SESSION["userid"];
        if($userid>0)
        {
            $userData = $this->getModelByName("user");
            $user=$userData->getUser($userid);
        }
        $identity_id = $_SESSION['identity_id'];

        $invitationdata=$this->getModelByName("invitation");
        $invitations=$invitationdata->getInvitation_Identities_ByIdentities($cross_id, $identity_list,false);
        $allinvitations=$invitationdata->getInvitation_Identities_ByIdentities($cross_id, $allexfee ,false);
        $crossData=$this->getModelByName("X");
        $cross=$crossData->getCross($cross_id);

        $host_id=$cross["host_id"];
        if($host_id >0)
        {
            $identitydata=$this->getModelByName("identity");
            $host_identity=$identitydata->getIdentityById($host_id);
        }
        //$host_identity=humanIdentity($host_identit,$user);



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
                if (intval($invitation["by_identity_id"]) > 0) {
                    $userprofile=$userData->getUserProfileByIdentityId($invitation["identity_id"]);
                }
                if($identity_id===$invitation["identity_id"]) //don't send to myself
                    continue;
                $by_identity=$identitydata->getIdentityById($invitation["by_identity_id"]);
                $to_identity=$identitydata->getIdentityById($invitation["identity_id"]);
                $args = array(
                    'title' => $cross["title"],
                    'description' => $cross["description"],
                    'begin_at' =>  humanDateTime($cross["begin_at"],$userprofile['timezone'] === '' ? $cross['timezone'] : $userprofile['timezone']),
                    'place_line1' => $cross["place"]["line1"],
                    'place_line2' => $cross["place"]["line2"],
                    'cross_id' => $cross_id,
                    'cross_id_base62' => int_to_base62($cross_id),
                    'invitation_id' => $invitation["invitation_id"],
                    'token' => $invitation["token"],
                    'identity_id' => $invitation["identity_id"],
                    'host_identity_id' => $host_id,
                    'host_identity' => $host_identity,
                    'provider' => $invitation["provider"],
                    'external_identity' => $invitation["external_identity"],
                    'name' => $invitation["name"],
                    'avatar_file_name' => $invitation["avatar_file_name"],
                    'rsvp_status' => $invitation["state"],
                    'to_identity_time_zone' => $userprofile['timezone'] === '' ? $cross['timezone'] : $userprofile['timezone'],
                    'by_identity' => $by_identity,
                    'to_identity' => $to_identity,
                    'invitations' => $allinvitations,
                );

                switch ($invitation['provider']) {
                    case 'facebook':
                        $invMsg = array(
                            'cross'      => array(
                                'title'           => $args['title'],
                                'description'     => $args['description'],
                                'time'            => array(
                                    'begin_at'        => $cross['begin_at'],
                                    'origin_begin_at' => $cross['origin_begin_at'],
                                    'time_type'       => $cross['time_type'],
                                    'timezone'        => $cross['timezone'],
                                ),
                                'invitations'     => array(),
                                'place'           => array(
                                    'line1' => $args['place_line1'],
                                    'line2' => $args['place_line2'],
                                ),
                                'cross_id'        => intval($args['cross_id']),
                                'cross_id_base62' => $args['cross_id_base62'],
                            ),
                            'invitation' => array(
                                'invitation_id' => intval($args['invitation_id']),
                                'token'         => $args['token'],
                                'rsvp'          => intval($args['rsvp_status']),
                                'by_identity'   => array(
                                    'id'                => intval($args['by_identity']['id']),
                                    'external_identity' => $args['by_identity']['external_identity'],
                                    'name'              => $args['by_identity']['name'],
                                    'bio'               => $args['by_identity']['bio'],
                                    'avatar_file_name'  => $args['by_identity']['avatar_file_name'],
                                    'external_username' => $args['by_identity']['external_username'],
                                    'provider'          => $args['by_identity']['provider'],
                                    'user_id'           => 0,  // @todo @Leask
                                ),
                                'to_identity'   => array(
                                    'identity_id'       => intval($args['identity_id']),
                                    'provider'          => $args['provider'],
                                    'external_identity' => $args['external_identity'],
                                    'name'              => $args['name'],
                                    'avatar_file_name'  => $args['avatar_file_name'],
                                    'external_username' => $args['to_identity']['external_username'],
                                    'bio'               => $args['to_identity']['bio'],
                                    'user_id'           => 0,  // @todo @Leask
                                    'timezone'          => $args['to_identity_time_zone'],
                                ),
                            ),
                        );
                        foreach ($args['invitations'] as $invRaw) {
                            $invItem = array(
                                'invitation_id'  => intval($invRaw['invitation_id']),
                                'rsvp'           => intval($invRaw['state']),
                                'token'          => $invRaw['token'],
                                'updated_at'     => $invRaw['updated_at'],
                                'by_identity_id' => intval($invRaw['by_identity_id']),
                                'is_host'        => intval($invRaw['identity_id']) === intval($args['host_identity_id']),
                                'identity'       => array(
                                    'identity_id'       => intval($invRaw['identity_id']),
                                    'provider'          => $invRaw['provider'],
                                    'name'              => $invRaw['name'],
                                    'avatar_file_name'  => $invRaw['avatar_file_name'],
                                    'bio'               => $invRaw['bio'],
                                    'external_username' => $invRaw['external_username'],
                                    'user_id'           => intval($invRaw['user_id']),
                                    'external_identity' => '',  // @todo @Leask
                                )
                            );
                            $invMsg['cross']['invitations'][] = $invItem;
                        }
                        $jobId = Resque::enqueue($invitation['provider'], "{$invitation['provider']}_job" , $invMsg, true);
                        break;
                    case 'email':
                    case 'twitter':
                    default:
                        $jobId = Resque::enqueue($invitation['provider'], "{$invitation['provider']}_job" , $args, true);
                }

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
        $identity_id = $_SESSION['identity_id'];

        if($identity_id >0)
        {
            $identitydata=$this->getModelByName("identity");
            $host_identity=$identitydata->getIdentityById($identity_id);
        }
        $userid=$_SESSION["userid"];
        if($userid>0)
        {
            $userData = $this->getModelByName("user");
            $user=$userData->getUser($userid);
        }
        $host_identity=humanIdentity($host_identit,$user);

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
                if(intval($invitation["by_identity_id"]) > 0) {
                    $by_identity=$identitydata->getIdentityById($invitation["by_identity_id"]);
                }
                $to_identity=$identitydata->getIdentityById($invitation["identity_id"]);
                $userprofile=$userData->getUserProfileByIdentityId($invitation["identity_id"]);

                $args = array(
                    'title' => $cross["title"],
                    'description' => $cross["description"],
                    'begin_at' => humanDateTime($cross["begin_at"],$userprofile['timezone'] === '' ? $cross['timezone'] : $userprofile['timezone']),
                    'time_type' => $cross["time_type"],
                    'place_line1' => $cross["place"]["line1"],
                    'place_line2' => $cross["place"]["line2"],
                    'cross_id' => $cross_id,
                    'cross_id_base62' => int_to_base62($cross_id),
                    'invitation_id' => $invitation["invitation_id"],
                    'token' => $invitation["token"],
                    'identity_id' => $invitation["identity_id"],
                    'host_identity_id' => $identity_id,
                    'provider' => $invitation["provider"],
                    'external_identity' => $invitation["external_identity"],
                    'name' => $invitation["name"],
                    'avatar_file_name' => $invitation["avatar_file_name"],
                    'host_identity' => $host_identity,
                    'rsvp_status' => $invitation["state"],
                    'by_identity' => $by_identity,
                    'to_identity' => $to_identity,
                    'to_identity_time_zone' => $userprofile['timezone'] === '' ? $cross['timezone'] : $userprofile['timezone'],
                    'invitations' => $invitations
                 );

                 switch ($invitation['provider']) {
                     case 'facebook':
                         $invMsg = array(
                             'cross'      => array(
                                 'title'           => $args['title'],
                                 'description'     => $args['description'],
                                 'time'            => array(
                                     'begin_at'        => $cross['begin_at'],
                                     'origin_begin_at' => $cross['origin_begin_at'],
                                     'time_type'       => $cross['time_type'],
                                     'timezone'        => $cross['timezone'],
                                 ),
                                 'invitations'     => array(),
                                 'place'           => array(
                                     'line1' => $args['place_line1'],
                                     'line2' => $args['place_line2'],
                                 ),
                                 'cross_id'        => intval($args['cross_id']),
                                 'cross_id_base62' => $args['cross_id_base62'],
                             ),
                             'invitation' => array(
                                 'invitation_id' => intval($args['invitation_id']),
                                 'token'         => $args['token'],
                                 'rsvp'          => intval($args['rsvp_status']),
                                 'by_identity'   => array(
                                     'id'                => intval($args['by_identity']['id']),
                                     'external_identity' => $args['by_identity']['external_identity'],
                                     'name'              => $args['by_identity']['name'],
                                     'bio'               => $args['by_identity']['bio'],
                                     'avatar_file_name'  => $args['by_identity']['avatar_file_name'],
                                     'external_username' => $args['by_identity']['external_username'],
                                     'provider'          => $args['by_identity']['provider'],
                                     'user_id'           => 0,  // @todo @Leask
                                 ),
                                 'to_identity'   => array(
                                     'identity_id'       => intval($args['identity_id']),
                                     'provider'          => $args['provider'],
                                     'external_identity' => $args['external_identity'],
                                     'name'              => $args['name'],
                                     'avatar_file_name'  => $args['avatar_file_name'],
                                     'external_username' => $args['to_identity']['external_username'],
                                     'bio'               => $args['to_identity']['bio'],
                                     'user_id'           => 0,  // @todo @Leask
                                     'timezone'          => $args['to_identity_time_zone'],
                                 ),

                             ),
                         );
                         foreach ($args['invitations'] as $invRaw) {
                             $invItem = array(
                                 'invitation_id'  => intval($invRaw['invitation_id']),
                                 'rsvp'           => intval($invRaw['state']),
                                 'token'          => $invRaw['token'],
                                 'updated_at'     => $invRaw['updated_at'],
                                 'by_identity_id' => intval($invRaw['by_identity_id']),
                                 'is_host'        => intval($invRaw['identity_id']) === intval($args['host_identity_id']),
                                 'identity'       => array(
                                     'identity_id'       => intval($invRaw['identity_id']),
                                     'provider'          => $invRaw['provider'],
                                     'name'              => $invRaw['name'],
                                     'avatar_file_name'  => $invRaw['avatar_file_name'],
                                     'bio'               => $invRaw['bio'],
                                     'external_username' => $invRaw['external_username'],
                                     'user_id'           => intval($invRaw['user_id']),
                                     'external_identity' => '',  // @todo @Leask
                                 )
                             );
                             $invMsg['cross']['invitations'][] = $invItem;
                         }
                         $jobId = Resque::enqueue($invitation['provider'], "{$invitation['provider']}_job" , $invMsg, true);
                         break;
                     case 'email':
                     case 'twitter':
                     default:
                         $jobId = Resque::enqueue($invitation['provider'], "{$invitation['provider']}_job" , $args, true);
                 }

                 $identities=$invitation["identities"];
                 if($identities)
                     foreach ($identities as $identity)
                         if($identity["provider"]=="iOSAPN" && $identity["status"]==3)
                         {

                            $args["identity"]=$identity;
                            $args["job_type"]="invitation";
                            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
                             //$send_apn_push_flag=true;
                         }

                 //if($send_apn_push_flag===true)
                 //{
                 //   $args["identity"]=$identity;
                 //   $args["job_type"]="invitation";
                 //   $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
                 //}

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
        $mail["cross_id"]=$cross_id;
        $mail["action"]="conversation";
        $mail["cross_id_base62"]=int_to_base62($cross_id);
        $mail["mutelink"]=$mutelink;
        //$mail["template_name"]="conversation";
        $mail["action"]="post";
        $mail["content"]=$content;

        $crossData=$this->getModelByName("x");
        $cross=$crossData->getCross($cross_id);
        $mail["title"]=$cross["title"];
        $mail["create_at"]=time();

        $identityData=$this->getModelByName("identity");
        $exfee_identity=$identityData->getIdentityById($host_identity_id);
        $userData=$this->getModelByName("user");
        $user=$userData->getUserProfileByIdentityId($host_identity_id);

        $exfee_identity=humanIdentity($exfee_identity,$user);
        //$mail["exfee_name"]=$exfee_identity["name"];
        $mail["identity"]=$exfee_identity;

        $apnargs["by_identity"]=$exfee_identity;
        $apnargs["title"]=$cross["title"];
        $apnargs["comment"]=$content;
        $apnargs["cross_id"]=$cross_id;

        $invitationdata=$this->getmodelbyname("invitation");
        $invitation_identities=$invitationdata->getinvitation_identities($cross_id);
        if($invitation_identities)
        {
            $to_identities=array();
            foreach($invitation_identities as $invitation_identity)
            {
                $identities=$invitation_identity["identities"];
                if($identities && $identityData->ifIdentitiesEqualWithIdentity($identities,$host_identity_id)==FALSE)  //ifIdentitiesEqualWithIdentity dont's send to host's other identity.
                    foreach ($identities as $identity) {
                        if (intval($identity["status"]) == 3) {
                            $muteData=$this->getmodelbyname("mute");
                            $mute=$muteData->ifIdentityMute("x",$cross_id,$identity["identity_id"]);
                            if ($mute === false && $invitation_identity["identity_id"] != $_SESSION["identity_id"]) {
                                $identity = humanidentity($identity, null);
                                if (!isset($to_identities[$identity['provider']])) {
                                    $to_identities[$identity['provider']] = array();
                                }
                                $to_identities[$identity['provider']][] = $identity;
                            }
                        }
                    }
            }
            // email
            $mail["to_identities"]=$to_identities['email'];
            $mail["to_identity_time_zone"]=$user["timezone"];
            $datetimeobj=humanDateTime($mail["create_at"],$user["timezone"]);
            $mail["create_at"]=$datetimeobj;
            $msghelper=$this->gethelperbyname("msg");
            $msghelper->sentConversationEmail($mail);
            // iOSAPN
            $apnargs["to_identities"]=$to_identities['iOSAPN'];
            $apnargs["to_identity_time_zone"]=$user["timezone"];
            $apnargs["create_at"]=$datetimeobj;
            $apnargs["job_type"]="conversation";
            $msghelper->sentApnConversation($apnargs);
            // upgrade messages object
            $msg = array(
             // 'action'                => $mail['action'],
             // 'to_identity_time_zone' => $mail['to_identity_time_zone'],
                'cross'         => array(
                    'title'           => $mail['title'],
                    'cross_id'        => $mail['cross_id'],
                    'cross_id_base62' => $mail['cross_id_base62'],
                    'link'            => $mail['link'],
                    'mutelink'        => $mail['mutelink'],
                ),
                'conversation'  => array(
                    'content'   => $mail['content'],
                    'time'      => $mail['create_at'],
                    'identity'  => array(
                        'id'                => intval($mail['identity']['id']),
                        'external_identity' => $mail['identity']['external_identity'],
                        'name'              => $mail['identity']['name'],
                        'bio'               => $mail['identity']['bio'],
                        'avatar_file_name'  => $mail['identity']['avatar_file_name'],
                        'external_username' => $mail['identity']['external_username'],
                        'provider'          => $mail['identity']['provider'],
                    )
                ),
                'to_identities' => array(),
            );
            foreach ($to_identities as $tidtgI => $tidtgItem) {
                foreach ($tidtgItem as $tidI => $tidItem) {
                    $to_identities[$tidtgI][$tidI] = array(
                        'status'            => intval($tidItem['status']),
                        'provider'          => $tidItem['provider'],
                        'external_identity' => $tidItem['external_identity'],
                        'name'              => $tidItem['name'],
                        'bio'               => $tidItem['bio'],
                        'avatar_file_name'  => $tidItem['avatar_file_name'],
                        'external_username' => $tidItem['external_username'],
                    );
                }
            }
            // twitter
            if (isset($to_identities['twitter'])) {
                $mail["to_identities"]=$to_identities['twitter'];
                $msghelper->sentTwitterConversation($msg);
            }
            // facebook
            if (isset($to_identities['facebook'])) {
                $mail["to_identities"]=$to_identities['facebook'];
                $msghelper->sentFacebookConversation($msg);
            }
        }
    }
    public function sendRSVP($cross_id,$identity_id,$state)
    {
        $crossData=$this->getModelByName("X");
        $cross=$crossData->getCross($cross_id);
        $invitationdata=$this->getmodelbyname("invitation");
        $invitation_identities=$invitationdata->getinvitation_identities($cross_id);

        $to_identities_apn=array();
        $invitation=NULL;
        foreach($invitation_identities as $invitation_identity)
        {
            if(intval($invitation_identity["identity_id"])==intval($identity_id))
                $invitation=$invitation_identity;
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
                            if($identity["provider"]=="iOSAPN" && $invitation_identity["identity_id"]!=$_SESSION["identity_id"])
                                array_push($to_identities_apn,$identity);
                        }
                    }
                }
        }
        if($invitation!=NULL && sizeof($to_identities_apn)>0)
        {
            $apnargs["invitation"]=$invitation;
            $apnargs["cross_title"]=$cross["title"];
            $apnargs["cross_id"]=$cross["id"];
            $apnargs["timestamp"]=time();
            $apnargs["to_identities"]=$to_identities_apn;
            $apnargs["job_type"]="rsvp";
            $msghelper=$this->getHelperByName("msg");
            $msghelper->sentApnRSVP($apnargs);
        }
    }
}
