<?php

class Exfee extends EFObject {

    public $invitations = null;

    public function __construct($id = 0, $invitations = array()) {
        parent::__construct();
        $this->type        = 'exfee';

        $this->id          = intval($id);
        $this->invitations = $invitations;
    }

}
