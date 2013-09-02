<?php

class CrossesActions extends ActionController {

    public function doIndex() {
        $params = $this->params;
        $updated_at = $params['updated_at'];
        if ($updated_at) {
            $updated_at = strtotime($updated_at);
        }
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            switch ($cross->attribute['state']) {
                case 'deleted':
                    apiError(403, 'not_authorized', "The X you're requesting is private.");
                case 'draft':
                    if (!in_array($result['uid'], $cross->exfee->hosts)) {
                        apiError(403, 'not_authorized', "The X you're requesting is private.");
                    }
            }
            touchCross($params['id'], $result['uid']);
            if ($updated_at && $updated_at >= strtotime($cross->exfee->updated_at)) {
                apiError(304, 'Cross Not Modified.');
            }
            $modRoutex = $this->getModelByName('Routex');
            $rtResult = $modRoutex->getRoutexStatusBy($cross->id, $result['uid']);
            if ($rtResult !== -1) {
                $cross->widget[] = [
                    'type'      => 'routex',
                    'my_status' => $rtResult,
                ];
            }
            $cross->touched_at = date('Y-m-d H:i:s') . ' +0000';
            apiResponse(['cross' => $cross]);
        }
        apiError(400, 'param_error', "The X you're requesting is not found.");
    }


    public function doWidgets() {
        $params = $this->params;
        $updated_at = $params['updated_at'];
        if ($updated_at) {
            $updated_at = strtotime($updated_at);
        }
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            switch ($cross->attribute['state']) {
                case 'deleted':
                    apiError(403, 'not_authorized', "The X you're requesting is private.");
                case 'draft':
                    if (!in_array($result['uid'], $cross->exfee->hosts)) {
                        apiError(403, 'not_authorized', "The X you're requesting is private.");
                    }
            }
            // votes
            $modVote  = $this->getModelByName('Vote');
            $vote_ids = $modVote->getVoteIdsByCrossId($params['id']);
            $widgets  = [];
            foreach ($vote_ids ?: [] as $vid) {
                if (($vote = $modVote->getVoteById($vid))
                  && $vote->status !== 'DELETED') {
                    $widgets[] = $vote;
                }
            }
            // request accesses
            $modRqst  = $this->getModelByName('Request');
            $reqAss   = $modRqst->getRequestAccessBy($cross->exfee->id);
            if ($reqAss) {
                $widgets[] = $reqAss;
            }
            apiResponse(['widgets' => $widgets]);
        }
        apiError(400, 'param_error', "The X you're requesting is not found.");
    }


    // api.leask.0d0f.com/v2/crosses/[int:cross_id]/touch?user_id=[int:user_id]
    public function doTouch() {
        // touch
        $params = $this->params;
        $cross_id = @ (int) $params['id'];
        $user_id  = @ (int) $params['user_id'];
        touchCross($cross_id, $user_id);
        // render
        header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        $image = imagecreatefromstring(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCB1jYAAAAAIAAc/INeUAAAAASUVORK5CYII='));
        imagepng($image);
        imagedestroy($image);
    }


    public function doCheckInvitationToken() {
        // load models
        $modExfee   = $this->getModelByName('exfee');
        // get inputs
        $invToken   = trim(@$_POST['invitation_token']);
        if (!$invToken) {
            apiError('400', 'no_token');
        }
        // get invitation
        $invitation = $modExfee->getRawInvitationByToken($invToken);
        //
        apiResponse(['valid' => $invitation && $invitation['valid']]);
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
        $bySmsToken  = false;
        if ($acsToken) {
            $crossToken = $modCross->getCrossAccessToken($acsToken);
            if ($crossToken
             && $crossToken['data']
             && $crossToken['data']['token_type'] === 'cross_access_token') {
                $invitation = $modExfee->getRawInvitationByCrossIdAndIdentityId(
                    $crossToken['data']['cross_id'], $crossToken['data']['identity_id']
                );
                $modExfeAuth->keyUpdate($acsToken, null, 60 * 60 * 24 * 7); // for 1 week
            }
        }
        if (!$invitation && $invToken) {
            if ($cross_id && strlen($invToken) === 4) {
                $exfee_id   = $modExfee->getExfeeIdByCrossId($cross_id);
                $invitation = $modExfee->getRawInvitationByExfeeIdAndToken($exfee_id, $invToken);
                $invToken   = $invitation['token'];
                $bySmsToken = !!$invitation;
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
            if ($result['cross']->attribute['state'] === 'deleted') {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
            // used token
            if ($invitation['valid']) {
                // check Smith token
                if (!in_array($invitation['identity_id'], explode(',', SMITH_BOT))) {
                    $modExfee->usedToken($invToken);
                }
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
                touchCross($invitation['cross_id'], $user_id);
                apiResponse($result);
            }
            // 已登录  初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // (any)    FALSE   (any)   只读浏览    M50D4 Sign In
            if (!$invitation['valid']) {
                $result['browsing_identity'] = $modIdentity->getIdentityById(
                    $invitation['identity_id']
                );
                $result['action'] = 'SIGNIN';
                if (isset($result['browsing_identity']->connected_user_id)) {
                    touchCross(
                        $invitation['cross_id'],
                        $result['browsing_identity']->connected_user_id
                    );
                }
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
                $result['action'] = 'SETUP';

                // setup user by sms {
                if ($bySmsToken && !isset($user_infos['CONNECTED'])) {
                    // clear verify token
                    if (isset($user_infos['VERIFYING'])) {
                        $modExfeAuth->resourceUpdate([
                            'token_type'   => 'verification_token',
                            'action'       => 'VERIFY',
                            'identity_id'  => $invitation['identity_id'],
                        ], 0);
                    }
                    // add new user
                    $user_id = $modUser->addUser();
                    // connect identity to new user
                    $modUser->setUserIdentityStatus(
                        $user_id, $invitation['identity_id'], 3
                    );
                    // send welcome sms
                    $objIdentity = $modIdentity->getIdentityById(
                        $invitation['identity_id']
                    );
                    $modIdentity->sendVerification(
                        'Welcome', $objIdentity, '', false, $objIdentity->name ?: ''
                    );
                    // signin
                    $result['authorization'] = $modUser->rawSignin($user_id);
                }
                // setup user by sms }

                touchCross($invitation['cross_id'], $user_id);
                apiResponse($result);
            }
            // 已登录 初次点击Token   身份连接状态  登录状态    帐号弹出窗操作
            // FALSE   TRUE    CONNECTED   正常登录    M50D3
            if (!$user_id
             && $invitation['valid']
             && isset($user_infos['CONNECTED'])) {
                if (in_array($invitation['identity_id'], explode(',', SMITH_BOT))) {
                    $result['free_identities'] = [];
                    foreach ($result['cross']->exfee->invitations as $invItem) {
                        if (!in_array($invItem->identity->id, explode(',', SMITH_BOT))
                            // && $invitation->identity->
                            // @todo check provider as wechat
                            ) {
                            $invItem->identity->free = $invItem->token_used_at === '0000-00-00 00:00:00';
                            $result['free_identities'][] = $invItem->identity;
                        }
                    }
                    $result['action'] = 'CLAIM_IDENTITY';
                } else {
                    if ($usInvToken) {
                        $result['authorization'] = $modUser->rawSignin(
                            $user_infos['CONNECTED'][0]['user_id']
                        );
                    } else {
                        $result['browsing_identity'] = $modIdentity->getIdentityById(
                            $invitation['identity_id']
                        );
                        $result['action'] = 'SIGNIN';
                    }
                }
                $result['read_only'] = false;
                touchCross(
                    $invitation['cross_id'],
                    $user_infos['CONNECTED'][0]['user_id']
                );
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
                if ($usInvToken) {
                    $result['action'] = 'SETUP';
                } else if (isset($invitation['raw_valid'])) {
                    $result['action'] = $invitation['raw_valid'] ? 'SETUP' : 'SIGNIN';
                } else {
                    $result['action'] = 'SIGNIN';
                }
                // setup user by sms {
                if ($bySmsToken && !isset($user_infos['CONNECTED'])) {
                    // clear verify token
                    if (isset($user_infos['VERIFYING'])) {
                        $modExfeAuth->resourceUpdate([
                            'token_type'   => 'verification_token',
                            'action'       => 'VERIFY',
                            'identity_id'  => $invitation['identity_id'],
                        ], 0);
                    }
                    // add new user
                    $user_id = $modUser->addUser();
                    // connect identity to new user
                    $modUser->setUserIdentityStatus(
                        $user_id, $invitation['identity_id'], 3
                    );
                    // send welcome sms
                    $objIdentity = $modIdentity->getIdentityById(
                        $invitation['identity_id']
                    );
                    $modIdentity->sendVerification(
                        'Welcome', $objIdentity, '', false, $objIdentity->name ?: ''
                    );
                    // signin
                    $result['authorization'] = $modUser->rawSignin($user_id);
                }
                // setup user by sms }

                touchCross($invitation['cross_id'], $user_id);
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
                $result['action'] = 'SIGNIN';
                touchCross($invitation['cross_id'], $user_id);
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
                if ($usInvToken) {
                    $result['action'] = 'SETUP';
                } else if (isset($invitation['raw_valid'])) {
                    $result['action'] = $invitation['raw_valid'] ? 'SETUP' : 'SIGNIN';
                } else {
                    $result['action'] = 'SIGNIN';
                }

                // setup user by sms {
                if ($bySmsToken && !isset($user_infos['CONNECTED'])) {
                    // clear verify token
                    if (isset($user_infos['VERIFYING'])) {
                        $modExfeAuth->resourceUpdate([
                            'token_type'   => 'verification_token',
                            'action'       => 'VERIFY',
                            'identity_id'  => $invitation['identity_id'],
                        ], 0);
                    }
                    // add new user
                    $user_id = $modUser->addUser();
                    // connect identity to new user
                    $modUser->setUserIdentityStatus(
                        $user_id, $invitation['identity_id'], 3
                    );
                    // send welcome sms
                    $objIdentity = $modIdentity->getIdentityById(
                        $invitation['identity_id']
                    );
                    $modIdentity->sendVerification(
                        'Welcome', $objIdentity, '', false, $objIdentity->name ?: ''
                    );
                    // signin
                    $result['authorization'] = $modUser->rawSignin($user_id);
                }
                // setup user by sms }

                touchCross($invitation['cross_id'], $user_id);
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
            if (isset($invitation->identity->connected_user_id)) {
                touchCross($cross_id, $invitation->identity->connected_user_id);
            }
            apiResponse(['invitation' => $invitation]);
        }
        apiError(404, 'invitation_not_found', 'Invitation Not Found');
    }


    public function doGetRouteXUrl() {
        $params = $this->params;
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            switch ($cross->attribute['state']) {
                case 'deleted':
                    apiError(403, 'not_authorized', "The X you're requesting is private.");
                case 'draft':
                    if (!in_array($result['uid'], $cross->exfee->hosts)) {
                        apiError(403, 'not_authorized', "The X you're requesting is private.");
                    }
            }
            $modExfee     = $this->getModelByName('Exfee');
            $hostIdentity = null;
            $curIdentity  = null;
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $result['uid']) {
                    $curIdentity  = $invitation->identity;
                }
                if ($invitation->host) {
                    $hostIdentity = $invitation->identity;
                }
            }
            $byIdentity = $curIdentity ?: $hostIdentity;
            if (!$byIdentity) {
                apiError(500, 'internal_server_error');
            }
            $idBot      = explode(',', SMITH_BOT)[0];
            $invitation = $modExfee->getRawInvitationByCrossIdAndIdentityId(
                $cross->id, $idBot
            );
            if (!$invitation) {
                $modIdentity = $this->getModelByName('Identity');
                $exfee = new Exfee;
                $now   = time();
                $bot   = $modIdentity->getIdentityById($idBot);
                $exfee->id = $cross->exfee->id;
                $exfee->invitations = [new Invitation(
                    0, $bot, $byIdentity, $byIdentity,
                    'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                )];
                $udeResult = $modExfee->updateExfee(
                    $exfee, $byIdentity->id, $byIdentity->connected_user_id, false, true
                );
                if ($udeResult) {
                    $invitation = $modExfee->getRawInvitationByCrossIdAndIdentityId(
                        $cross->id, $idBot
                    );
                }
            }
            if (!$invitation) {
                apiError(500, 'internal_server_error');
            }
            apiResponse(['url' => SITE_URL . "/!{$cross->id}/routex?xcode={$invitation['token']}&via={$byIdentity->external_username}@{$byIdentity->provider}"]);
        }
        apiError(400, 'param_error', "The X you're requesting is not found.");
    }


    public function doGather() {
        $params=$this->params;
        $cross_str=@file_get_contents('php://input');
        $cross=json_decode($cross_str);
        if ($cross && is_object($cross) && isset($cross->cross)) {
            $cross = $cross->cross;
        }
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
        $chkCross = $crossHelper->validateCross($cross);
        if ($chkCross['error']) {
            apiError(400, 'cross_error', $chkCross['error'][0]);
        }
        $cross = $chkCross['cross'];
        $gthResult = $crossHelper->gatherCross($cross, $by_identity_id, $result['uid']);
        $cross_id = @$gthResult['cross_id'];

        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName('cross');
            $cross=$crossHelper->getCross($cross_id);
            touchCross($cross_id, $result['uid']);
            if (@$gthResult['over_quota']) {
                apiResponse([
                    'cross'            => $cross,
                    'exfee_over_quota' => EXFEE_QUOTA_SOFT_LIMIT,
                ], '206');
            }
            apiResponse(array("cross"=>$cross));
        }
        else
            apiError(500,"server_error","Can't gather this Cross.");

    }


    public function doEdit() {
        $params=$this->params;
        $cross_str=@file_get_contents('php://input');
        $cross=json_decode($cross_str);
        if ($cross && is_object($cross) && isset($cross->cross)) {
            $cross = $cross->cross;
        }
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

        $by_identity_id = (int) $result['by_identity_id'];
        $cross->id=$params["id"];
        $cross->exfee_id=$result["exfee_id"];
        $old_cross = $crossHelper->getCross((int) $params['id']);
        $chkCross = $crossHelper->validateCross($cross, $old_cross);
        if ($chkCross['error']) {
            apiError(400, 'cross_error', $chkCross['error'][0]);
        }
        $cross_rs = $crossHelper->editCross($cross, $by_identity_id);
        if ($cross_rs) {
            $cross_id = $cross_rs['cross_id'];
            $cross    = $crossHelper->getCross($cross_id, true);
            // call Gobus {
            $oldDraft = isset($old_cross->attribute)
                     && isset($old_cross->attribute['state'])
                     && $old_cross->attribute['state'] === 'draft';
            $draft    = isset($cross->attribute)
                     && isset($cross->attribute['state'])
                     && $cross->attribute['state']     === 'draft' ?: $oldDraft;
            if (!$draft) {
                $modQueue = $this->getModelByName('Queue');
                if ($oldDraft) {
                    $modQueue->despatchInvitation(
                        $cross, $cross->exfee,
                        (int) $result['uid'] ?: -$by_identity_id, $by_identity_id
                    );
                } else {
                    if ($cross_rs['notification']) {
                        $modQueue->despatchUpdate(
                            $cross, $old_cross, [], [],
                            $result['uid'] ?: -$by_identity_id, $by_identity_id
                        );
                    }
                }
            }
            // }
            foreach ($cross->exfee->invitations as $i => $invitation) {
                unset($cross->exfee->invitations[$i]->token);
                unset($cross->exfee->invitations[$i]->token_used_at);
            }
            touchCross($cross_id, $result['uid']);
            $cross->touched_at = date('Y-m-d H:i:s') . ' +0000';
            apiResponse(['cross' => $cross]);
        }
        apiError(500, 'server_error', "Can't Edit this Cross.");
    }


    public function doArchive() {
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        $archive  = isset($_POST['archive']) && strtolower($_POST['archive']) === 'false' ? false : true;
        if (!$cross_id) {
            apiError(400, 'no_cross_id', "cross_id must be provided.");
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
                touchCross($cross_id, $result['uid']);
                apiResponse(['cross' => $cross]);
            }
        }
        apiError(500, 'server_error', "Can't Edit this Cross.");
    }


    public function doDelete() {
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        if (!$cross_id) {
            apiError(400, 'no_cross_id', "cross_id must be provided.");
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

        $result = $modCross->deleteCrossByCrossIdAndUserId($cross_id, $result['uid']);

        if ($result) {
            touchCross($cross_id, $result['uid']);
            apiResponse(['cross_id' => $cross_id]);
        } else if ($result === false) {
            apiError(400, 'param_error', "Can't Edit this Cross.");
        }

        apiError(403, 'not_authorized', 'You can not delete this cross.');
    }

}
