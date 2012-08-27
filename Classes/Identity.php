<?php

class Identity extends EFObject {

    public $name              = null;

    public $nickname          = null;

    public $bio               = null;

    public $provider          = null;

    public $connected_user_id = null;

    public $external_id       = null;

    public $external_username = null;

    public $avatar_filename   = null;

    public $created_at        = null;

    public $updated_at        = null;

    public function __construct($id                = 0,
                                $name              = '',
                                $nickname          = '',
                                $bio               = '',
                                $provider          = '',
                                $connected_user_id = 0,
                                $external_id       = '',
                                $external_username = '',
                                $avatar_filename   = '',
                                $created_at        = '',
                                $updated_at        = '') {
        parent::__construct($id, 'identity');

        $this->name              = $name;
        $this->nickname          = $nickname;
        $this->bio               = $bio;
        $this->provider          = $provider;
        $this->connected_user_id = (int) $connected_user_id;
        $this->external_id       = (string) $external_id;
        $this->external_username = $external_username;
        $this->avatar_filename   = $avatar_filename;
        $this->created_at        = $created_at;
        $this->updated_at        = $updated_at ?: $created_at;
    }

}
