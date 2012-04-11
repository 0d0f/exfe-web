<?php

class ExfeeModels extends DataModel {
    
    public function getExfeeById($id) {
        // init
        $hlpIdentity = $this->getHelperByName('identity', 'v2');
        // get invitations
        $rawExfee = $this->getAll("SELECT * FROM `invitations` WHERE `cross_id` = {$id}");
        $objExfee = new Exfee($id);
        foreach ($rawExfee as $ei => $eItem) {
            $objIdentity   = $hlpIdentity->getIdentityById($eItem['identity_id']);
            $oByIdentity   = $hlpIdentity->getIdentityById($eItem['by_identity_id']);
            if (!$objIdentity || !$oByIdentity) {
                continue;
            }
            $objExfee->invitations[] = new Invitation(
                $eItem['id'],
                $objIdentity,
                $oByIdentity,
                $eItem['state'],
                $eItem['via'],
                $eItem['created_at'],
                $eItem['updated_at']
            );
        }
        return $objExfee;
    }
    
    
    public function addInvitationIntoExfee($invitation, $exfee_id) {
        // init
        $hlpIdentity = $this->getHelperByName('identity', 'v2');
        // adding new identity
        if (!$invitation->identity->id) {
            $invitation->identity->id = $hlpIdentity->addIdentity(
                $invitation->identity->provider,
                $invitation->identity->external_id,
                $identityDetail = array(
                    'name'              => $invitation->identity->name
                    'external_username' => $invitation->identity->external_username
                )
            );      
        }
        if (!$invitation->identity->id) {
            return null;
        }
        // make invitation token
        $invToken = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
        // insert invitation into database
        $dbResult = $this->query(
            'INSERT INTO `invitations` SET'
          . '`identity_id`    = ' . $invitation->identity->id . ','
          . '`cross_id`       = ' . $exfee_id                 . ','
          . '`state`          = ' . $invitation->rsvp_status  . ','
          . '`created_at`     = NOW(),'
          . '`updated_at`     = NOW(),'
          . "`token`          = '{$invToken}',"
          . '`by_identity_id` = ' . $invitation->by_identity->id;        
        );
        return intval($dbResult['insert_id']);
    }


    public function addExfee($invitations = array()) {
        $dbResult = $this->query("INSERT INTO `exfees` SET `id` = 0");
        $id       = intval($dbResult['insert_id']);
        foreach ($invitations as $iI => $iItem) {
            $this->addInvitationIntoExfee($iItem, $exfee_id);
        }
        return $id;
    }


    public function updateExfeeById($id, $invitations = array()) {
        if (!$id) {
            return null;
        }
        $this->query("DELETE FROM `invitations` WHERE `cross_id` = {$id}");
        return $this->addExfee($id, $invitations);
    }

}
