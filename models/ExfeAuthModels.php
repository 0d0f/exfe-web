<?php

class ExfeAuthModels extends DataModel {

    protected function useTokenApi($method, $args) {
        if ($method && $args) {
            if (is_array($args)) {
                foreach ($args as $aI => $aItem) {
                    if (is_array($aItem)) {
                        $args[$aI] = json_encode($aItem);
                    }
                }
            }
            $url      = EXFE_AUTH_SERVER . "/TokenManager?method={$method}";
            $objCurl  = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode($args));
            $httpBody = @json_decode(curl_exec($objCurl), true);
            $httpCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($httpCode === 200 && $httpBody) {
                if (is_array($httpBody)) {
                    foreach ($httpBody as $hI => $hItem) {
                        $httpBody[$hI] = (
                            $rtDecode = @json_decode($hItem, true)
                        ) === null ? $hItem : $rtDecode;
                    }
                }
                return $httpBody;
            } else if (DEBUG) {
                error_log($httpBody);
            }
        }
        return null;
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
