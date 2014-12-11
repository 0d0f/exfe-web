<?php

require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';


class ExfeAuthModels extends DataModel {

    protected function packToken($rawToken) {
        return $rawToken ? [
            'key'        => "{$rawToken['key']}",
            'data'       => json_decode($rawToken['data'], true),
            'touched_at' => (int) strtotime($rawToken['touched_at']),
            'expire_at'  => (int) $rawToken['expire_at'],
        ] : null;
    }


    public function checkResponse($httpResponse) {
        if (!$httpResponse
         || !$httpResponse['http_code']
         ||  $httpResponse['http_code'] === 500) {
            header('HTTP/1.1 500 Internal Server Error');
            exit(1);
        }
    }


    public function create(
        $resource, $data, $expireAfterSeconds, $short = false
    ) {
        if ($resource && $data && $expireAfterSeconds) {
            $rawResult = httpkit::request(
                EXFE_AUTH_SERVER . '/v3/tokens',
                ['type' => $short ? 'short' : 'long'],
                [
                    'resource'             => json_encode($resource),
                    'data'                 => json_encode($data),
                    'expire_after_seconds' => (int) $expireAfterSeconds,
                ], false, false, 3, 3, 'json', true
            );
            $this->checkResponse($rawResult);
            if ($rawResult && $rawResult['http_code'] === 200) {
                $ipvResult = $this->packToken($rawResult['json']);
                if ($ipvResult
                 && isset($ipvResult['key'])
                 && $ipvResult['key']) {
                    return $ipvResult['key'];
                }
            }
        }
        return null;
    }


    public function keyGet($key) {
        if ($key) {
            $rawResult = httpkit::request(
                EXFE_AUTH_SERVER . "/v3/tokens/key/{$key}",
                null, null, false, false, 3, 3, 'json', true
            );
            $this->checkResponse($rawResult);
            if ($rawResult && $rawResult['http_code'] === 200) {
                return $this->packToken($rawResult['json'][0]);
            }
        }
        return null;
    }


    public function resourceGet($resource) {
        if ($resource) {
            $rawResult = httpkit::request(
                EXFE_AUTH_SERVER . '/v3/tokens/resources',
                null, json_encode($resource), false, false, 3, 3, 'json', true
            );
            $this->checkResponse($rawResult);
            if ($rawResult && $rawResult['http_code'] === 200) {
                $rtnResult = [];
                foreach ($rawResult['json'] as $rI => $rItem) {
                    $rtnResult[] = $this->packToken($rItem);
                }
                return $rtnResult;
            }
        }
        return null;
    }


    public function keyUpdate($key, $data = null, $expireAfterSeconds = null) {
        $postArgs = [];
        if ($data !== null) {
            $postArgs['data'] = json_encode($data);
        }
        if ($expireAfterSeconds !== null) {
            $postArgs['expire_after_seconds'] = $expireAfterSeconds;
        }
        if ($key && $postArgs) {
            $rawResult = httpkit::request(
                EXFE_AUTH_SERVER . "/v3/tokens/key/{$key}",
                null, $postArgs, false, false, 3, 3, 'json', true
            );
            $this->checkResponse($rawResult);
            if ($rawResult && $rawResult['http_code'] === 200) {
                return true;
            }
        }
        return false;
    }


    public function resourceUpdate($resource, $expireAfterSeconds = null) {
        $postArgs = [];
        if ($expireAfterSeconds !== null) {
            $postArgs['expire_after_seconds'] = $expireAfterSeconds;
        }
        if ($resource && $postArgs) {
            $postArgs['resource'] = json_encode($resource);
            $rawResult = httpkit::request(
                EXFE_AUTH_SERVER . '/v3/tokens/resource',
                null, $postArgs, false, false, 3, 3, 'json', true
            );
            $this->checkResponse($rawResult);
            if ($rawResult && $rawResult['http_code'] === 200) {
                return true;
            }
        }
        return false;
    }

}
