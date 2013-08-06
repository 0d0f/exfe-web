<?php

class WechatHelper extends ActionController {

    protected $modWechat = null;


    public function __construct() {
        $this->modWechat = $this->getModelByName('Wechat');
    }


    public function getMenu() {
        return $this->modWechat->getMenu();
    }


    public function createMenu($menu) {
        return $this->modWechat->createMenu($menu);
    }


    public function deleteMenu() {
        return $this->modWechat->deleteMenu();
    }

}
