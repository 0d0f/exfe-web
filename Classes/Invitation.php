<?php

class Invitation extends EFObject {

    public $identity         = null;

    public $by_identity      = null;

    public $rsvp_status      = null;

    public $via              = null;

    public $created_at       = null;

    public $updated_at       = null;

    public $token            = null;

    public $host             = null;

    public $mates            = null;


    public function __construct($id               = 0,
                                $identity         = null,
                                $by_identity      = null,
                                $rsvp_status      = '',
                                $via              = '',
                                $token            = '',
                                $created_at       = '',
                                $updated_at       = '',
                                $host             = false,
                                $mates            = 0) {
        parent::__construct($id, 'invitation');

        $updated_at             = $updated_at
                               && $updated_at !== '0000-00-00 00:00:00'
                                ? $updated_at : $created_at;

        $this->identity         = $identity;
        $this->by_identity      = $by_identity;
        $this->rsvp_status      = $rsvp_status;
        $this->via              = $via;
        $this->token            = $token;
        $this->created_at       = $created_at . ' +0000';
        $this->updated_at       = $updated_at . ' +0000';
        $this->host             = !!intval($host);
        $this->mates            = (int) $mates;
    }

}
