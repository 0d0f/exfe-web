<?php

class DeviceModels extends DataModel {

    public function getDeviceByUseridAndUdid($user_id, $udid) {
        $user_id    = (int) $user_id;
        $udid       = mysql_real_escape_string($udid);
        return $user_id && $udid ? $this->getRow(
            "SELECT * FROM `devices`
             WHERE `user_id` =  {$user_id}
             AND   `udid`    = '{$udid}'"
        ) : null;
    }


    public function updateDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        $user_id    = (int) $user_id;
        $udid       = mysql_real_escape_string($udid);
        $push_token = mysql_real_escape_string($push_token);
        $os_name    = mysql_real_escape_string($os_name);
        $name       = mysql_real_escape_string($name);
        $brand      = mysql_real_escape_string($brand);
        $model      = mysql_real_escape_string($model);
        $os_version = mysql_real_escape_string($os_version);
        return $user_id && $udid && $push_token && $os_name ? $this->query(
            "UPDATE `devices`
             SET    `push_token`         = '{$push_token}',
                    `name`               = '{$name}',
                    `brand`              = '{$brand}',
                    `model`              = '{$model}',
                    `os_name`            = '{$os_name}',
                    `os_version`         = '{$os_version}',
                    `status`             =  1,
                    `last_connected_at`  =  NOW()
             WHERE  `user_id`            =  {$user_id}
             AND    `udid`               = '{$udid}'"
        ) : false;
    }


    public function connectDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        $user_id    = (int) $user_id;
        $udid       = mysql_real_escape_string($udid);
        $push_token = mysql_real_escape_string($push_token);
        $os_name    = mysql_real_escape_string($os_name);
        $name       = mysql_real_escape_string($name);
        $brand      = mysql_real_escape_string($brand);
        $model      = mysql_real_escape_string($model);
        $os_version = mysql_real_escape_string($os_version);
        return $user_id && $udid && $push_token && $os_name ? $this->query(
            "INSERT INTO `devices`
             SET    `user_id`            =  {$user_id},
                    `udid`               = '{$udid}',
                    `push_token`         = '{$push_token}',
                    `name`               = '{$name}',
                    `brand`              = '{$brand}',
                    `model`              = '{$model}',
                    `os_name`            = '{$os_name}',
                    `os_version`         = '{$os_version}',
                    `browser_version`    = '',
                    `description`        = '',
                    `status`             =  1,
                    `first_connected_at` =  NOW(),
                    `last_connected_at`  =  NOW(),
                    `disconnected_at`    =  0"
        ) : false;
    }


    public function disconnectDeviceUseridAndUdid($user_id, $udid) {
        $user_id    = (int) $user_id;
        $udid       = mysql_real_escape_string($udid);
        return $user_id && $udid ? $this->query(
            "UPDATE `devices`
             SET    `status`             =  0,
                    `disconnected_at`    =  NOW()
             WHERE  `user_id`            =  {$user_id}
             AND    `udid`               = '{$udid}'"
        ) : false;
    }


    public function regDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        return $user_id && $udid && $push_token && $os_name ? (
            $this->getDeviceByUseridAndUdid($user_id, $udid)
          ? $this->updateDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name, $brand, $model, $os_version)
          : $this->connectDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name, $brand, $model, $os_version)
        ) : false;
    }


    public function getDevicesByUserid($user_id, $mainIdentity = null) {
        $user_id    = (int) $user_id;
        $rawResult  = $user_id ? $this->getAll(
            "SELECT * FROM `devices`
             WHERE  `user_id` = {$user_id}
             AND    `status`  = 1"
        ) : [];
        if ($mainIdentity) {
            foreach ($rawResult as $rI => $rItem) {
                $rawResult[$rI] = new Identity(
                    -$rItem['id'],
                    $mainIdentity->name,
                    $rItem['name'],
                    $mainIdentity->bio,
                    $rItem['os_name'],
                    $rItem['user_id'],
                    $rItem['push_token'],
                    $rItem['udid'],
                    '',
                    $rItem['first_connected_at'],
                    $rItem['last_connected_at']
                );
            }
        }
        return $rawResult;
    }

}
