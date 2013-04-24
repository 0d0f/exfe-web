<?php

class Rsvp extends EFObject {

    public $identity_id    = null;

    public $rsvp_status    = null;

    public $by_identity_id = null;

    public function __construct(
        $identity_id    = 0,
        $rsvp_status    = '',
        $by_identity_id = 0
    ) {
        parent::__construct(0, 'user');

        $this->identity_id    = $identity_id;
        $this->rsvp_status    = $rsvp_status;
        $this->by_identity_id = $by_identity_id;
    }

}
