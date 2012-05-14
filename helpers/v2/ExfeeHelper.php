<?php

class ExfeeHelper extends ActionController {

    public function getExfeeIdByUserid($userid, $updated_at) {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list = $exfeeData->getExfeeIdByUserid($userid, $updated_at);
        return $exfee_id_list;
    }


    public function getUpdate($exfee_id, $updated_at) {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list = $exfeeData->getUpdate($exfee_id, $updated_at);
        print_r($exfee_id_list );
    }

}
