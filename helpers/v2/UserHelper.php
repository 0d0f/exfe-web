<?php

class UserHelper extends ActionController {

    protected $modUser = null;


    public function __construct() {
        $this->modUser = $this->getModelByName('User', 'v2');
    }


    public function getUserIdsByIdentityIds($identity_ids) {
        return $this->modUser->getUserIdsByIdentityIds($identity_ids);
    }


    public function getMobileIdentitiesByUserId($user_id) {
        return $this->modUser->getMobileIdentitiesByUserId($user_id);
    }


    public function getUserIdentityStatus($status_index) {
        return $this->modUser->arrUserIdentityStatus[$status_index];
    }


    public function verifyIdentity($identity, $action, $user_id = 0) {
        return $this->modUser->verifyIdentity($identity, $action, $user_id);
    }

}
