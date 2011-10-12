<?php

class XHelper extends ActionController
{

    public function addCrossDiffLog($cross_id, $identity_id, $old_cross, $crossobj)
    {
        $crossData=$this->getModelByName("X");

        $logdata=$this->getModelByName("log");
        if($old_cross["title"]!=$crossobj["title"])
        {
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"title",$crossobj["title"],"");
        }
        if($old_cross["description"]!=$crossobj["desc"])
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"description",$crossobj["desc"],"");
        if($old_cross["begin_at"]!=$crossobj["start_time"])
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"begin_at",$crossobj["start_time"],"");
    }

}
