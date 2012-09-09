<?php

class PlaceModels extends DataModel {

    public function getPlace($place_id) {
        $sql="SELECT * FROM  `places` where id=$place_id;";
        $result=$this->getRow($sql);

        $title=$result["place_line1"];
        $description=$result["place_line2"];
        $lng=$result["lng"];
        $lat=$result["lat"];
        $provider=$result["provider"];
        $external_id=$result["external_id"];
        $created_at=$result["created_at"];
        $updated_at=$result["updated_at"];

        $place=new Place(intval($place_id), $title ?: '', $description ?: '', $lng ?: '', $lat ?: '', $provider ?: '', $external_id ?: '', $created_at ?: '', $updated_at ?: '');

        return $place;
    }

    public function addPlace($place) {
        $title=mysql_real_escape_string($place->title);
        $description=mysql_real_escape_string($place->description);
        $external_id=mysql_real_escape_string($place->external_id);
        $provider=mysql_real_escape_string($place->provider);
        if (intval($place->id)<=0) {
            $sql = "INSERT INTO `places` (`place_line1`, `place_line2`, `provider`,
                    `external_id`, `lng`, `lat`, `created_at`, `updated_at`)
                    values ('{$title}', '{$description}', '{$provider}',
                    '{$external_id}','{$place->lng}','{$place->lat}', now(), now());";
            $result = $this->query($sql);
            return intval($result["insert_id"]) > 0
                 ? intval($result["insert_id"]) : false;
        } else {
            $sql = "UPDATE `places` SET
                    `place_line1` = '{$title}',
                    `place_line2` = '{$description}',
                    `provider`    = '{$provider}',
                    `external_id` = '{$external_id}',
                    `lng`='{$place->lng}',
                    `lat`='{$place->lat}',
                    `updated_at`  = now()
                    WHERE `id`    = {$place->id};";
            $result=$this->query($sql);
            if(intval($result)>0)
                return $place->id;
            else
                return false;
        }


    }

}
