<?php

// Created by Leask Huang
// version 0.1
// 2013-07-30

class libwechat {

    public $token = '';


    public function __construct($token) {
        $this->token = $token;
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

}
