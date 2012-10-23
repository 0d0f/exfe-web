<?php

class User extends EFObject {

    public $name              = null;

    public $bio               = null;

    public $default_identity  = null;

    public $avatar_filename   = null;

    public $timezone          = null;

    public $identities        = null;

    public $devices           = null;

    public $created_at        = null;

    public $updated_at        = null;

    public function __construct($id                = 0,
                                $name              = '',
                                $bio               = '',
                                $default_identity  = null,
                                $avatar_filename   = '',
                                $timezone          = '',
                                $identities        = [],
                                $devices           = [],
                                $created_at        = '',
                                $updated_at        = '') {
        parent::__construct($id, 'user');

        $created_at              = $created_at ?:  '0000-00-00 00:00:00';
        $updated_at              = $updated_at
                                && $updated_at !== '0000-00-00 00:00:00'
                                 ? $updated_at : $created_at;

        $this->name              = $name;
        $this->bio               = $bio ?: '';
        $this->default_identity  = $default_identity;
        $this->avatar_filename   = $avatar_filename;
        $this->timezone          = $timezone ?: '';
        $this->identities        = $identities;
        $this->devices           = $devices;
        $this->created_at        = $created_at . ' +0000';
        $this->updated_at        = $updated_at . ' +0000';

    }

}
