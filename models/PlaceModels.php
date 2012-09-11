<?php

class PlaceModels extends DataModel {

    public function getPlace($place_id) {
        $sql    = "SELECT * FROM `places` WHERE `id` = {$place_id}";
        $result = $this->getRow($sql);
        return new Place(
            $place_id,
            $result['place_line1'],
            $result['place_line2'],
            $result['lng'],
            $result['lat'],
            $result['provider'],
            $result['external_id'],
            $result['created_at'],
            $result['updated_at']
        );
    }

    public function addPlace($place) {
        $place->id   = (int) $place->id;
        $title       = mysql_real_escape_string($place->title);
        $description = mysql_real_escape_string($place->description);
        $external_id = mysql_real_escape_string($place->external_id);
        $provider    = mysql_real_escape_string($place->provider);
        if ($place->id <= 0) {
            $sql = "INSERT INTO `places` (`place_line1`, `place_line2`, `provider`,
                    `external_id`, `lng`, `lat`, `created_at`, `updated_at`)
                    VALUES ('{$title}', '{$description}', '{$provider}',
                    '{$external_id}','{$place->lng}','{$place->lat}', now(), now())";
            $result = $this->query($sql);
            if (intval($result['insert_id']) > 0) {
                return intval($result['insert_id']);
            }
        } else {
            $sql = "UPDATE `places` SET
                    `place_line1` = '{$title}',
                    `place_line2` = '{$description}',
                    `provider`    = '{$provider}',
                    `external_id` = '{$external_id}',
                    `lng`         = '{$place->lng}',
                    `lat`         = '{$place->lat}',
                    `updated_at`  =  NOW()
                    WHERE `id`    =  {$place->id}";
            $result = $this->query($sql);
            if (intval($result) > 0) {
                return $place->id;
            }
        }
        return false;
    }

}
