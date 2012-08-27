<?php

class CrossesActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow("cross",$params["token"],array("cross_id"=>$params["id"]));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $crossHelper=$this->getHelperByName('cross');
        $cross=$crossHelper->getCross($params["id"]);
        if($cross===NULL)
            apiError(400,"param_error","The X you're requesting is not found.");
        apiResponse(array("cross"=>$cross));
    }


    public function doGetCrossByInvitationToken() {
        // @todo: REVOKED身份合并后状态不变
        // load models
        $hlpCheck    = $this->getHelperByName('check');
        $hlpCross    = $this->getHelperByName('cross');
        $modExfee    = $this->getModelByName('exfee');
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get signin status
        $params      = $this->params;
        $signinStat  = $hlpCheck->isAPIAllow('user_edit', trim($params['token']));
        $user_id     = $signinStat['check'] ? (int) $signinStat['uid'] : 0;
        // get invitation data
        $invToken    = trim($_POST['invitation_token']);
        $invitation  = $modExfee->getRawInvitationByToken($invToken);
        // 受邀 token 存在
        if ($invitation && $invitation['state'] !== 4) {
            // used token
            if ($invitation['valid']) {
                $modExfee->usedToken($invToken);
            }
            // get user info by invitation token
            $user_infos = $modUser->getUserIdentityInfoByIdentityId(
                $invitation['identity_id']
            );
            // get cross by token
            $result = [
                'cross'     => $hlpCross->getCross($invitation['cross_id']),
                'read_only' => true,
            ];
            // 已登录  初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // TRUE (any)   CONNECTED / REVOKED （同用户）   正常登录    M50D3
            if ($user_id
             && ((isset($user_infos['CONNECTED']) && ($inv_user_id = $user_infos['CONNECTED'][0]['user_id']))
              || (isset($user_infos['REVOKED'])   && ($inv_user_id = $user_infos['REVOKED'][0]['user_id'])))
             && $user_id === $inv_user_id) {
                $result['read_only'] = false;
                apiResponse($result);
            }
            // 已登录  初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // (any)    FALSE   (any)   只读浏览    M50D4 Sign In
            if (!$invitation['valid']) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['action'] = 'singin';
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // (any)   TRUE    (null)  浏览身份登录  M50D4 Set Up *
            if ($invitation['valid']
             && !$user_infos) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['read_only'] = false;
                $result['action'] = 'setup';
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // FALSE   TRUE    CONNECTED   正常登录    M50D3
            if (!$user_id
             && $invitation['valid']
             && isset($user_infos['CONNECTED'])) {
                $result['authorization'] = $modUser->rawSignin(
                    $user_infos['CONNECTED'][0]['user_id']
                );
                $result['read_only'] = false;
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // FALSE   TRUE    VERIFYING / RELATED / REVOKED   浏览身份登录  M50D4 Set Up *
            if (!$user_id
             && $invitation['valid']
             && (isset($user_infos['VERIFYING'])
              || isset($user_infos['RELATED'])
              || isset($user_infos['REVOKED']))) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['read_only'] = false;
                $result['action'] = 'setup';
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // TRUE    TRUE    CONNECTED（不同用户） 浏览身份登录  M50D4 Sign In
            if ($user_id
             && $invitation['valid']
             && isset($user_infos['CONNECTED'])
             && $user_id !== $user_infos['CONNECTED'][0]['user_id']) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['read_only'] = false;
                $result['action'] = 'singin';
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // TRUE    TRUE    VERIFYING / RELATED / REVOKED   浏览身份登录  M50D4 Set Up *
            if ($user_id
             && $invitation['valid']
             && (isset($user_infos['VERIFYING'])
              || isset($user_infos['RELATED'])
              || isset($user_infos['REVOKED']))) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['read_only'] = false;
                $result['action'] = 'setup';
                apiResponse($result);
            }
        }
        // 受邀 token 不存在 / 无效
        apiError(404, 'invalid_invitation_token', 'Invalid Invitation Token');
    }


    public function doGetInvitationByToken() {
        // load models
        $modExfee = $this->getModelByName('Exfee');
        // get args
        $params   = $this->params;
        if (!($cross_id = (int) $params['id'])) {
            apiError(404, 'invalid_cross_id', 'Invalid Cross Id');
        }
        if (!($token    = trim($_POST['token']))) {
            apiError(404, 'invalid_invitation_token', 'Invalid Invitation Token');
        }
        // get exfee_id
        $exfee_id   = $modExfee->getExfeeIdByCrossId($cross_id);
        // get invitation
        $invitation = $modExfee->getInvitationByExfeeIdAndToken($exfee_id, $token);
        // return
        if ($invitation) {
            apiResponse(['invitation' => $invitation]);
        }
        apiError(404, 'invitation_not_found', 'Invitation Not Found');
    }


    public function doGather() {
        $params=$this->params;
        $cross_str=@file_get_contents('php://input');
        $cross=json_decode($cross_str);
        $by_identity_id=$cross->by_identity->id;
        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow("cross_add",$params["token"],array("by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $crossHelper=$this->getHelperByName('cross');
        $cross_id=$crossHelper->gatherCross($cross, $by_identity_id, $result['uid']);

        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName('cross');
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
        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow("cross_edit",$params["token"],array("cross_id"=>$params["id"],"by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $by_identity_id = (int) $result['by_identity_id'];
        $cross->id=$params["id"];
        $cross->exfee_id=$result["exfee_id"];
        $crossHelper=$this->getHelperByName('cross');
        $msgArg = array('old_cross' => $crossHelper->getCross(intval($params["id"])), 'to_identities' => array());
        $cross_id=$crossHelper->editCross($cross,$by_identity_id);
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName('cross');
            $msgArg['cross'] = $cross = $crossHelper->getCross($cross_id, true);
            // call Gobus {
            $hlpGobus = $this->getHelperByName('gobus');
            $modUser  = $this->getModelByName('user');
            $chkMobUs = array();
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->id === $by_identity_id) {
                    $msgArg['by_identity'] = $invitation->identity;
                }
                $msgArg['to_identities'][] = $invitation->identity;
                // get mobile identities
                if ($invitation->identity->connected_user_id > 0
                && !$chkMobUs[$invitation->identity->connected_user_id]) {
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
