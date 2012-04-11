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
    
    
    public function addExfeeById($id, $exfee) {
        
    }


    public function updateExfeeById($id) {
    
    }

}
