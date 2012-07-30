<?php

class XHelper extends ActionController {

    public function addCrossDiffLog($cross_id, $identity_id, $old_cross, $crossobj) {
        $changed=array();
        $logdata=$this->getModelByName("log");
        if($old_cross["title"] !== $crossobj["title"])
        {
            $changed["title"]=$crossobj["title"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"title",$crossobj["title"],$old_cross["title"]);
        }
        if($old_cross["description"] !== $crossobj["description"])
        {
            $changed["description"]=$crossobj["description"];
            $logdata->addLog("identity",$identity_id,"change","cross",$cross_id,"description",$crossobj["description"],"");
        }
        if($old_cross['begin_at']  !== $crossobj['begin_at']
        || $old_cross['time_type'] !== $crossobj['time_type']
        || $old_cross['timezone']  !== $crossobj['timezone']) {
            $logdata->addLog(
                'identity', $identity_id, 'change', 'cross', $cross_id, 'begin_at', '',
                json_encode(array(
                    'begin_at'        => $changed['begin_at']        = $crossobj['begin_at'],
                    'time_type'       => $changed['time_type']       = $crossobj['time_type'],
                    'timezone'        => $changed['timezone']        = $crossobj['timezone'],
                    'origin_begin_at' => $changed['origin_begin_at'] = $crossobj['origin_begin_at']))
            );
        }
        if ($old_cross['place']['line1'] !== $crossobj['place']['line1']
         || $old_cross['place']['line2'] !== $crossobj['place']['line2']) {
            $logdata->addLog(
                'identity', $identity_id, 'change', 'cross', $cross_id, 'place',
                '', json_encode($changed['place'] = $crossobj['place'])
            );
        }
        if (sizeof($changed) === 0) {
            return FALSE;
        }

        return $changed;
    }


    public function logX($identity_id, $cross_id, $cross_title) {
        $modLog = $this->getModelByName('log');
        $modLog->addLog('identity', $identity_id, 'gather', 'cross',
                        $cross_id, '', $cross_title, '');
    }


    public function delDraft($draft_id) {
        $modXDraft = $this->getModelByName('XDraft');
        $modXDraft->delDraft($draft_id);
    }

}
