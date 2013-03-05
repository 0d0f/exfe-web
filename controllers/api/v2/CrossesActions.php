<?php

class CrossesActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $updated_at=$params["updated_at"];
        if ($updated_at) {
            $updated_at = strtotime($updated_at);
        }
        $checkHelper=$this->getHelperByName('check');
        $result=$checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params["id"]);
        if ($cross) {
            if ($cross->attribute['deleted']) {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
            if ($updated_at && $updated_at >= strtotime($cross->exfee->updated_at)) {
                apiError(304, 'Cross Not Modified.');
            }
            apiResponse(['cross' => $cross]);
        }
        apiError(400, 'param_error', "The X you're requesting is not found.");
    }


    public function doGetCrossByInvitationToken() {
        // load models
        $hlpCheck    = $this->getHelperByName('check');
        $hlpCross    = $this->getHelperByName('cross');
        $modCross    = $this->getModelByName('cross');
        $modExfeAuth = $this->getModelByName('ExfeAuth');
        $modExfee    = $this->getModelByName('exfee');
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        // get signin status
        $params      = $this->params;
        $signinStat  = $hlpCheck->isAPIAllow('user_edit', trim($params['token']));
        $user_id     = $signinStat['check'] ? (int) $signinStat['uid'] : 0;
        // get invitation data
        $acsToken    = trim(@$_POST['cross_access_token']);
        $invToken    = trim(@$_POST['invitation_token']);
        $cross_id    = (int)@$_POST['cross_id'];
        $invitation  = null;
        $usInvToken  = false;
        if ($acsToken) {
            $crossToken = $modCross->getCrossAccessToken($acsToken);
            if ($crossToken
             && $crossToken['data']
             && $crossToken['data']['token_type'] === 'cross_access_token'
             && !$crossToken['is_expire']) {
                $invitation = $modExfee->getRawInvitationByCrossIdAndIdentityId(
                    $crossToken['data']['cross_id'], $crossToken['data']['identity_id']
                );
                $modExfeAuth->refreshToken($acsToken, 60 * 60 * 24 * 7); // for 1 week
            }
        }
        if (!$invitation && $invToken) {
            if ($cross_id && strlen($invToken) === 4) {
                $exfee_id   = $modExfee->getExfeeIdByCrossId($cross_id);
                $invitation = $modExfee->getRawInvitationByExfeeIdAndToken($exfee_id, $invToken);
                $invToken   = $invitation['token'];
            } else {
                $invitation = $modExfee->getRawInvitationByToken($invToken);
            }
            $usInvToken = !!$invitation;
        }
        // 受邀 token 存在
        if ($invitation && $invitation['state'] !== 4) {
            // get cross by token
            $result = [
                'cross'     => $hlpCross->getCross($invitation['cross_id']),
                'read_only' => true,
            ];
            // check cross status
            if ($result['cross']->attribute['deleted']) {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
            // used token
            if ($invitation['valid']) {
                $modExfee->usedToken($invToken);
                if (!$acsToken) {
                    $crossAccessToken = $modCross->generateCrossAccessToken(
                        $invitation['cross_id'], $invitation['identity_id'],
                        $modUser->getUserIdByIdentityId($invitation['identity_id'])
                    );
                    if ($crossAccessToken) {
                        $result['cross_access_token'] = $crossAccessToken;
                    }
                }
            }
            // get user info by invitation token
            $user_infos = $modUser->getUserIdentityInfoByIdentityId(
                $invitation['identity_id']
            );
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
                if ($usInvToken) {
                    $result['authorization'] = $modUser->rawSignin(
                        $user_infos['CONNECTED'][0]['user_id']
                    );
                } else {
                    $result['browsing_identity'] = $modIdentity->getIdentityById(
                        $invitation['identity_id']
                    );
                }
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
        apiError(403, 'invalid_invitation_token', 'Invalid Invitation Token');
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

        if (DEBUG) {
            error_log($cross_str);
        }

        $crossHelper=$this->getHelperByName('cross');
        $chkCross = $crossHelper->validateCross($cross);
        if ($chkCross['error']) {
            apiError(400, 'cross_error', $chkCross['error'][0]);
        }
        $cross = $chkCross['cross'];
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
        $checkHelper = $this->getHelperByName('check');
        $crossHelper = $this->getHelperByName('cross');
        $result=$checkHelper->isAPIAllow("cross_edit",$params["token"], ["cross_id" => $params["id"], "by_identity_id" => $by_identity_id]);
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401, "invalid_auth", '');
            else
                apiError(403, "not_authorized", "The X you're requesting is private.");
        }

        if (DEBUG) {
            error_log($cross_str);
        }

        $by_identity_id = (int) $result['by_identity_id'];
        $cross->id=$params["id"];
        $cross->exfee_id=$result["exfee_id"];
        $old_cross = $crossHelper->getCross((int) $params['id']);
        $chkCross = $crossHelper->validateCross($cross, $old_cross);
        if ($chkCross['error']) {
            apiError(400, 'cross_error', $chkCross['error'][0]);
        }
        $cross_id=$crossHelper->editCross($cross, $by_identity_id);

        if(intval($cross_id) > 0) {
            $cross = $crossHelper->getCross($cross_id, true);
            // call Gobus {
            $modQueue = $this->getModelByName('Queue');
            $modQueue->despatchSummary(
                $cross, $old_cross, [], [], $result['uid'] ?: -$by_identity_id, $by_identity_id
            );
            // }
            foreach ($cross->exfee->invitations as $i => $invitation) {
                $cross->exfee->invitations[$i]->token = '';
            }
            apiResponse(['cross' => $cross]);
        }
        apiError(500, 'server_error', "Can't Edit this Cross.");
    }


    public function doArchive() {
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        $archive  = isset($_POST['archive']) && strtolower($_POST['archive']) === 'false' ? false : true;
        if (!$cross_id) {
            apiError(403, 'not_authorized', "The X you're requesting is private.");
        }

        $checkHelper = $this->getHelperByName('check');
        $hlpCross    = $this->getHelperByName('Cross');
        $modCross    = $this->getModelByName('Cross');

        $result = $checkHelper->isAPIAllow('user', $params['token']);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
        }

        if ($modCross->archiveCrossByCrossIdAndUserId($cross_id, $result['uid'], $archive)) {
            $cross = $hlpCross->getCross($cross_id);
            if ($cross) {
                apiResponse(['cross' => $cross]);
            }
        }
        apiError(500, 'server_error', "Can't Edit this Cross.");
    }

}
