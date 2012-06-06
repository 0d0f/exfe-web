<?php

class RelationHelper extends ActionController {

    public function saveRelationsByXInvitation($user_id,$my_identity_id,$cross_id) {
        $invitationData=$this->getModelByName("invitation");
        $ids=$invitationData->getYESInvitationsByCrossId($cross_id,$my_identity_id);
        $relationData=$this->getModelByName("relation");

        $relationData->saveRelationsWithIds($user_id,$ids,$my_identity_id);
    }

}

