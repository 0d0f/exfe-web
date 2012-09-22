<?php

class IcsActions extends ActionController {

    public function doIndex() {
        $modUser     = $this->getModelByName('User');
        $checkHelper = $this->getHelperByName('check');
        $params      = $this->params;
        if (!$params['id']) {
            apiError(400, 'no_user_id', 'user_id must be provided');
            header("HTTP/1.0 404 Not Found");
        }
        $result = $checkHelper->isAPIAllow('user_self', $params['token'], array('user_id' => $params['id']));
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You can not access the informations of this user.');
            } else {
                apiError(401, 'invalid_auth', '');
            }
        }

        if ($objUser = $modUser->getUserById($params['id'], true, 0)) {
            $passwd  = $modUser->getUserPasswdByUserId($params['id']);
            $objUser->password = !!$passwd['encrypted_password'];
            apiResponse(['user' => $objUser]);
        }
        apiError(404, 'user_not_found', 'user not found');



        $params = $this->params;
        $uid=$params["id"];
        $updated_at=$params["updated_at"];
        if($updated_at!='')
            $updated_at=date('Y-m-d H:i:s',strtotime($updated_at));

        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow("user_crosses",$params["token"],array("user_id"=>$uid));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
        }

        $exfeeHelper= $this->getHelperByName('exfee');
        $exfee_id_list=$exfeeHelper->getExfeeIdByUserid(intval($uid),$updated_at);
        $crossHelper= $this->getHelperByName('cross');
        if($updated_at!='')
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,true,$uid);
        else
            $cross_list=$crossHelper->getCrossesByExfeeIdList($exfee_id_list,null,null,false,$uid);
        apiResponse(array("crosses"=>$cross_list));












    }

}
