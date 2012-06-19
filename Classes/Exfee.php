<?php

class Exfee extends EFObject {

    public $invitations = null;

    public $total       = 0;

    public $accepted    = 0;


    protected function countExfee() {
    	// foreach ($this-> as $key => $value) {
    	// 	# code...
    	// }
    }


    public function __construct($id = 0, $invitations = array()) {
        parent::__construct($id, 'exfee');

        $this->invitations = $invitations;
    }

}
