<?php

class UserHelper extends ActionController {

    protected $modUser = null;


    public function __construct() {
        $this->modUser = $this->getModelByName('User');
    }


    public function getUserIdsByIdentityIds($identity_ids, $notConnected = false) {
        return $this->modUser->getUserIdsByIdentityIds($identity_ids, $notConnected);
    }


    public function getRawUserById($id) {
        return $this->modUser->getRawUserById($id);
    }


    public function getUserIdentityStatus($status_index) {
        return $this->modUser->arrUserIdentityStatus[(int) $status_index];
    }


    public function verifyIdentity(
        $identity,
        $action,
        $user_id = 0,
        $args = null,
        $device = '',
        $device_callback = '',
        $workflow = []
    ) {
        return $this->modUser->verifyIdentity(
            $identity,
            $action,
            $user_id,
            $args,
            $device,
            $device_callback,
            $workflow
        );
    }


    public function setUserIdentityStatus($user_id, $identity_id, $status) {
        return $this->modUser->setUserIdentityStatus($user_id, $identity_id, $status);
    }


    public function getUserIdByIdentityId($identity_id) {
        return $this->modUser->getUserIdByIdentityId($identity_id);
    }


    public function rawSignin($user_id, $passwdInDb = null) {
        return $this->modUser->rawSignin($user_id, $passwdInDb);
    }


    public function addUser($password = '', $name = '', $bio = '') {
        return $this->modUser->addUser($password, $name, $bio);
    }


    public function getRawUserIdentityStatusByIdentityId($identity_id) {
        return $this->modUser->getRawUserIdentityStatusByIdentityId($identity_id);
    }


    public function getIdentityIdsByUserId($user_id) {
        return $this->modUser->getIdentityIdsByUserId($user_id);
    }

}
