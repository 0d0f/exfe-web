<?php

class Recipient extends EFObject {

    public $identity_id       = null;

    public $user_id           = null;

    public $name              = null;

    public $auth_data         = null;

    public $timezone          = null;

    public $token             = null;

    public $language          = null;

    public $provider          = null;

    public $external_id       = null;

    public $external_username = null;

    public $fallbacks         = null;


    public function __construct(
        $identity_id       = 0,
        $user_id           = 0,
        $name              = '',
        $auth_data         = '',
        $timezone          = '',
        $token             = '',
        $language          = '',
        $provider          = '',
        $external_id       = '',
        $external_username = '',
        $fallbacks         = []
    ) {
        parent::__construct(0, 'Recipient');

        $this->identity_id       = $identity_id;
        $this->user_id           = $user_id;
        $this->name              = $name;
        $this->auth_data         = $auth_data;
        $this->timezone          = $timezone;
        $this->token             = $token;
        $this->language          = $language ?: 'en_us';
        $this->provider          = $provider;
        $this->external_id       = $external_id;
        $this->external_username = $external_username;
        $this->fallbacks         = $fallbacks;

        if (preg_match('/phone/', $provider) && preg_match('/\+86.*/', $external_id)) {
            $this->language = 'zh_cn';
        }
    }

}
