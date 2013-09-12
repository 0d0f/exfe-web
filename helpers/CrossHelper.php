<?php

class CrossHelper extends ActionController {

    protected $modCross = null;


    public function __construct() {
        $this->modCross = $this->getModelByName('Cross');
    }


    public function validateCross($cross, $old_cross = null) {
        return $this->modCross->validateCross($cross, $old_cross);
    }


    public function getCrossesByExfeeids($exfee_id_list, $time_type = null, $time_split = null) {
        return $this->modCross->getCrossesByExfeeids($exfee_id_list, $time_type, $time_split);
    }


    public function getCrossesByExfeeIdList($exfee_id_list, $time_type = null, $time_split = null, $with_updated = false, $uid = 0) {
        $crosses = $this->getCrossesByExfeeids($exfee_id_list, $time_type, $time_split);
        // build and return a cross object array
        $exfeeData=$this->getModelByName("exfee");
        $identityData=$this->getModelByName("identity");
        $conversationData=$this->getModelByName("conversation");
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
                $place=new Place(
                    $cross["place_id"], $cross["place_line1"], $cross["place_line2"], $cross["lng"], $cross["lat"],
                    $cross["provider"],$cross["external_id"], $cross['place_created_at'], $cross['place_updated_at']
                );
                $background=new Background($cross["background"]);

                $begin_at=new CrossTime($cross['date_word'], $cross['date'], $cross['time_word'], $cross['time'], $cross["timezone"], $cross['origin_begin_at'], intval($cross['outputformat']));

                $attribute = [];
                $status    = ['draft', 'published', 'deleted'];
                $attribute['state']  = $status[$cross['state']];
                $attribute['closed'] = !!$cross['closed'];

                $created_at=$cross["created_at"];

                $by_identity=$identityData->getIdentityById($cross["by_identity_id"]);

                $exfee=$exfeeData->getExfeeById(intval($cross["exfee_id"]));
                $conversation_count=$conversationData->getConversationCounter($cross["exfee_id"],$uid);
                $default_widget = $cross['default_widget'];
                $cross=new Cross($cross['id'], $cross['title'], $cross['description'], $attribute, $exfee, [$background], $begin_at, $place, $conversation_count);
                $cross->default_widget = $default_widget;
                $cross->by_identity=$by_identity;
                $cross->created_at=$created_at." +0000";
                $relative_id=0;
                $relation="";
                $cross->setRelation($relative_id,$relation);
                $cross->updated_at = $exfee->updated_at;
                if($with_updated==true)
                {
                    $updated=json_decode($updated_crosses[$cross->id],true);
                    if ($updated) {
                        foreach ($updated as $uI => $uItem) {
                            $updated[$uI]['updated_at'] = "{$uItem['updated_at']} +0000";
                        }
                        $cross->updated = $updated;
                    } else {
                        $cross->updated = [];
                    }
                }
                if ($uid) {
                    $cross->touched_at = date('Y-m-d H:i:s', getCrossTouchTime($cross->id, $uid)) . ' +0000';
                }
                array_push($cross_list,$cross);
            }
        return $cross_list;
    }


    public function getCross($cross_id, $withToken = false, $withRemoved = false, $updated_at = '') {
        $crossData=$this->getModelByName("cross");
        $cross=$crossData->getCross($cross_id);
        if($cross==NULL)
            return;

        $placeData=$this->getModelByName("place");
        $place=$placeData->getPlace($cross["place_id"]);

        $identityData=$this->getModelByName("identity");

        $by_identity=$identityData->getIdentityById($cross["by_identity_id"]);

        $background=new Background($cross["background"]);

        $begin_at=new CrossTime($cross['date_word'], $cross['date'], $cross['time_word'], $cross['time'], $cross["timezone"], $cross['origin_begin_at'], intval($cross['outputformat']));

        $attribute = [];
        $status    = ['draft', 'published', 'deleted'];
        $attribute['state']  = $status[$cross['state']];
        $attribute['closed'] = !!$cross['closed'];

        $exfeeData=$this->getModelByName("exfee");
        $exfee=$exfeeData->getExfeeById(intval($cross["exfee_id"]), $withRemoved, $withToken);
        $created_at=$cross["created_at"];

        $default_widget = $cross['default_widget'];
        $cross=new Cross($cross['id'], $cross['title'], $cross['description'], $attribute, $exfee, [$background], $begin_at, $place);
        $cross->by_identity = $by_identity;
        $cross->created_at  = $created_at . ' +0000';
        $cross->updated_at  = $exfee->updated_at;
        $cross->default_widget = $default_widget;
        $relative_id=0;
        $relation="";

        $update_result = getUpdate($cross_id);
        $cross->updated = [];
        if ($update_result) {
            $updated_at = $updated_at ? strtotime($updated_at) : 0;
            foreach ($update_result as $uI => $uItem) {
                $uItem['updated_at'] .= ' +0000';
                if (($updated_at && $updated_at <= strtotime($uItem['updated_at']))
                 || !$updated_at) {
                    $cross->updated[$uI]['updated_at']  = $uItem['updated_at'];
                    $cross->updated[$uI]['by_identity'] = $identityData->getIdentityById($uItem['identity_id']);
                }
            }
        }
        return $cross;
    }


    public function gatherCross($cross, $by_identity_id, $user_id = 0) {

        $place_id=0;
        if($cross->place)
        {
            $placeData=$this->getModelByName("place");
            $place_id=$placeData->addPlace($cross->place);
        }

        $exfeeData=$this->getModelByName("exfee");
        $exfee_id=$exfeeData->getNewExfeeId();

        $crossData=$this->getModelByName("cross");
        if ($exfee_id>0) {
            $cross_id=$crossData->addCross($cross, $place_id, $exfee_id, $by_identity_id);
            $draft = isset($cross->attribute)
                  && ((isset($cross->attribute->state)   && $cross->attribute->state   === 'draft')
                   || (isset($cross->attribute['state']) && $cross->attribute['state'] === 'draft'));
            $timezone = '';
            if (@$cross->time->begin_at->timezone) {
                $timezone = $cross->time->begin_at->timezone;
            }
            $efeResult = $exfeeData->addExfee($exfee_id, $cross->exfee->invitations, $by_identity_id, $user_id, $draft, '', $timezone);
            $exfeeData->updateExfeeTime($exfee_id);
        }

        return [
            'cross_id'        => $cross_id,
            'exfee_id'        => $exfee_id,
            'over_quota'      => @$efeResult['soft_quota'],
            'over_soft_quota' => @$efeResult['soft_quota'],
            'over_hard_quota' => @$efeResult['hard_quota'],
        ];
    }


    public function editCross($cross, $by_identity_id) {
        // get current cross object
        $old_cross = $this->getCross(intval($cross->id));

        $exfee_id  = intval($cross->exfee_id);
        // check exfee and update exfee
        $placeData = $this->getModelByName('place');
        $crossData = $this->getModelByName('cross');
        $place     = $cross->place;

        if ($place
         && ($place->title    !== '' || $place->description !== ''
          || $place->lng      !== '' || $place->lat         !== ''
          || $place->provider !== '' || $place->external_id !== ''
          || $place->id       !=  0)) {
            $place_id = $placeData->addPlace($place);
        }

        $cross_rs  = $crossData->addCross($cross, $place_id, $exfee_id, $by_identity_id, $old_cross);
        $exfeeData = $this->getModelByName('exfee');
        $exfeeData->updateExfeeTime($exfee_id);
        return $cross_rs;
    }


    public function getRawCrossById($cross_id) {
        return $this->modCross->getCross($cross_id);
    }


    public function doTutorial($identity, $background = '', $title = '') {
        return $this->modCross->doTutorial($identity, $background, $title);
    }


    public function deleteCrossByCrossIdAndUserId($cross_id, $user_id, $delete = true) {
        return $this->modCross->deleteCrossByCrossIdAndUserId($cross_id, $user_id, $delete);
    }


    public function setDefaultWidget($cross_id, $default_widget) {
        return $this->modCross->setDefaultWidget($cross_id, $default_widget);
    }

}
