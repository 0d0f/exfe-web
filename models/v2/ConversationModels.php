<?php

class ConversationModels extends DataModel {

    public function getConversationByExfeeId($exfee_id) {
        $sql="select * from posts where postable_id=$exfee_id and (postable_type='exfee' or postable_type='cross') order by created_at;";
        $posts=$this->getAll($sql);
        return $posts;
    }

}
