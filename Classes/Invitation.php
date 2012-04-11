<?php
class Invitation extends EFObject {

    public $identity    = null;
    
    public $by_identity = null;
    
    public $rsvp_status = null;
        
    public $via         = null;
    
    public $created_at  = null;
    
    public $updated_at  = null;


    public function __construct($id          = 0,
                                $identity    = null,
                                $by_identity = null,
                                $rsvp_status = '',
                                $via         = '',
                                $created_at  = '',
                                $updated_at  = '') {
        parent::__construct();
        $this->type        = 'invitation';

        $this->id          = intval($id);
        $this->identity    = $identity;
        $this->by_identity = $by_identity;
        $this->rsvp_status = $rsvp_status;
        $this->via         = $via;
        $this->created_at  = $created_at;
        $this->updated_at  = $updated_at;
    }

}
