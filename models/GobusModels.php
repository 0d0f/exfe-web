<?php

class GobusModels extends DataModel {

    public function useGobusApi($server, $api, $method, $args) {
        if ($method && $args) {
            if (is_array($args)) {
                foreach ($args as $aI => $aItem) {
                    if (is_array($aItem)) {
                        $args[$aI] = json_encode($aItem);
                    }
                }
            }
            $url       = "{$server}/{$api}?method={$method}";
            $objCurl   = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode($args));
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
            }
            if (DEBUG) {
                error_log(@$rawResult);
                error_log(@$httpBody);
            }
        }
        return null;
    }


    public function sadfsd() {

    }

}
