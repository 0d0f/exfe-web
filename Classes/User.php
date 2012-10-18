<?php

class User extends EFObject {

    public $name              = null;

    public $bio               = null;

    public $default_identity  = null;

    public $avatar_filename   = null;

    public $timezone          = null;

    public $identities        = null;

    public function __construct($id                = 0,
                                $name              = '',
                                $bio               = '',
                                $default_identity  = null,
                                $avatar_filename   = '',
                                $timezone          = '',
                                $identities        = array()) {
        parent::__construct($id, 'user');

        $this->name              = $name;
        $this->bio               = $bio ?: '';
        $this->default_identity  = $default_identity;
        $this->avatar_filename   = $avatar_filename;
        $this->timezone          = $timezone;
        $this->identities        = $identities;
    }

}
