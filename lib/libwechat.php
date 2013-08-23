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
        $toUserName, $fromUserName, $content, $msgType = 'text', $FuncFlag = 0
    ) {
        if ($toUserName && $fromUserName && $content) {
            $msg = [
                'ToUserName'   => $toUserName,
                'FromUserName' => $fromUserName,
                'MsgType'      => $msgType,
                'CreateTime'   => time(),
                'FuncFlag'     => $FuncFlag,
            ];
            switch ($msgType) {
                case 'text':
                    $msg['Content']      = $content;
                    break;
                case 'music':
                    // ['Title' => '', 'Description' => '', 'MusicUrl' => '', 'HQMusicUrl' => '']
                    $msg['Music']        = $content;
                    break;
                case 'news':
                    // [['Title' => '', 'Description' => '', 'PicUrl' => '', 'Url' => '']]
                    $msg['ArticleCount'] = sizeof($content);
                    $msg['Articles']     = $content;
                    break;
                default:
                    return null;
            }
            return self::xml_encode($msg);
        }
        return null;
    }


    public function sendTemplateMessage($toUserName, $template_id, $content) {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/message/template/send', [
                    'access_token' => $access_token,
                ], [
                    'touser'      => $toUserName,
                    'template_id' => $template_id,
                    'data'        => $content,
                ], false, false, 3, 3, 'json', true
            );
            return $result
                && $result['http_code'] === 200
                && $result['json']
                && @ (int) $result['json']['errcode'] === 0;
        }
        return null;
    }


    public static function xmlSafeStr($str) {
        return '<![CDATA[' . preg_replace(
            "/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $str
        ) . ']]>';
    }


    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    public static function data_to_xml($data) {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"{$key}\"";
            $xml .= "<{$key}>";
            $xml .= (is_array($val) || is_object($val))
                  ? self::data_to_xml($val)
                  : self::xmlSafeStr($val);
            list($key,) = explode(' ', $key);
            $xml .=  "</{$key}>";
        }
        return $xml;
    }


    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
    */
    public function xml_encode(
        $data,
        $root     = 'xml',
        $item     = 'item',
        $attr     = '',
        $id       = 'id',
        $encoding = 'utf-8'
    ) {
        if (is_array($attr)) {
            $_attr = [];
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
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


    public function getMenu() {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/menu/get', [
                    'access_token' => $access_token,
                ], null, false, false, 3, 3, 'json', true
            );
            if ($result
             && $result['http_code'] === 200
             && $result['json']
             && @$result['json']['menu']) {
                return $result['json']['menu'];
            }
        }
        return null;
    }


    public function createMenu($menu) {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/menu/create', [
                    'access_token' => $access_token,
                ], $menu, false, false, 3, 3, 'json', true, true, [], '', true
            );
            if ($result
             && $result['http_code'] === 200
             && $result['json']
             && @ (int) $result['json']['errcode'] === 0) {
                return $this->getMenu();
            }
        }
        return null;
    }


    public function deleteMenu() {
        $access_token = $this->getAccessToken();
        if ($access_token) {
            $result = httpkit::request(
                'https://api.weixin.qq.com/cgi-bin/menu/delete', [
                    'access_token' => $access_token,
                ], null, false, false, 3, 3, 'json', true
            );
            if ($result
             && $result['http_code'] === 200
             && $result['json']
             && @ (int) $result['json']['errcode'] === 0) {
                return true;
            }
        }
        return null;
    }

}
