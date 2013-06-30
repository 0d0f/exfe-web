<?php

class Invitation extends EFObject {

    public $identity                = null;

    public $invited_by              = null;

    public $by_identity             = null; // @todo: to be removed in v3

    public $updated_by              = null;

    public $rsvp_status             = null; // @todo: to be removed in v3

    public $response                = null;

    public $via                     = null;

    public $created_at              = null;

    public $updated_at              = null;

    public $token                   = null;

    public $host                    = null;

    public $mates                   = null;

    public $remark                  = null;

    public $notification_identities = [];


    public function __construct(
        $id                      = 0,
        $identity                = null,
        $invited_by              = null,
        $by_identity             = null, // @todo: $by_identity to be changed in v3
        $rsvp_status             = '',
        $via                     = '',
        $token                   = '',
        $created_at              = '',
        $updated_at              = '',
        $host                    = false,
        $mates                   = 0,
        $remark                  = [],
        $notification_identities = []
    ) {
        parent::__construct($id, 'invitation');

        $updated_at                    = $updated_at
                                      && $updated_at !== '0000-00-00 00:00:00'
                                       ? $updated_at : $created_at;

        $this->identity                = $identity;
        $this->invited_by              = $invited_by;
        $this->by_identity             = $by_identity; // @todo: to be removed in v3
        $this->updated_by              = $by_identity;
        $this->rsvp_status             = $rsvp_status; // @todo: to be removed in v3
        $this->response                = $rsvp_status;
        $this->via                     = $via;
        $this->token                   = $token;
        $this->created_at              = $created_at . ' +0000';
        $this->updated_at              = $updated_at . ' +0000';
        $this->host                    = !!intval($host);
        $this->mates                   = (int) $mates;
        $this->remark                  = is_array($remark) ? $remark : [];
        $this->notification_identities = $notification_identities;
    }

}
