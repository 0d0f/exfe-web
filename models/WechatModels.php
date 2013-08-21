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
            $external_id = splitIdentityId($external_id)[0];
            $rawIdentity = $this->libwechat->getUserInfo($external_id);
            return $this->makeIdentityBy($rawIdentity);
        }
        return null;
    }


    public function makeIdentityBy($rawIdentity) {
        return $rawIdentity ? new Identity(
            0, $rawIdentity['nickname'], '', '', 'wechat', 0,
            "{$rawIdentity['openid']}@" . WECHAT_OFFICIAL_ACCOUNT_ID,
            "{$rawIdentity['openid']}@" . WECHAT_OFFICIAL_ACCOUNT_ID, null, '',
            '', 0, false, strtolower($rawIdentity['language']), 'Asia/Shanghai'
        ) : null;
    }


    public function packMessage(
        $toUserName, $content, $msgType = 'text', $FuncFlag = 0
    ) {
        $toUserName = splitIdentityId($toUserName);
        return $this->libwechat->packMessage(
            $toUserName[0], $toUserName[1],
            $content, $msgType, $FuncFlag
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


    public function sendTemplateMessage($toUserName, $template_id, $content) {
        return $this->libwechat->sendTemplateMessage(
            splitIdentityId($toUserName)[0], $template_id, $content
        );
    }

}
