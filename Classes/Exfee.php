<?php

class Exfee extends EFObject {

    public $invitations = null;

    public $items       = 0;

    public $total       = 0;

    public $accepted    = 0;


    public function __construct($id = 0, $invitations = array()) {
        parent::__construct($id, 'exfee');

        $this->invitations = $invitations;
    }


    public function summary() {
    	foreach ($this->invitations as $invI => $invItem) {
    		if ($invItem->rsvp_status === 'REMOVED'
    		 || $invItem->rsvp_status === 'NOTIFICATION') {
    			continue;
    		}
    		// @todo: 需要处理身份冲突时的 fallback。
            $this->items++;
			$this->total    += ($num = 1 + $invItem->mates);
			$this->accepted += $invItem->rsvp_status === 'ACCEPTED' ? $num : 0;
		}
    }

}
