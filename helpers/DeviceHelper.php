<?php

class DeviceHelper extends ActionController {

    protected $modDevice = null;


    public function __construct() {
        $this->modDevice = $this->getModelByName('device');
    }


    public function getDevicesByUserid($user_id, $mainIdentity = null) {
        return $this->modDevice->getDevicesByUserid($user_id, $mainIdentity);
    }

}
