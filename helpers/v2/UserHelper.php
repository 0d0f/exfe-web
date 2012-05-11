<?php

class UserHelper extends ActionController {

    protected $modUser = null;


    public function __construct() {
        $this->modUser = $this->getModelByName('User', 'v2');
    }


    public function getUserIdsByIdentityIds($identity_ids) {
        return $this->modUser->getUserIdsByIdentityIds($identity_ids);
    }


    public function sendResetPasswordMail($args) {
        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        return Resque::enqueue('email', 'emailresetpassword_job', $args, true);
    }

}
