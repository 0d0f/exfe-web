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
        $postByForm  = false,
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
                if (is_array($argsPost)) {
                    foreach ($argsPost as $i => $item) {
                        if (is_array($item)) {
                            $argsPost[$i] = json_encode($item);
                        }
                    }
                }
                $argsPost = $postByForm
                          ? http_build_query($argsPost)
                          : json_encode($argsPost);
                curl_setopt($objCurl, CURLOPT_POST,       1);
                curl_setopt($objCurl, CURLOPT_POSTFIELDS, $argsPost);
            }
            $rawData     = @curl_exec($objCurl);
            $intHttpCode = @curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            $result = ['data' => $rawData, 'http_code' => "{$intHttpCode}"];
            if ($jsonDecode) {
                $result['json'] = @json_decode($rawData, $decoAsArray);
            }
            return $result;
        }
        return null;
    }


    public function fetchImageExpress($url) {
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
