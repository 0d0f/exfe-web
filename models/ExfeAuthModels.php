<?php

class ExfeAuthModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    protected function useTokenApi($method, $args) {
        return $this->hlpGobus->useGobusApi(
            EXFE_AUTH_SERVER, 'TokenManager', $method, $args, true
        );
    }


    public function generateToken($resource, $data, $expireAfterSeconds) {
        return $this->useTokenApi('Generate', [
            'resource'             => $resource,
            'data'                 => $data,
            'expire_after_seconds' => $expireAfterSeconds,
        ]);
    }


    public function getToken($token) {
        return $this->useTokenApi('Get', $token);
    }


    public function findToken($resource) {
        return $this->useTokenApi('Find', $resource);
    }


    public function updateToken($token, $data) {
        return $this->useTokenApi('Update', [
            'Token'    => $token,
            'Data'     => $data,
        ]);
    }


    public function verifyToken($token, $resource) {
        return $this->useTokenApi('Verify', [
            'token'    => $token,
            'resource' => $resource,
        ]);
    }


    public function deleteToken($token) {
        return $this->useTokenApi('Delete', $token);
    }


    public function refreshToken($token, $expireAfterSeconds) {
        return $this->useTokenApi('Refresh', [
            'token'                => $token,
            'expire_after_seconds' => $expireAfterSeconds,
        ]);
    }


    public function expireToken($token) {
        return $this->useTokenApi('Expire', $token);
    }


    public function expireAllTokens($resource) {
        return $this->useTokenApi('ExpireAll', $resource);
    }


}
