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

}

