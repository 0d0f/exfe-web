<?php
// @todo: 过滤需要删除的cross

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
            'user_icses', @$params['token'], ['user_id' => $uid]
        );
        if (!$result['check'] || !$result['uid']) {
            header('HTTP/1.1 401 Unauthorized');
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
        echo "BEGIN:VCALENDAR\n"
           . "VERSION:2.0\n"
           . "CALSCALE:GREGORIAN\n"
           . "METHOD:REQUEST\n\n";
        // output content
        if ($cross_list && is_array($cross_list)) {
            foreach ($cross_list as $crossItem) {
                if (($icsItem = $modIcs->makeIcs($crossItem))) {
                    echo "{$icsItem}\n";
                }
            }
        }
        // end
        echo 'END:VCALENDAR';
    }


    public function doCrosses() {
        // get models
        $modIcs      = $this->getModelByName('Ics');
        $modExfee    = $this->getModelByName('Exfee');
        $crossHelper = $this->getHelperByName('Cross');
        // check authorization
        $params      = $this->params;
        if (!($token         = dbescape(@$params['token']))
         || !($rawInvitation = $modExfee->getRawInvitationByToken($token))
         ||   $rawInvitation['state'] === 4
         || !($objCross      = $crossHelper->getCross($rawInvitation['cross_id']))) {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        // make ics
        $icsItem = $modIcs->makeIcs($objCross);
        if (!$icsItem) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // output header
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header('Content-type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"cross_{$rawInvitation['cross_id']}.ics\"");
        echo "BEGIN:VCALENDAR\n"
           . "VERSION:2.0\n"
           . "CALSCALE:GREGORIAN\n"
           . "METHOD:REQUEST\n\n";
        // output content
        echo "{$icsItem}\n";
        // end
        echo 'END:VCALENDAR';
    }

}
