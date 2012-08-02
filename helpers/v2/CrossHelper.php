<?php

class CrossHelper extends ActionController {

    public function getCrossesByExfeeIdList($exfee_id_list, $time_type = null, $time_split = null,$with_updated=false,$uid = 0)
    {
        $crossData=$this->getModelByName("cross","v2");
        $crosses=$crossData->getCrossesByExfeeids($exfee_id_list, $time_type, $time_split);
        //build and return a cross object array
        $exfeeData=$this->getModelByName("exfee","v2");
        $identityData=$this->getModelByName("identity","v2");
        $conversationData=$this->getModelByName("conversation","v2");
        $cross_list=array();
        $cross_ids=array();

        if($crosses)
            foreach($crosses as $cross)
                array_push($cross_ids,$cross["id"]);
        if($with_updated==true)
            $updated_crosses=mgetUpdate($cross_ids);

        if($crosses)
            foreach($crosses as $cross)
            {
                $place=new Place($cross["place_id"],$cross["place_line1"],$cross["place_line2"],$cross["lng"],$cross["lat"],$cross["provider"],$cross["external_id"]);
                $background=new Background($cross["background"]);

                $begin_at=new CrossTime($cross['date_word'], $cross['date'], $cross['time_word'], $cross['time'], $cross["timezone"], $cross['origin_begin_at'], intval($cross['outputformat']));

                $attribute=array();
                if($cross["state"]==1)
                    $attribute["state"]="published";
                else if($cross["state"]==0)
                    $attribute["state"]="draft";

                $created_at=$cross["created_at"];

                $by_identity=$identityData->getIdentityById($cross["by_identity_id"]);

                $exfee=$exfeeData->getExfeeById(intval($cross["exfee_id"]));
                $conversation_count=$conversationData->getConversationCounter($cross["exfee_id"],$uid);
                $cross=new Cross($cross["id"],$cross["title"], $cross["description"], $attribute,$exfee, array($background),$begin_at, $place,$conversation_count);
                $cross->by_identity=$by_identity;
                $cross->created_at=$created_at." +0000";
                $relative_id=0;
                $relation="";
                $cross->setRelation($relative_id,$relation);
                $cross->updated_at=$exfee->updated_at." +0000";
                if($with_updated==true)
                {
                    $updated=json_decode($updated_crosses[$cross->id],true);
                    if($updated)
                        $cross->updated=$updated;
                }
                array_push($cross_list,$cross);
            }
        return $cross_list;
    }


    public function getCross($cross_id, $withToken = false, $withRemoved = false)
    {
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross($cross_id);
        if($cross==NULL)
            return;

        $placeData=$this->getModelByName("place","v2");
        $place=$placeData->getPlace($cross["place_id"]);

        $identityData=$this->getModelByName("identity","v2");

        $by_identity=$identityData->getIdentityById($cross["by_identity_id"]);

        $background=new Background($cross["background"]);

        $begin_at=new CrossTime($cross['date_word'], $cross['date'], $cross['time_word'], $cross['time'], $cross["timezone"], $cross['origin_begin_at'], intval($cross['outputformat']));

        $attribute=array();
        if($cross["state"]==1)
            $attribute["state"]="published";
        else if($cross["state"]==0)
            $attribute["state"]="draft";

        $exfeeData=$this->getModelByName("exfee","v2");
        $exfee=$exfeeData->getExfeeById(intval($cross["exfee_id"]), $withRemoved, $withToken);
        $created_at=$cross["created_at"];

        $cross=new Cross($cross["id"],$cross["title"], $cross["description"],$attribute,$exfee, array($background),$begin_at, $place);
        $cross->by_identity=$by_identity;
        $cross->created_at=$created_at;
        $relative_id=0;
        $relation="";

        $update_result=getUpdate($cross_id);
        //$cross->setRelation($relative_id,$relation);
        return $cross;
    }


    public function gatherCross($cross, $by_identity_id, $user_id = 0)
    {

        $place_id=0;
        if($cross->place)
        {
            $placeData=$this->getModelByName("place","v2");
            $place_id=$placeData->addPlace($cross->place);
        }

        $exfeeData=$this->getModelByName("exfee","v2");
        $exfee_id=$exfeeData->getNewExfeeId();

        $crossData=$this->getModelByName("cross","v2");
        if($exfee_id>0)
        {
            $cross_id=$crossData->addCross($cross,$place_id,$exfee_id,$by_identity_id);
            $exfeeData->addExfee($exfee_id, $cross->exfee->invitations, $by_identity_id, $user_id);
            $exfeeData->updateExfeeTime($exfee_id);
        }

        return $cross_id;
    }


    public function editCross($cross, $by_identity_id) {
        // get current cross object
        $old_cross = $this->getCross(intval($cross->id));

        $exfee_id  = intval($cross->exfee_id);
        // check exfee and update exfee
        $placeData = $this->getModelByName("place","v2");
        $crossData = $this->getModelByName("cross","v2");
        $place = $cross->place;
        if ($place && $place->type === 'Place'
         && ($place->title       !== '' || $place->description !== ''
          || $place->lng         !== 0  || $place->lat         !== 0
          || $place->provider    !== '' || $place->external_id !== 0)) {
            $place_id=$placeData->addPlace($place);
        }

        $cross_id=$crossData->addCross($cross,$place_id,$exfee_id,$by_identity_id,$old_cross);
        $exfeeData=$this->getModelByName("exfee","v2");
        $exfeeData->updateExfeeTime($exfee_id);
        return $cross_id;

    }

}

