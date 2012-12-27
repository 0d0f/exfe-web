<?php

class ExfeAuthModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    protected function useTokenApi($method, $args, $short = false) {
        return $this->hlpGobus->useGobusApi(
            EXFE_AUTH_SERVER,
            $short ? 'ShortTokenManager' : 'TokenManager',
            $method, $args, true
        );
    }


    public function generateToken($resource, $data, $expireAfterSeconds, $short = false) {
        return $this->useTokenApi('Generate', [
            'resource'             => $resource,
            'data'                 => $data,
            'expire_after_seconds' => $expireAfterSeconds,
        ], $short);
    }


    public function getToken($token, $short = false) {
        return $this->useTokenApi('Get', $token, $short);
    }


    public function findToken($resource, $short = false) {
        return $this->useTokenApi('Find', $resource, $short);
    }


    public function updateToken($token, $data, $short = false) {
        return $this->useTokenApi('Update', [
            'Token'    => $token,
            'Data'     => $data,
        ], $short);
    }


    public function verifyToken($token, $resource, $short = false) {
        return $this->useTokenApi('Verify', [
            'token'    => $token,
            'resource' => $resource,
        ], $short);
    }


    public function refreshToken($token, $expireAfterSeconds, $short = false) {
        return $this->useTokenApi('Refresh', [
            'token'                => $token,
            'expire_after_seconds' => $expireAfterSeconds,
        ], $short);
    }


    public function expireToken($token, $short = false) {
        return $this->useTokenApi('Expire', $token, $short);
    }


    public function expireAllTokens($resource, $short = false) {
        return $this->useTokenApi('ExpireResource', $resource, $short);
    }

}
