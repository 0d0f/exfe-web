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
        if(sizeof(explode(" ",$cross["begin_at"]))>1)
        {
            $datetime=explode(" ",$cross["begin_at"]);
            $date=$datetime[0];
            $time=$datetime[1];
        }
        $begin_at=new CrossTime("",$date,"",$time,$cross["timezone"],$cross["origin_begin_at"],"");
        ///($date_word,$date,$time_word,$time,$timezone,$origin,$originMark)

        $attribute=array();
        if($cross["state"]==1)
            $attribute["state"]="published";
        else if($cross["state"]==0)
            $attribute["state"]="draft";

        $cross=new Cross($cross_id,$cross["title"], $cross["description"], $attribute, $exfee_id, array($background),$begin_at, $place);

        $cross->by_identity=$by_identity;
        $relative_id=0;
        $relation="";
        $cross->setRelation($relative_id,$relation);

        return $cross;
    }

}

