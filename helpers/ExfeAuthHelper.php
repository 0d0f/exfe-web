<?php

class ExfeAuthHelper extends ActionController {

    protected $modExfeAuth = null;


    public function __construct() {
        $this->modExfeAuth = $this->getModelByName('ExfeAuth');
    }


    public function generateToken($resource, $data, $expireAfterSeconds) {
        return $this->modExfeAuth->generateToken(
            $resource, $data, $expireAfterSeconds
        );
    }


    public function getToken($token) {
        return $this->modExfeAuth->getToken($token);
    }


    public function verifyToken($token, $resource) {
        return $this->modExfeAuth->verifyToken($token, $resource);
    }


    public function updateToken($token, $data) {
        return $this->modExfeAuth->updateToken($token, $data);
    }


    public function deleteToken($token) {
        return $this->modExfeAuth->deleteToken($token);
    }


    public function refreshToken($token, $expireAfterSeconds) {
        return $this->modExfeAuth->refreshToken($token, $expireAfterSeconds);
    }


    public function expireToken($token) {
        return $this->modExfeAuth->expireToken($token);
    }


}
