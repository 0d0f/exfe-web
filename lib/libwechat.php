<?php

// Created by Leask Huang
// version 0.1
// 2013-07-30

require_once(dirname(dirname(__FILE__)) . '/lib/httpkit.php');


class libwechat {

    public $token  = '';

    public $appid  = '';

    public $secret = '';


    public function __construct($token, $appid, $secret) {
        $this->token  = $token;
        $this->appid  = $appid;
        $this->secret = $secret;
    }


    public function checkSignature($signature, $timestamp, $nonce) {
        $tmpArr = [$this->token, $timestamp, $nonce];
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        return $tmpStr === $signature;
    }


    public function unpackMessage($rawInput) {
        return simplexml_load_string(
            $rawInput, 'SimpleXMLElement', LIBXML_NOCDATA
        );
    }


    public function packMessage(
        $toUserName, $fromUserName, $content, $msgType = 'text'
    ) {
        if ($toUserName && $fromUserName && $content) {
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        </xml>";
            return sprintf(
                $textTpl, $toUserName, $fromUserName, time(), $msgType,
                self::xmlSafeStr($content)
            );
        }
        return null;
    }


    public static function xmlSafeStr($str) {
        return preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $str);
    }


    public function getAccessToken() {
        $token_key    = 'wechat_access_token';
        $access_token = getCache($token_key);
        if (!$access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/token', [
                    'grant_type' => 'client_credential',
                    'appid'      => $this->appid,
                    'secret'     => $this->secret,
                ], null, false, false, 3, 3, 'json', true
            );
            if ($result
             && $result['http_code'] === 200
             && $result['json']
             && isset($result['json']['access_token'])) {
                $access_token = $result['json']['access_token'];
                $expires_in   = (int) $result['json']['expires_in'] ?: 7200;
                setCache($token_key, $access_token, $expires_in);
            }
        }
        return $access_token;
    }


    public function getUserInfo($openid) {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/user/info', [
                    'access_token' => $access_token,
                    'openid'       => $openid,
                ], null, false, false, 3, 3, 'json', true
            );
            if ($result
             && $result['http_code'] === 200
             && $result['json']
             && @ (int) $result['json']['subscribe'] === 1) {
                return $result['json'];
            }
        }
        return null;
    }

}
