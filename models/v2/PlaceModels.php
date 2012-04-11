<?php

class PlaceModels extends DataModel {
    public function getPlace($place_id)
    {
        $sql="SELECT * FROM  `places` where id=$place_id;";
        $result=$this->getRow($sql);

        $title=$result["place_line1"];
        $description=$result["place_line2"];
        $lng=$result["lng"];
        $lat=$result["lat"];
        $provider=$result["provider"];
        $external_id=$result["external_id"];

        $place=new Place($title,$description,$lng,$lat,$provider,$external_id); 
        $place->id=$place_id;
        return $place;

    }
    public function addPlace($place)
    {
        if (intval($place->id)==0) {
            $sql = "INSERT INTO `places` (`place_line1`, `place_line2`, `provider`,
                    `external_id`, `lng`, `lat`, `created_at`, `updated_at`)
                    values ('{$place->title}', '{$place->description}', '{$place->provider}',
                    '{$place->external_id}',{$place->lng},{$place->lat}, now(), now());";
            $result = $this->query($sql);
            return intval($result["insert_id"]) > 0
                 ? intval($result["insert_id"]) : false;
        } else {
            $sql = "UPDATE `places` SET
                    `place_line1` = '{$place->title}',
                    `place_line2` = '{$place->description}',
                    `provider`    = '{$place->provider}',
                    `external_id` = '{$place->external_id}',
                    `lng`={$place->lng}, 
                    `lat`={$place->lat},
                    `updated_at`  = now() 
                    WHERE `id`    = {$place->id};";
            return $this->query($sql);
        }


    }

}

