<?php

class ExfeAuthModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    protected function useTokenApi($method, $postArgs = [], $short = false, $getArgs = [], $id = '') {
        return $this->hlpGobus->useGobusApi(
            EXFE_AUTH_SERVER,
            $short ? 'shorttoken' : 'TokenManager',
            $method, $postArgs, true, $getArgs, $id
        );
    }


    public function generateToken($resource, $data, $expireAfterSeconds, $short = false) {
        $rawResult = $this->useTokenApi($short ? '' : 'Generate', [
            'resource'             => $resource,
            'data'                 => $data,
            'expire_after_seconds' => $expireAfterSeconds,
        ], $short);
        return $rawResult
             ? ($short ? $rawResult['key'] : $rawResult)
             : null;
    }


    public function getToken($token, $short = false) {
        if ($short) {
            $result = $this->useTokenApi('GET', null, true, ['key' => $token]);
            if ($result && is_array($result)) {
                $result = ['token' => $result[0]['key'],
                           'data'  => json_decode($result[0]['data'], true)];
            }
        } else {
            $result = $this->useTokenApi('Get', $token);
        }
        return $result;
    }


    public function findToken($resource, $short = false) {
        if ($short) {
            $rawResult = $this->useTokenApi('GET', null, true, ['resource' => $resource]);
            $result = [];
            foreach ($rawResult && is_array($rawResult) ? $rawResult : [] as $item) {
                $result[] = ['token' => $item['key'], 'data' => $item['data']];
            }
        } else {
            $result = $this->useTokenApi('Find', $resource);
        }
        return $result;
    }


    public function updateToken($token, $data, $short = false) {
        return $short
             ? $this->useTokenApi('PUT', ['data' => $data], true, [], $token)
             : $this->useTokenApi('Update', ['Token' => $token, 'Data' => $data]);
    }


    public function refreshToken($token, $expireAfterSeconds, $short = false) {
        return $short
             ? $this->useTokenApi('PUT', ['expire_after_seconds' => $expireAfterSeconds], true, [], $token)
             : $this->useTokenApi('Refresh', ['token' => $token, 'expire_after_seconds' => $expireAfterSeconds]);
    }


    public function expireToken($token, $short = false) {
        return $short
             ? $this->refreshToken($token, 0, true)
             : $this->useTokenApi('Expire', $token);
    }


    public function expireAllTokens($resource, $short = false) {
        return $short
             ? $this->useTokenApi('Expire', null, true, ['resource' => $resource], 'resource')
             : $this->useTokenApi('ExpireAll', $resource);
    }

}
