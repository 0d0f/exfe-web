<?php

class PlaceModels extends DataModel {

    public function savePlace($place, $placeId = null) {
        $place_line1 = mysql_real_escape_string($place['line1']);
        $place_line2 = mysql_real_escape_string($place['line2']);
        $provider    = mysql_real_escape_string($place['provider']);
        $external_id = mysql_real_escape_string($place['external_id']);
        $time        = time();
        if ($place['lng'] !== '' && $place['lat'] !== '') {
            $lngKey = ', `lng`';
            $latKey = ', `lat`';
            $lng    = ', ' . (float) $place['lng'];
            $lat    = ', ' . (float) $place['lat'];
            $lctStr = ', `lng` = ' . (float) $place['lng']
                    . ', `lat` = ' . (float) $place['lat'];
        } else {
            $lngKey = $latKey = $lng = $lat = $lctStr = '';
        }

        if ($placeId === null) {
            $sql = "INSERT INTO `places` (`place_line1`, `place_line2`, `provider`,
                    `external_id`{$lngKey}{$latKey}, `created_at`, `updated_at`)
                    values ('{$place_line1}', '{$place_line2}', '{$provider}',
                    '{$external_id}'{$lng}{$lat}, FROM_UNIXTIME({$time}),
                    FROM_UNIXTIME({$time}));";
            $result = $this->query($sql);
            return intval($result["insert_id"]) > 0
                 ? intval($result["insert_id"]) : false;
        } else {
            $sql = "UPDATE `places` SET
                    `place_line1` = '{$place_line1}',
                    `place_line2` = '{$place_line2}',
                    `provider`    = '{$provider}',
                    `external_id` = '{$external_id}'
                    {$lctStr},
                    `updated_at`  = FROM_UNIXTIME({$time})
                    WHERE `id`    = {$placeId};";
            return $this->query($sql);
        }
    }


    public function getPlace($place_id) {
        $sql = "select place_line1 as line1, place_line2 as line2, provider, external_id, lng, lat from places where id={$place_id};";
        $place = $this->getRow($sql);
        return $place;
    }

}
