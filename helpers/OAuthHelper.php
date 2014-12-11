<?php

class OAuthHelper extends ActionController {

    protected $modOAuth = null;


    public function __construct() {
        $this->modOAuth = $this->getModelByName('OAuth');
    }


    public function getTwitterRequestToken($workflow = []) {
        return $this->modOAuth->getTwitterRequestToken($workflow);
    }


    public function facebookRedirect($workflow = []) {
        return $this->modOAuth->facebookRedirect($workflow);
    }


    public function dropboxRedirect($workflow = []) {
        return $this->modOAuth->dropboxRedirect($workflow);
    }


    public function flickrRedirect($workflow = []) {
        return $this->modOAuth->flickrRedirect($workflow);
    }


    public function instagramRedirect($workflow = []) {
        return $this->modOAuth->instagramRedirect($workflow);
    }


    public function googleRedirect($workflow = []) {
        return $this->modOAuth->googleRedirect($workflow);
    }


    public function refreshGoogleToken($token, $changedOnly = false) {
        return $this->modOAuth->refreshGoogleToken($token, $changedOnly);
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
