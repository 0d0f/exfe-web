<?php

class UserHelper extends ActionController {

    protected $modUser = null;


    public function __construct() {
        $this->modUser = $this->getModelByName('User');
    }


    public function getUserIdsByIdentityIds($identity_ids, $notConnected = false) {
        return $this->modUser->getUserIdsByIdentityIds($identity_ids, $notConnected);
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


    public function setUserIdentityStatus($user_id, $identity_id, $status) {
        return $this->modUser->setUserIdentityStatus($user_id, $identity_id, $status);
    }

}
