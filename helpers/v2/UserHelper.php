<?php

class UserHelper extends ActionController {

    protected $modUser = null;
    
    
    public function __construct() {
        $this->modUser = $this->getModelByName('User', 'v2');
    }
    

    public function getUserIdsByIdentityIds($identity_ids) {
        return $this->modUser->getUserIdsByIdentityIds($identity_ids);
    }

}
