<?php

class QueueHelper extends ActionController {

    protected $modQueue = null;


    public function __construct() {
        $this->modQueue = $this->getModelByName('Queue');
    }


    public function pushToQueue($queue, $method, $data) {
        return $this->modQueue->pushToQueue($queue, $method, $data);
    }


    public function despatchInvitation($cross, $to_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchInvitation($cross, $to_exfee, $by_user_id, $by_identity_id);
    }


    public function despatchSummary($cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id) {
        return $this->modQueue->despatchSummary(
            $cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id
        );
    }


    public function updateFriends($identity, $oauth_info) {
        return $this->modQueue->updateFriends($identity, $oauth_info);
    }


    public function updateIdentity($identity, $oauth_info) {
        return $this->modQueue->updateIdentity($identity, $oauth_info);
    }

}
