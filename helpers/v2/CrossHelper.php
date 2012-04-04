<?php

class CrossHelper extends ActionController {

    public function getCross($crossid)
    {
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross($crossid);
        print_r($cross);

        //$crossTime=new CrossTime($result["begin_at"],$result["timezone"],$result["origin_begin_at"]);
        //print_r($crossTime);

        $placeData=$this->getModelByName("place","v2");
        $place=$placeData->getPlace($cross["place_id"]);

        $background=new Background($cross["background"]);
        
        $cross=new Cross($cross["title"], $cross["description"], $time="", $place, $attribute, $exfee_id, array($background));
        print_r($cross);
    }

}

