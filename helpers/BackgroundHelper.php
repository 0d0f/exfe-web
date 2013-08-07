<?php

class BackgroundHelper extends ActionController {

    protected $hlpBackground = null;


    public function __construct() {
        $this->hlpBackground = $this->getModelByName('Background');
    }


    public function getAllBackground() {
        return $this->hlpBackground->getAllBackground();
    }

}
