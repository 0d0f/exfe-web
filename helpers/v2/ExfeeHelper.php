<?php

class ExfeeHelper extends ActionController {
    public function getExfeeIdByUserid($userid)
    {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list= $exfeeData->getExfeeIdByUserid($userid);
        return $exfee_id_list;
    }
    public function getUpdate($exfee_id,$updated_at)
    {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list = $exfeeData->getUpdate($exfee_id,$updated_at);
        print_r($exfee_id_list );

    }

}
