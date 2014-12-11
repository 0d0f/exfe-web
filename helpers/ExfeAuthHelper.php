<?php

class ExfeAuthHelper extends ActionController {

    protected $modExfeAuth = null;


    public function __construct() {
        $this->modExfeAuth = $this->getModelByName('ExfeAuth');
    }


    public function create(
        $resource, $data, $expireAfterSeconds, $short = false
    ) {
        return $this->modExfeAuth->create(
            $resource, $data, $expireAfterSeconds, $short
        );
    }


    public function keyGet($key) {
        return $this->modExfeAuth->keyGet($key);
    }


    public function resourceGet($resource) {
        return $this->modExfeAuth->resourceGet($resource);
    }


    public function keyUpdate($key, $data = null, $expireAfterSeconds = null) {
        return $this->modExfeAuth->keyUpdate($key, $data, $expireAfterSeconds);
    }


    public function resourceUpdate($resource, $expireAfterSeconds = null) {
        return $this->modExfeAuth->resourceUpdate(
            $resource, $expireAfterSeconds
        );
    }

}
