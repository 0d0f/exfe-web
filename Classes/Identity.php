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

    public $order             = null;


    static function parseEmail($email) {
        $email = trim($email);
        if (preg_match('/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/', $email)) {
            $name  = preg_replace('/^[\s\"\']*|[\s\"\']*$/', '', preg_replace('/^([^<]*).*$/', '$1', $email));
            $email = trim(preg_replace('/^.*<([^<^>]*).*>$/', '$1', $email));
        } else if (preg_match('/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $email)) {
            $name  = trim(preg_replace('/^([^@]*).*$/', '$1', $email));
        } else {
            return null;
        }
        return array('name' => $name, 'email' => $email);
    }


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
                                $updated_at        = '',
                                $order             = 0) {
        parent::__construct($id, 'identity');

        $created_at              = $created_at ?:  '0000-00-00 00:00:00';
        $updated_at              = $updated_at
                                && $updated_at !== '0000-00-00 00:00:00'
                                 ? $updated_at : $created_at;

        $this->name              = $name;
        $this->nickname          = $nickname;
        $this->bio               = $bio ?: '';
        $this->provider          = $provider;
        $this->connected_user_id = (int)    $connected_user_id;
        $this->external_id       = (string) $external_id;
        $this->external_username = $external_username;
        $this->avatar_filename   = $avatar_filename;
        $this->created_at        = $created_at . ' +0000';
        $this->updated_at        = $updated_at . ' +0000';
        $this->order             = (int)    $order;

        if (!$this->name) {
            switch ($this->provider) {
                case 'email':
                    $objParsed  = $this->parseEmail($this->external_username);
                    $this->name = $objParsed['name'];
                    break;
                case 'twitter':
                case 'facebook':
                default:
                    $this->name = $this->external_username;
            }
        }
    }

}
