<?php
class Exfee extends EFObject {

    public $identities = null;

    public function __construct($id, $identities) {
        parent::__construct();
        $this->type       = 'exfee';
        $this->identities = array();

        $this->id         = intval($id);
        $this->identities = $identities;
    }

}
