<?php

class IcsActions extends ActionController {

    public function doIndex() {
        $modUser     = $this->getModelByName('User');
        $checkHelper = $this->getHelperByName('check');
        $params      = $this->params;
        if (!$params['id']) {
           // apiError(400, 'no_user_id', 'user_id must be provided');
            header("HTTP/1.0 404 Not Found");
        }
        $result = $checkHelper->isAPIAllow('user_crosses', $params['token'], ['user_id' => $uid]);
        if (!$result['check']) {
            if ($result['uid']) {
             //   apiError(403, 'not_authorized', 'You can not access the informations of this user.');
            } else {
               // apiError(401, 'invalid_auth', '');
            }
        }



        $exfeeHelper   = $this->getHelperByName('exfee');
        $exfee_id_list = $exfeeHelper->getExfeeIdByUserid(intval($uid));
        $crossHelper   = $this->getHelperByName('cross');
        $cross_list    = $crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,false,$uid);
        print_r(array("crosses"=>$cross_list));















    }

}
