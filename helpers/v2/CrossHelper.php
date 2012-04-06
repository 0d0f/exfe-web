<?php

class CrossHelper extends ActionController {

    public function getCross($cross_id)
    {
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross($cross_id);

        //$crossTime=new CrossTime($result["begin_at"],$result["timezone"],$result["origin_begin_at"]);
        //print_r($crossTime);

        $placeData=$this->getModelByName("place","v2");
        $place=$placeData->getPlace($cross["place_id"]);

        $identityData=$this->getModelByName("identity","v2");
        $by_identity=$identityData->getIdentityById($cross["host_id"]);
        $background=new Background($cross["background"]);
        $begin_at=new CrossTime("","","","","","","");

        $cross=new Cross($cross_id,$cross["title"], $cross["description"], $attribute, $exfee_id, array($background),$begin_at, $place);

        $cross->by_identity=$by_identity;
        $relative_id=0;
        $relation="";
        $cross->setRelation($relative_id,$relation);

        return $cross;
    }

}

