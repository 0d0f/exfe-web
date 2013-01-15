<?php

class GobusModels extends DataModel {

    public function useGobusApi($server, $api, $method, $postArgs = [], $encode_fields = false, $getArgs = [], $id = '') {
        if ($postArgs || $getArgs) {
            if ($encode_fields && is_array($postArgs)) {
                foreach ($postArgs as $aI => $aItem) {
                    if (is_array($aItem)) {
                        $postArgs[$aI] = json_encode($aItem);
                    }
                }
            }
            $strArgs = '';
            foreach ($getArgs as $aI => $aItem) {
                $strArgs .= "&{$aI}=" . (is_array($aItem) ? json_encode($aItem) : $aItem);
            }
            $url       = "{$server}/{$api}"
                       . ($id     ? "/{$id}"            : '')
                       . ($method ? "?method={$method}" : '')
                       . $strArgs;
            if (DEBUG) {
                error_log("URL: {$url}");
            }
            $objCurl   = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode($postArgs ?: ''));
            $rawResult = @curl_exec($objCurl);
            $httpCode  = @curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($rawResult !== false && $httpCode === 200) {
                $httpBody = ($rtDecode = @json_decode($rawResult, true)) === null
                          ? $rawResult : $rtDecode;
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
