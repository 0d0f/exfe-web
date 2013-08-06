<?php

require_once dirname(dirname(__FILE__)) . '/lib/libwechat.php';


class WechatModels extends DataModel {

    public $libwechat = null;


    public function __construct() {
        $this->libwechat = new libwechat(
            WECHAT_OFFICIAL_ACCOUNT_KEY,
            WECHAT_OFFICIAL_ACCOUNT_APPID,
            WECHAT_OFFICIAL_ACCOUNT_SECRET
        );
    }


    public function valid($signature, $timestamp, $nonce) {
        return $this->libwechat->checkSignature($signature, $timestamp, $nonce);
    }


    public function unpackMessage($rawInput) {
        return $this->libwechat->unpackMessage($rawInput);
    }


    public function getIdentityBy($external_id) {
        if ($external_id) {
             $rawIdentity = $this->libwechat->getUserInfo($external_id);
             return new Identity(
                0, $rawIdentity['nickname'], '', '', 'wechat', 0,
                $rawIdentity['openid'], $rawIdentity['openid'], null, '', '', 0,
                false, strtolower($rawIdentity['language']), 'Asia/Shanghai'
            );
        }
        return null;
    }


    public function packMessage($toUserName, $content, $msgType = 'text') {
        return $this->libwechat->packMessage(
            $toUserName,
            WECHAT_OFFICIAL_ACCOUNT_ID,
            $content,
            $msgType
        );
    }


    public function getMenu() {
        return $this->libwechat->getMenu();
    }


    public function createMenu($menu) {
        return $this->libwechat->createMenu($menu);
    }


    public function deleteMenu() {
        return $this->libwechat->deleteMenu();
    }

}
