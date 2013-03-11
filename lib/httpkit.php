<?php

// Created by Leask Huang
// version 0.1
// 2013-03-08

class httpKit {

    public static function request(
        $url,
        $argsGet     = null,
        $argsPost    = null,
        $headerOnly  = false,
        $binaryMode  = false,
        $timeout     = 3,
        $maxRedirs   = 3,
        $postType    = 'txt',
        $jsonDecode  = false,
        $decoAsArray = true
    ) {
        if ($url) {
            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL,            $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER,         $headerOnly);
            curl_setopt($objCurl, CURLOPT_BINARYTRANSFER, $binaryMode);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($objCurl, CURLOPT_MAXREDIRS,      $maxRedirs);
            curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, 1);
            if ($argsGet) {
                $url .= (strpos($url, '?') ? '&' : '?')
                      . http_build_query($argsGet);
            }
            if ($argsPost !== null) {
                switch ($postType) {
                    case 'json':
                        $argsPost = json_encode($argsPost);
                        break;
                    case 'form':
                        $argsPost = http_build_query($argsPost);
                }
                curl_setopt($objCurl, CURLOPT_POST,       1);
                curl_setopt($objCurl, CURLOPT_POSTFIELDS, $argsPost);
            }
            if (DEBUG) {
                error_log('httpKit fetching {');
                error_log("URL: {$url}");
                if ($argsPost !== null) {
                    error_log("POST: {$argsPost}");
                }
            }
            $rawData     = @curl_exec($objCurl);
            $intHttpCode = @curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            $result = ['data' => $rawData, 'http_code' => "{$intHttpCode}"];
            if ($jsonDecode) {
                $result['json'] = @json_decode($rawData, $decoAsArray);
            }
            if (DEBUG) {
                $strLog = 'RETURN: ';
                if ($binaryMode) {
                    $strLog .= $rawData ? '"[binary data]"' : '"[null]"';
                } else {
                    $strLog .= $rawData;
                }
                error_log("HTTP-CODE: {$intHttpCode}");
                error_log("{$strLog}");
                error_log('httpKit fetching }');
            }
            return $result;
        }
        return null;
    }


    public static function fetchImageExpress($url) {
        $rawResult = self::request($url, null, null, false, true);
        if ($rawResult
         && $rawResult['data']
         && $rawResult['http_code'] === '200') {
            $objImage = @imagecreatefromstring($rawResult['data']);
            if ($objImage) {
                return $objImage;
            }
        }
        return null;
    }

}
