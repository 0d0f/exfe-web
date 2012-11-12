<?php

class QueueHelper extends ActionController {

    protected $modQueue = null;


    public function __construct() {
        $this->modQueue = $this->getModelByName('Queue');
    }


    public function despatchInvitation($cross, $by_user_id) {
        return $this->modQueue->despatchInvitation($cross, $by_user_id);
    }


    public function despatchSummary($cross, $old_cross, $del_exfee, $by_user_id) {
        return $this->modQueue->despatchSummary(
            $cross, $old_cross, $del_exfee, $by_user_id
        );
    }

}
