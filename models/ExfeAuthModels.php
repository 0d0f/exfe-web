<?php

class ExfeAuthModels extends DataModel {

    protected function useTokenApi($method, $args) {
        if ($method && $args) {
            $url      = EXFE_AUTH_SERVER . "/TokenManager?method={$method}";
            $objCurl  = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode($args));
            $httpBody = @json_decode(curl_exec($objCurl));
            print_r($httpBody);
            $httpCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($httpCode === 200 && $httpBody) {
                return $httpBody;
            }
        }
        return null;
    }


    public function generateToken($resource, $data, $expireAfterSeconds) {
        return $this->useTokenApi('Generate', [
            'Resource'           => $resource,
            'Data'               => $data,
            'ExpireAfterSeconds' => $expireAfterSeconds,
        ]);
    }


    public function getToken($token) {
        return $this->useTokenApi('Get', $token);
    }


    public function verifyToken($token, $resource) {
        return $this->useTokenApi('Verify', [
            'Token'    => $token,
            'Resource' => $resource,
        ]);
    }


    public function updateToken($token, $data) {
        return $this->useTokenApi('Update', [
            'Token'    => $token,
            'Data'     => $data,
        ]);
    }


    public function deleteToken($token) {
        return $this->useTokenApi('Delete', $token);
    }


    public function refreshToken($token, $expireAfterSeconds) {
        return $this->useTokenApi('Refresh', [
            'Token'              => $token,
            'ExpireAfterSeconds' => $expireAfterSeconds,
        ]);
    }


    public function expireToken($token) {
        return $this->useTokenApi('Expire', $token);
    }

}
