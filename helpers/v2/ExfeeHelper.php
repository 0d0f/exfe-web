<?php

class ExfeeHelper extends ActionController {
    public function getExfeeIdByUserid($userid)
    {
        $exfeeData = $this->getModelByName('exfee', 'v2');
        $exfee_ids= $exfeeData->getExfeeIdByUserid($userid);
        print_r($exfee_ids);
    }

}
