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
            $key = "wechat_identity:{$external_id}";
            $identity = getCache($key);
            if (!$identity) {
                $external_id = splitIdentityId($external_id)[0];
                $rawIdentity = $this->libwechat->getUserInfo($external_id);
                $identity    = $this->makeIdentityBy($rawIdentity);
                setCache($key, $identity);
            }
            return $identity;
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


    public function requestXTitle($cross_id, $cross_title, $external_id) {
        setCache("wechat_user_{$external_id}_current_x_id", $cross_id, 60 * 2);
        return $this->sendTemplateMessage(
            $external_id, 'x_title_update', ['cross' => ['title' => $cross_title]]
        );
    }


    public function getCurrentX($external_id) {
        return @ (int) getCache("wechat_user_{$external_id}_current_x_id");
    }


    public function sendTemplateMessage($toUserName, $template_id, $content) {
        $templates = [
            'gh_c2e5730627a4' => [
                'x_title_update'        => 'cKExQY5C6M20Sk6dNzajAqDPkryvIgGz6nWpnlbQlj5JQjshzG_gQ0F18RsJeWH1',
                'user_location_request' => '0w_9XXPiMHmqKWgrQB-zzkujNgDgG1JRGN8j132SiNEx0HcqXu8a1G_xTLwedrmW',
                'x_join'                => 'Nb5n4d_tNHcRgWjz9gBk1Y5A3nM-roYYJdbKe5mh0BLc5k-grCSi08_n5qh4Irnj',
            ],
            'gh_8c4c8d9d14a7' => [
                'x_title_update'        => 'cEhPMpIuw87cGZKvZpWjCru_I7LW-SerUzLHlzYyy2px1ao16opH6_Qld8H96Lec',
                'user_location_request' => 'F2b3C5kpw2lDPyYlUAygr6X1STqdHclR9vKMhBxsEHXO7IgwJ-oI8gBUdhfutePU',
                'x_join'                => 'WOF8SzXspPuLjrBMD-mYbwP0Z-xNYwPgAl5Tgy5Mc7uulKulrggUU_R-W4fRZF9A',
            ],
        ];
        $ids = splitIdentityId($toUserName);
        return $this->libwechat->sendTemplateMessage(
            $ids[0], $templates[$ids[1]][$template_id], $content
        );
    }


    public function logMessage($identity_id, $message) {
        if ($identity_id && $message) {
            return $this->query(
                "INSERT INTO `wechat_posts` SET `identity_id` = {$identity_id}, `content` = '{$message}';"
            );
        }
        return false;
    }

}
