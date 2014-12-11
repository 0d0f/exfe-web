<?php

class User extends EFObject {

    public $name            = null;

    public $bio             = null;

    public $avatar          = null;

    public $avatar_filename = null;

    public $timezone        = null;

    public $locale          = null;

    public $identities      = null;

    public $devices         = null;

    public $created_at      = null;

    public $updated_at      = null;


    public function __construct(
        $id         = 0,
        $name       = '',
        $bio        = '',
        $avatar     = null,
        $timezone   = '',
        $identities = [],
        $devices    = [],
        $created_at = '',
        $updated_at = '',
        $locale     = ''
    ) {
        parent::__construct($id, 'user');

        $created_at       = $created_at ?:  '0000-00-00 00:00:00';
        $updated_at       = $updated_at
                         && $updated_at !== '0000-00-00 00:00:00'
                          ? $updated_at : $created_at;

        $this->name       = $name ?: '';
        $this->bio        = $bio  ?: '';
        $this->timezone   = $timezone ?: '';
        $this->identities = $identities;
        $this->devices    = $devices;
        $this->created_at = $created_at . ' +0000';
        $this->updated_at = $updated_at . ' +0000';
        $this->locale     = $locale   ?: '';

        if (is_array($avatar)) {
            $this->avatar_filename = $avatar['80_80'];
            $this->avatar          = $avatar;
        } else if ($avatar) {
            $this->avatar_filename = $avatar;
            $this->avatar          = [
                'original' => $avatar,
                '320_320'  => $avatar,
                '80_80'    => $avatar,
            ];
        } else {
            $this->avatar_filename = '';
            $this->avatar          = null;
        }
    }

}
