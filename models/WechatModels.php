<?php

require_once dirname(dirname(__FILE__)) . '/lib/libwechat.php';


class WechatModels extends DataModel {

    public $libwechat = null;


    public function __construct() {
        $this->libwechat = new libwechat(WECHAT_OFFICIAL_ACCOUNT_KEY);
    }


    public function valid($signature, $timestamp, $nonce) {
        return $this->libwechat->checkSignature($signature, $timestamp, $nonce);
    }


    public function unpackMessage($rawInput) {
        return $this->libwechat->unpackMessage($rawInput);
    }


    public function getIdentityBy($external_id) {
        return $external_id ? new Identity(
            0,
            $external_id,
            $external_id,
            '',
            'wechat',
            0,
            $external_id,
            $external_id
        ) : null;
    }


    public function packMessage($toUserName, $content, $msgType = 'text') {
        return $this->libwechat->packMessage(
            $toUserName,
            WECHAT_OFFICIAL_ACCOUNT_ID,
            $content,
            $msgType
        );
    }

}
