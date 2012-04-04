<?php
class User extends EFObject {
    
    public $name              = null;

    public $bio               = null;

    public $default_identity  = null;

    public $avatar_filename   = null;

    public $avatar_updated_at = null;

    public $timezone          = null;

    public $identities        = null;

    public function __construct($id                = null,
                                $name              = null,
                                $bio               = null,
                                $default_identity  = null,
                                $avatar_filename   = null,
                                $avatar_updated_at = null,
                                $timezone          = null,
                                $identities        = null) {
        parent::__construct();
        $this->type              = 'exfee';
        $this->identities        = array();

        $this->id                = intval($id);
        $this->name              = $name;
        $this->bio               = $bio;
        $this->default_identity  = $default_identity;
        $this->avatar_filename   = $avatar_filename;
        $this->avatar_updated_at = $avatar_updated_at;
        $this->timezone          = $timezone;
        $this->identities        = $identities;
    }

}
