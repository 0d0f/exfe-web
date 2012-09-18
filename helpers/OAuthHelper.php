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

}
