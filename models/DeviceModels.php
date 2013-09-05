<?php

class DeviceModels extends DataModel {

    public function getDeviceByUseridAndUdid($user_id, $udid, $os_name) {
        $user_id    = (int) $user_id;
        $udid       = dbescape($udid);
        $os_name    = dbescape($os_name);
        return $user_id && $udid && $os_name ? $this->getRow(
            "SELECT * FROM `devices`
             WHERE `user_id` =  {$user_id}
             AND   `udid`    = '{$udid}'
             AND   `os_name` = '{$os_name}'"
        ) : null;
    }


    public function updateDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        $user_id    = (int) $user_id;
        $udid       = dbescape($udid);
        $push_token = dbescape($push_token);
        $os_name    = dbescape($os_name);
        $name       = dbescape($name);
        $brand      = dbescape($brand);
        $model      = dbescape($model);
        $os_version = dbescape($os_version);
        $this->disconnectDeviceByUdidExceptUserid($udid, $os_name, $user_id);
        return $user_id && $udid && $push_token && $os_name ? $this->query(
            "UPDATE `devices`
             SET    `push_token`         = '{$push_token}',
                    `name`               = '{$name}',
                    `brand`              = '{$brand}',
                    `model`              = '{$model}',
                    `os_version`         = '{$os_version}',
                    `status`             =  1,
                    `last_connected_at`  =  NOW()
             WHERE  `user_id`            =  {$user_id}
             AND    `udid`               = '{$udid}'
             AND    `os_name`            = '{$os_name}'"
        ) : false;
    }


    public function connectDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        $user_id    = (int) $user_id;
        $udid       = dbescape($udid);
        $push_token = dbescape($push_token);
        $os_name    = dbescape($os_name);
        $name       = dbescape($name);
        $brand      = dbescape($brand);
        $model      = dbescape($model);
        $os_version = dbescape($os_version);
        $this->disconnectDeviceByUdidExceptUserid($udid, $os_name, $user_id);
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


    public function disconnectDeviceByUdidExceptUserid($udid, $os_name, $user_id) {
        $udid       = dbescape($udid);
        $os_name    = dbescape($os_name);
        $user_id    = (int) $user_id;
        return $udid && $os_name && $user_id ? $this->query(
            "UPDATE `devices`
             SET    `status`             =  0,
                    `disconnected_at`    =  NOW()
             WHERE  `status`             =  1
             AND    `udid`               = '{$udid}'
             AND    `os_name`            = '{$os_name}'
             AND    `user_id`           <>  {$user_id}"
        ) : false;
    }


    public function disconnectDeviceByUseridAndUdid($user_id, $udid, $os_name) {
        $user_id    = (int) $user_id;
        $udid       = dbescape($udid);
        $os_name    = dbescape($os_name);
        return $user_id && $udid ? $this->query(
            "UPDATE `devices`
             SET    `status`             =  0,
                    `disconnected_at`    =  NOW()
             WHERE  `user_id`            =  {$user_id}
             AND    `udid`               = '{$udid}'
             AND    `os_name`            = '{$os_name}'"
        ) : false;
    }


    public function regDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name = '', $brand = '', $model = '', $os_version = '') {
        return $user_id && $udid && $push_token && $os_name ? (
            $this->getDeviceByUseridAndUdid($user_id, $udid, $os_name)
          ? $this->updateDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name, $brand, $model, $os_version)
          : $this->connectDeviceByUseridAndUdid($user_id, $udid, $push_token, $os_name, $name, $brand, $model, $os_version)
        ) : false;
    }


    public function getDevicesByUserid($user_id, $mainIdentity = null) {
        $user_id    = (int) $user_id;
        $rawResult  = $user_id ? ($this->getAll(
            "SELECT * FROM `devices`
             WHERE  `user_id` = {$user_id}
             AND    `status`  = 1
             GROUP BY `udid`"
        ) ?: []) : [];
        foreach ($rawResult as $rI => $rItem) {
            if (time() - strtotime("{$rItem['last_connected_at']} +0000") > 60 * 60 * 24 * 90) {
                $this->disconnectDeviceByUseridAndUdid($rItem['user_id'], $rItem['udid'], $rItem['os_name']);
                unset($rawResult[$rI]);
            }
        }
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
                    $rItem['last_connected_at'],
                    0,
                    $rItem['unreachable'],
                    $mainIdentity->locale,
                    $mainIdentity->timezone
                );
            }
        } else {
            foreach ($rawResult as $rI => $rItem) {
                $rawResult[$rI] = new Device(
                    $rItem['id'],
                    $rItem['name'],
                    $rItem['brand'],
                    $rItem['model'],
                    $rItem['os_name'],
                    $rItem['os_version'],
                    $rItem['description'],
                    $rItem['status'],
                    $rItem['first_connected_at'],
                    $rItem['last_connected_at'],
                    $rItem['disconnected_at']
                );
            }
        }
        return array_values($rawResult);
    }


    public function updateDeviceReachableByUdid($udid, $os_name, $error) {
        $udid       = dbescape($udid);
        $os_name    = dbescape($os_name);
        $error      = strlen($error) ? 1 : 0;
        return $udid && $os_name ? $this->query(
            "UPDATE `devices`
             SET    `unreachable`        =  {$error}
             WHERE  `udid`               = '{$udid}'
             AND    `os_name`            = '{$os_name}'"
        ) : false;
    }

}
