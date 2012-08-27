<?php

class DeviceModels extends DataModel {

    public function getDeviceByUdid($udid) {
        return $udid ? $this->getRow(
            "SELECT * FROM `devices` WHERE `udid` = '{$udid}'"
        ) : null;
    }


    public function updateDeviceByUdid($udid, $push_token, $name = '', $brand = '', $model = '', $os_name = '', $os_version = '') {
        return $udid && $push_token && $this->query(
            "UPDATE `devices`
             SET    `push_token`         = '{$push_token}',
                    `name`               = '{$name}',
                    `brand`              = '{$brand}',
                    `model`              = '{$model}',
                    `os_name`            = '{$os_name}',
                    `os_version`         = '{$os_version}',
                    `status`             =  1,
                    `last_connected_at`  =  NOW()
             WHERE  `udid`               = '{$udid}'"
        ) : false;
    }


    public function connectDevice($udid, $push_token, $name = '', $brand = '', $model = '', $os_name = '', $os_version = '') {
        return $udid && $push_token && $this->query(
            "INSERT INTO `devices`
             SET    `udid`               = '{$udid}',
                    `push_token`         = '{$push_token}',
                    `name`               = '{$name}',
                    `brand`              = '{$brand}',
                    `model`              = '{$model}',
                    `os_name`            = '{$os_name}',
                    `os_version`         = '{$os_version}',
                    `status`             =  1,
                    `first_connected_at` =  NOW()
                    `last_connected_at`  =  NOW()
                    `disconnected_at`    =  0";
        ) : false;
    }


    public function disconnectDevice($udid) {
        return $udid ? $this->query(
            "INSERT INTO `devices`
             SET    `status`             =  0,
                    `first_connected_at` =  NOW()
                    `last_connected_at`  =  NOW()
                    `disconnected_at`    =  0";
        ) : false;
    }


    public function regDevice($udid, $push_token, $name = '', $brand = '', $model = '', $os_name = '', $os_version = '') {
        if ($udid && $push_token) {
            $curDevice = $this->getDeviceByUdid($udid);
            if ($curDevice) {
                return $this->updateDeviceByUdid($udid, $push_token, $name, $brand, $model, $os_name, $os_version);
            }
            return $this->connectDevice($udid, $push_token, $name, $brand, $model, $os_name, $os_version);
        }
        return false;
    }

}
