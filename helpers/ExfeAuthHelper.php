<?php

class ExfeAuthHelper extends ActionController {

    protected $modExfeAuth = null;


    public function __construct() {
        $this->modExfeAuth = $this->getModelByName('ExfeAuth');
    }


    public function generateToken($resource, $data, $expireAfterSeconds, $short = false) {
        return $this->modExfeAuth->generateToken(
            $resource, $data, $expireAfterSeconds, $short
        );
    }


    public function getToken($token, $short = false) {
        return $this->modExfeAuth->getToken($token, $short);
    }


    public function findToken($resource, $short = false) {
        return $this->modExfeAuth->findToken(json_encode($resource), $short);
    }


    public function updateToken($token, $data, $short = false) {
        return $this->modExfeAuth->updateToken($token, $data, $short);
    }


    public function verifyToken($token, $resource, $short = false) {
        return $this->modExfeAuth->verifyToken($token, $resource, $short);
    }


    public function refreshToken($token, $expireAfterSeconds, $short = false) {
        return $this->modExfeAuth->refreshToken($token, $expireAfterSeconds, $short);
    }


    public function expireToken($token, $short = false) {
        return $this->modExfeAuth->expireToken($token, $short);
    }


    public function expireAllTokens($resource, $short = false) {
        return $this->modExfeAuth->expireAllTokens($resource, $short);
    }

}
