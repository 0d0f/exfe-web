<?php

class ExfeeHelper extends ActionController {

    public function getExfeeIdByUserid($userid, $updated_at = '') {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list = $exfeeData->getExfeeIdByUserid($userid, $updated_at);
        return $exfee_id_list;
    }


    public function getCrossIdByExfeeId($exfee_id) {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        return $exfeeData->getCrossIdByExfeeId($exfee_id);
    }

}
