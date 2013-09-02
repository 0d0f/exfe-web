<?php

class QueueHelper extends ActionController {

    protected $modQueue = null;


    public function __construct() {
        $this->modQueue = $this->getModelByName('Queue');
    }


    public function fireBus(
        $recipients, $merge_key, $method, $service, $type, $ontime, $data, $cstRequest = ''
    ) {
        return $this->modQueue->fireBus(
            $recipients, $merge_key, $method, $service, $type, $ontime, $data, $cstRequest
        );
    }


    public function despatchInvitation($cross, $to_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchInvitation($cross, $to_exfee, $by_user_id, $by_identity_id);
    }


    public function despatchPreview($cross, $to_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchPreview($cross, $to_exfee, $by_user_id, $by_identity_id);
    }


    public function despatchUpdate($cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchUpdate(
            $cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id
        );
    }


    public function despatchJoin($cross, $to_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchJoin(
            $cross, $to_exfee, $by_user_id, $by_identity_id
        );
    }


    public function despatchRemind($cross, $to_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchRemind(
            $cross, $to_exfee, $by_user_id, $by_identity_id
        );
    }


    public function updateFriends($identity, $oauth_info) {
        return $this->modQueue->updateFriends($identity, $oauth_info);
    }


    public function updateIdentity($identity, $oauth_info) {
        return $this->modQueue->updateIdentity($identity, $oauth_info);
    }

}
