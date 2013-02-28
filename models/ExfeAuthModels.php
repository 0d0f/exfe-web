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


    protected function fixShortToken($strToken) {
        return strlen($strToken) === 3 ? "0{$strToken}" : "{$strToken}";
    }


    public function generateToken($resource, $data, $expireAfterSeconds, $short = false) {
        $rawResult = $this->useTokenApi($short ? '' : 'Generate', [
            'resource'             => $resource,
            'data'                 => $data,
            'expire_after_seconds' => $expireAfterSeconds,
        ], $short);
        return $rawResult
             ? ($short ? $this->fixShortToken($rawResult['key']) : $rawResult)
             : null;
    }


    public function getToken($token, $short = false) {
        if ($short) {
            $result = $this->useTokenApi('GET', null, true, ['key' => $token]);
            if ($result && is_array($result)) {
                $result = ['token'     => $this->fixShortToken($result[0]['key']),
                           'data'      => json_decode($result[0]['data'], true),
                           'is_expire' => false];
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
                $result[] = ['token'     => $this->fixShortToken($item['key']),
                             'data'      => $item['data'],
                             'is_expire' => false];
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
             ? $this->useTokenApi(null, 0, true, ['resource' => $resource], 'resource')
             : $this->useTokenApi('ExpireAll', json_encode($resource));
    }

}
