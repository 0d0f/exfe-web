<?php

class CrossesActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross",$params["token"],array("cross_id"=>$params["id"]));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross=$crossHelper->getCross($params["id"]);
        if($cross===NULL)
            apiError(400,"param_error","The X you're requesting is not found.");
        apiResponse(array("cross"=>$cross));
    }


    public function doGetCrossByInvitationToken() {
        // load models
        $checkHelper = $this->getHelperByName('check', 'v2');
        $modExfee    = $this->getModelByName('exfee',  'v2');
        $modUser     = $this->getModelByName('user',   'v2');
        // get signin status
        $params      = $this->params;
        $signinStat  = $checkHelper->isAPIAllow('user_edit', trim($params['token']));
        $user_id     = $signinStat['check'] ? $signinStat['uid'] : 0;
        // get invitation data
        $invitation  = $modExfee->getRawInvitationByToken(trim($_POST['invitation_token']));
        // 受邀 token 存在
        if ($invitation) {
            // 受邀 token 有效
            if ($invitation['token_used_at'] === '0000-00-00 00:00:00'
             || time() - strtotime($invitation['token_used_at']) < 60 * 60) {
                // 已登录
                if ($user_id) {
                    // 身份连接状态                                       （后台操作）登录状态 / 帐号弹出窗
                    // CONNECTED / REVOKED （同用户）                      正常登录
                    // CONNECTED / REVOKED （不同用户）                    浏览身份 / M50D5 合并或登录（REVOKED身份合并后状态不变）
                    // VERIFYING / RELATED （建新用户并连接，清除验证token） 浏览身份 / M50D5 设置或合并
                // 未登录
                } else {
                    // 身份连接状态          （后台操作）登录状态 / 帐号弹出窗
                    // CONNECTED            正常登录
                    // REVOKED              浏览身份 / M50D4 登录
                    // VERIFYING / RELATED （建新用户并连接，清除验证token）正常登录
                }
            // 受邀 token 无效
            } else {
                // 已登录 身份连接状态  （后台操作）登录状态 / 帐号弹出窗
                // TRUE  CONNECTED   （同用户） 正常登录
                //   -       -       只读浏览 / M50D4 登录
            }
        // 受邀 token 不存在
        } else {
            // apiError();
        }
    }


    public function doGather() {
        $params=$this->params;
        $cross_str=@file_get_contents('php://input');
        $cross=json_decode($cross_str);
        $by_identity_id=$cross->by_identity->id;
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross_add",$params["token"],array("by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross_id=$crossHelper->gatherCross($cross, $by_identity_id, $result['uid']);

        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $cross=$crossHelper->getCross($cross_id);
            apiResponse(array("cross"=>$cross));
        }
        else
            apiError(500,"server_error","Can't gather this Cross.");

    }


    public function doEdit() {
        $params=$this->params;
        $cross_str=@file_get_contents('php://input');
        $cross=json_decode($cross_str);
        $by_identity_id=$cross->by_identity->id;
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross_edit",$params["token"],array("cross_id"=>$params["id"],"by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $cross->id=$params["id"];
        $cross->exfee_id=$result["exfee_id"];
        $crossHelper=$this->getHelperByName("cross","v2");
        $msgArg = array('old_cross' => $crossHelper->getCross(intval($params["id"])), 'to_identities' => array());
        $cross_id=$crossHelper->editCross($cross,$by_identity_id);
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $msgArg['cross'] = $cross = $crossHelper->getCross($cross_id, true);
            // call Gobus {
            $hlpGobus = $this->getHelperByName('gobus', 'v2');
            $modUser  = $this->getModelByName('user',   'v2');
            $chkMobUs = array();
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->id === $by_identity_id) {
                    $msgArg['by_identity'] = $invitation->identity;
                }
                $msgArg['to_identities'][] = $invitation->identity;
                // get mobile identities
                if (!$chkMobUs[$invitation->identity->connected_user_id]) {
                    $mobIdentities = $modUser->getMobileIdentitiesByUserId(
                        $invitation->identity->connected_user_id
                    );
                    foreach ($mobIdentities as $mI => $mItem) {
                        $msgArg['to_identities'][] = $mItem;
                    }
                    $chkMobUs[$invitation->identity->connected_user_id] = true;
                }
            }
            $hlpGobus->send('cross', 'Update', $msgArg);
            foreach ($cross->exfee->invitations as $i => $invitation) {
                $cross->exfee->invitations[$i]->token = '';
            }
            // }
            apiResponse(array("cross"=>$cross));
        }
        else
            apiError(500,"server_error","Can't Edit this Cross.");
    }

}
