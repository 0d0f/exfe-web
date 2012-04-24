<?php

class ExfeeModels extends DataModel {
    
    protected $rsvp_status = array('NORESPONSE', 'ACCEPTED', 'INTERESTED', 'DECLINED', 'REMOVED', 'NOTIFICATION');
        
    
    protected function makeExfeeToken() {
        return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    }
    
    
    protected function getIndexOfRsvpStatus($rsvp_status) {
        return intval(array_search(strtoupper($rsvp_status), $this->rsvp_status));
    }
    
    
    public function getExfeeById($id, $withRemoved = false, $withToken = false) {
        // init
        $hlpIdentity = $this->getHelperByName('identity', 'v2');
        // get invitations
        $rawExfee = $this->getAll("SELECT * FROM `invitations` WHERE `cross_id` = {$id}");
        $objExfee = new Exfee($id);
        foreach ($rawExfee as $ei => $eItem) {
            $objIdentity   = $hlpIdentity->getIdentityById($eItem['identity_id']);
            $oByIdentity   = $hlpIdentity->getIdentityById($eItem['by_identity_id']);
            if (!$objIdentity || !$oByIdentity || ($eItem['state'] === 4 && !$withRemoved)) {
                continue;
            }
            $objExfee->invitations[] = new Invitation(
                $eItem['id'],
                $objIdentity,
                $oByIdentity,
                $this->rsvp_status[$eItem['state']],
                $eItem['via'],
                $withToken ? $eItem['token'] : '',
                $eItem['created_at'],
                $eItem['updated_at']
            );
        }
        return $objExfee;
    }
    
    
    public function getUserIdsByExfeeId($exfee_id) {
        $hlpUser      = $this->getHelperByName('User', 'v2');
        $identity_ids = array();
        $rawExfee     = $this->getAll("SELECT * FROM `invitations` WHERE `cross_id` = {$exfee_id}");
        if ($rawExfee) {
            foreach ($rawExfee as $ei => $eItem) {
                if ($eItem['identity_id'] && $eItem['state'] !== 4) {
                    $identity_ids[] = $eItem['identity_id'];
                }
            }
        }
        return $hlpUser->getUserIdsByIdentityIds($identity_ids);
    }
    
    
    public function addInvitationIntoExfee($invitation, $exfee_id, $by_identity_id) {
        // init
        $hlpIdentity = $this->getHelperByName('identity', 'v2');
        // adding new identity
        if (!$invitation->identity->id) {
            $invitation->identity->id = $hlpIdentity->addIdentity(
                $invitation->identity->provider,
                $invitation->identity->external_id,
                $identityDetail = array(
                    'name'              => $invitation->identity->name,
                    'external_username' => $invitation->identity->external_username,
                )
            );      
        }
        if (!$invitation->identity->id) {
            return null;
        }
        // make invitation token
        $invToken    = $this->makeExfeeToken();
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->rsvp_status);
        // insert invitation into database
        $sql = "INSERT INTO `invitations` SET
                `identity_id`      =  {$invitation->identity->id},
                `cross_id`         =  {$exfee_id},
                `state`            = '{$rsvp_status}',
                `created_at`       = NOW(),
                `updated_at`       = NOW(),
                `exfee_updated_at` = NOW(),
                `token`            = '{$invToken}',
                `by_identity_id`   =  {$by_identity_id}";
        $dbResult = $this->query($sql);
        return intval($dbResult['insert_id']);
    }
    
    
    public function updateInvitation($invitation, $by_identity_id, $updateToken = false) {
        // base check
        if (!$invitation->id || !$invitation->identity->id || $by_identity_id) {
            return null;
        }
        // make invitation token
        $sqlToken = $updateToken
                  ? (", `token` = '" . $this->makeExfeeToken() . "', `tokenexpired` = 0")
                  : '';
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->rsvp_status);
        // update database
        return $this->query(
            "UPDATE `invitations` SET
             `state`            = {$rsvp_status},
             `updated_at`       = NOW(),
             `exfee_updated_at` = NOW(),
             `by_identity_id`   = {$by_identity_id}{$sqlToken}
             WHERE `id`         = {$invitation->id}"   
        );
    }


    public function addExfee($invitations, $by_identity_id) {
        if (!is_array($invitations) || !$by_identity_id) {
            return null;
        }
        $dbResult = $this->query("INSERT INTO `exfees` SET `id` = 0, `updated_at` = NOW()");
        $exfee_id = intval($dbResult['insert_id']);
        foreach ($invitations as $iI => $iItem) {
            $this->addInvitationIntoExfee($iItem, $exfee_id, $by_identity_id);
        }
        return $exfee_id;
    }


    public function updateExfeeById($id, $invitations, $by_identity_id) {
        // get helper
        $hlpIdentity = $this->getHelperByName('identity', 'v2');
        // base check
        if (!$id || !is_array($invitations) || !$by_identity_id) {
            return null;
        }
        //
        $objExfee = $this->getExfeeById($id, true);
        foreach ($invitations as $tI => $tItem) {
            // adding new identity
            if (!$tItem->identity->id) {
                $tItem->identity->id = $hlpIdentity->addIdentity(
                    $tItem->identity->provider,
                    $tItem->identity->external_id,
                    $tItem = array(
                        'name'              => $tItem->identity->name,
                        'external_username' => $tItem->identity->external_username,
                    )
                );
            }
            // if no identity id, skip it
            if (!$tItem->identity->id) {
                continue;
            }
            // find out the existing invitation
            $exists = false;
            foreach ($objExfee->invitations as $fI => $fItem) {
                if ($tItem->identity->id === $fItem->identity->id) {
                    $exists = true;
                    // update existing invitaion
                    $tItem->id = $fItem->id;
                    // update exfee token
                    $updateToken = $this->getIndexOfRsvpStatus($fItem->rsvp_status) === 4
                                && $this->getIndexOfRsvpStatus($tItem->rsvp_status) !== 4;
                    $this->updateInvitation($tItem, $by_identity_id, $updateToken);
                    unset($objExfee->invitations[$fI]);
                }
            }
            // add new invitation if it's a new invitation
            if (!$exists) {
                $this->addInvitationIntoExfee($tItem, $id, $by_identity_id);
            }
        }
        // foreach ($objExfee->invitations as $fI => $fItem) {
        //     // mark as leaved
        //     $fItem->rsvp_status = $this->rsvp_status[4];
        //     $this->updateInvitation($fItem, $by_identity_id);
        // }
        $this->updateExfeeTime($id);
        return $id;
    }


    public function getExfeeIdByUserid($userid)
    {
        $sql="select identityid from user_identity where userid=$userid;";
        $identities=$this->getColumn($sql);

        $identities_list=implode($identities,",");
        $sql="select DISTINCT cross_id from invitations where identity_id in($identities_list) order by created_at limit 100;";
        //TODO: cross_id will be renamed to exfee_id
        $exfee_id_list=$this->getColumn($sql);
        return $exfee_id_list;
    }

    public function updateExfeeTime($exfee_id)
    {
        $sql="update exfees set updated_at=NOW() where `id`=$exfee_id;";
        $this->query($sql);
    }

    public function getUpdate($exfee_id,$updated_at)
    {
        //$sql="select id from exfees where ";
    }
}
