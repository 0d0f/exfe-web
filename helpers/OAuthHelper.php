<?php

class OAuthHelper extends ActionController {

    protected $modOAuth = null;


    public function __construct() {
        $this->modOAuth = $this->getModelByName('OAuth');
    }


    public function getTwitterRequestToken($workflow = []) {
        return $this->modOAuth->getTwitterRequestToken($workflow);
    }


    public function resetSession() {
        return $this->modOAuth->resetSession();
    }


    public function getFacebookProfileByExternalUsername($external_username) {
        return $this->modOAuth->getFacebookProfileByExternalUsername(
            $external_username
        );
    }


    public function getTwitterProfileByExternalUsername($external_username) {
        return $this->modOAuth->getTwitterProfileByExternalUsername(
            $external_username
        );
    }

}
