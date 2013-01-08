<?php

class ExfeAuthModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    protected function useTokenApi($method, $args, $short = false, $get = false, $id = '') {
        return $this->hlpGobus->useGobusApi(
            EXFE_AUTH_SERVER,
            $short ? 'shorttoken' : 'TokenManager',
            $method, $args, true, $get, $id
        );
    }


    public function generateToken($resource, $data, $expireAfterSeconds, $short = false) {
        return $this->useTokenApi($short ? '' : 'Generate', [
            'resource'             => $resource,
            'data'                 => $data,
            'expire_after_seconds' => $expireAfterSeconds,
        ], $short);
    }


    public function getToken($token, $short = false) {
        if ($short) {
            $result = $this->useTokenApi('GET', ['key' => $token], true, true);
            if ($result && is_array($result)) {
                $result = $result[0];
            }
        } else {
            $result = $this->useTokenApi('Get', $token);
        }
        return $result;
    }


    public function findToken($resource, $short = false) {
        return $short
             ? $this->useTokenApi('GET', ['resource' => $resource], true, true)
             : $this->useTokenApi('Find', $resource);
    }


    public function updateToken($token, $data, $short = false) {
        return $short
             ? $this->useTokenApi('PUT', ['data' => $data], true, false, $token)
             : $this->useTokenApi('Update', ['Token' => $token, 'Data' => $data]);
    }


    public function refreshToken($token, $expireAfterSeconds, $short = false) {
        return $short
             ? $this->useTokenApi('PUT', ['expire_after_seconds' => $expireAfterSeconds], true, false, $token)
             : $this->useTokenApi('Refresh', ['token' => $token, 'expire_after_seconds' => $expireAfterSeconds]);
    }


    public function expireToken($token, $short = false) {
        return $short
             ? $this->useTokenApi('PUT', ['expire_after_seconds' => 0], true, false, $token)
             : $this->useTokenApi('Expire', $token);
    }


    public function expireAllTokens($resource, $short = false) {
        return $short
             ? $this->useTokenApi('Expire', ['resource' => $resource, 'expire_after_seconds' => 0], true)
             : $this->useTokenApi('ExpireAll', $resource);
    }

}
