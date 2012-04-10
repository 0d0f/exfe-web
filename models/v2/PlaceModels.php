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

    
    //public $title=null;
    //public $description=null;
    //public $lng=0.0;
    //public $lat=0.0;
    //public $provider=null;
    //public $external_id=0;
    //public $created_at=null;
    //public $updated_at=null;

        if ($placeId === null) {
            $sql = "INSERT INTO `places` (`place_line1`, `place_line2`, `provider`,
                    `external_id`, `lng`, `lat`, `created_at`, `updated_at`)
                    values ('{$place->title}', '{$place->description}', '{$place->provider}',
                    '{$place->external_id}',{$place->lng},{$place->lat}, now(), now());";
            $result = $this->query($sql);
            return intval($result["insert_id"]) > 0
                 ? intval($result["insert_id"]) : false;
        } else {
            $sql = "UPDATE `places` SET
                    `place_line1` = '{$title}',
                    `place_line2` = '{$description}',
                    `provider`    = '{$provider}',
                    `external_id` = '{$external_id}'
                    `lng`={$lng}, 
                    `lat`={$lat},
                    `updated_at`  = now() 
                    WHERE `id`    = {$placeId};";
            return $this->query($sql);
        }


    }

}

