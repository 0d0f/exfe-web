<?php

class GobusModels extends DataModel {

    public function useGobusApi($server, $api, $method, $args, $encode_fields = false, $get = false, $id = '') {
        if ($method && $args) {
            if ($encode_fields && is_array($args)) {
                foreach ($args as $aI => $aItem) {
                    if (is_array($aItem)) {
                        $args[$aI] = json_encode($aItem);
                    }
                }
            }
            $getArgs = '';
            if ($get) {
                foreach ($args as $aI => $aItem) {
                    $getArgs .= ($getArgs ? '&' : '?') . "{$aI}=" . json_encode($aItem);
                }
            }
            $url       = "{$server}/{$api}"
                       . ($id     ? "/{$id}"            : '')
                       . ($method ? "?method={$method}" : '')
                       . $getArgs;
            $objCurl   = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            if (!$get) {
                curl_setopt($objCurl, CURLOPT_POST, 1);
                curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode($args));
            }
            $rawResult = @curl_exec($objCurl);
            $httpCode  = @curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($rawResult !== false && $httpCode === 200) {
                $httpBody = (
                    $rtDecode = @json_decode($rawResult, true)
                ) === null ? $rawResult : $rtDecode;
                if ($httpBody) {
                    if (is_array($httpBody)) {
                        foreach ($httpBody as $hI => $hItem) {
                            $httpBody[$hI] = (
                                $rtDecode = @json_decode($hItem, true)
                            ) === null ? $hItem : $rtDecode;
                        }
                    }
                    return $httpBody;
                }
            } else if (!$httpCode) {
                header('HTTP/1.1 500 Internal Server Error');
                exit(1);
            }
        }
        return null;
    }

}
