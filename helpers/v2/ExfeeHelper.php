<?php

class ExfeeHelper extends ActionController {
    public function getExfeeIdByUserid($userid)
    {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_id_list= $exfeeData->getExfeeIdByUserid($userid);
        return $exfee_id_list;
    }

}
