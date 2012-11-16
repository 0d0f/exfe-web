<?php

class MapModels extends DataModel {

    function getIpNumByIpAddress($ip_address) {
        $arrIp = explode('.', $ip_address);
        $intIp = 0;
        if (sizeof($arrIp) === 4) {
            foreach ($arrIp as $k => $v) {
                $intIp += (int) $v * pow(256, (int) (3 - $k));
            }
        }
        return $intIp;
    }


    public function getLocIdByIpNum($int_ip) {
        if ($int_ip) {
            $rawResult = $this->getRow(
                "SELECT `loc_id` FROM `geoip_blocks`
                 WHERE  `start_ip_num` <= {$int_ip}
                 AND    `end_ip_num`   >= {$int_ip}"
            );
            if ($rawResult && $rawResult['loc_id']) {
                return (int) $rawResult['loc_id'];
            }
        }
        return 0;
    }


    public function getLocationByLocId($loc_id) {
        if ($loc_id) {
            $rawResult = $this->getRow(
                "SELECT * FROM `geoip_locations` WHERE `loc_id` = {$loc_id}"
            );
            if ($rawResult) {
                return $rawResult;
            }
        }
        return null;
    }


    public function getLocationByIpAddress($ip_address) {
        return $this->getLocationByLocId($this->getLocIdByIpNum(
            $this->getIpNumByIpAddress($ip_address)
        ));
    }


    public function getCurrentIpAddress() {
        $ip_pattern = '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/i';
        // check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        // to check ip is pass from proxy
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // recheck
        return preg_match($ip_pattern, $ip) ? $ip : '';
    }


    public function getCurrentLocation() {
        return $this->getLocationByIpAddress($this->getCurrentIpAddress());
    }

}
