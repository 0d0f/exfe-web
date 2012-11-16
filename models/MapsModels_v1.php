<?php

class MapsModels extends DataModel {

    public function getUserRegion($ipAddressIntNum) {
        $sql = "select region from ip_data where start<={$ipAddressIntNum} and end>={$ipAddressIntNum}";
        $result = $this->getRow($sql);
        return $result;
    }

}
