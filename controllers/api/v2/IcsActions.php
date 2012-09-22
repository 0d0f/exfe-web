<?php

class IcsActions extends ActionController {

    public function doIndex() {
        // get models
        $modIcs      = $this->getModelByName('ics');
        $checkHelper = $this->getHelperByName('check');
        $exfeeHelper = $this->getHelperByName('exfee');
        $crossHelper = $this->getHelperByName('cross');
        // check authorization
        $params = $this->params;
        $uid    = (int) @$params['id'];
        $result = $checkHelper->isAPIAllow(
            'user_crosses', @$params['token'], ['user_id' => $uid]
        );
        if (!$result['check'] || !$result['uid']) {
            header("HTTP/1.1 401 Unauthorized");
            return;
        }
        // get crosses
        $exfee_id_list = $exfeeHelper->getExfeeIdByUserid($uid);
        $cross_list    = $crossHelper->getCrossesByExfeeIdList($exfee_id_list);
        // output header
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="crosses.ics"');
        // output content
        if ($cross_list && is_array($cross_list)) {
            foreach ($cross_list as $crossItem) {
                if (($icsItem = $modIcs->makeIcs($crossItem))) {
                    echo "{$icsItem}\n\n";
                }
            }
        }
    }

}
