<?php

class CrossHelper extends ActionController {

    public function getCross($cross_id)
    {
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross($cross_id);
        if($cross==NULL)
            return;

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

        $attribute=array();
        if($cross["state"]==1)
            $attribute["state"]="published";
        else if($cross["state"]==0)
            $attribute["state"]="draft";

        $exfeeData=$this->getModelByName("exfee","v2");
        $exfee=$exfeeData->getExfeeById(intval($cross["exfee_id"]));

        $cross=new Cross($cross_id,$cross["title"], $cross["description"], $attribute,$exfee, array($background),$begin_at, $place);
        
        

        $cross->by_identity=$by_identity;
        $relative_id=0;
        $relation="";
        $cross->setRelation($relative_id,$relation);

        return $cross;
    }
    public function gatherCross($cross)
    {
        $placeData=$this->getModelByName("place","v2");
        $place_id=$placeData->addPlace($cross->place);
        // create exfee
        //$exfee_id=xxx;

        $crossData=$this->getModelByName("cross","v2");
        $exfee_id=1;
        if($place_id>0 && $exfee_id>0)
            $cross_id=$crossData->addCross($cross,$place_id,$exfee_id);

        return $cross_id;
    }
    public function editCross($cross)
    {
        $exfee_id=intval($cross->exfee_id);
        // check exfee and update exfee
        $placeData=$this->getModelByName("place","v2");
        $crossData=$this->getModelByName("cross","v2");
        $place=$cross->place;
        if($place!="" && $place->type=="Place")
            $place_id=$placeData->addPlace($place);

        $cross_id=$crossData->addCross($cross,$place_id,$exfee_id);
        return $cross_id;

    }

}

