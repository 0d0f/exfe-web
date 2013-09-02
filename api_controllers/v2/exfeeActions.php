<?php

class ExfeeActions extends ActionController {

    public function doIndex() {
        // get libs
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee');
        $hlpCheck = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], array('cross_id' => $cross_id));
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        if ($objExfee = $modExfee->getExfeeById($exfee_id)) {
            touchCross($cross_id, $result['uid']);
            apiResponse(array('exfee' => $modExfee->getExfeeById($exfee_id)));
        }
        apiError(400, 'fetching exfee failed', '');
    }


    public function doEdit() {
        // get libs
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee');
        $modCross = $this->getModelByName('Cross');
        $hlpCheck = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        if (!($by_identity_id = intval(@$_POST['by_identity_id'] ?: @$_GET['by_identity_id']))) {
            apiError(400, 'no_by_identity_id', 'by_identity_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross_edit', $params['token'], ['cross_id' => $cross_id, 'by_identity_id' => $by_identity_id]);
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        $by_identity_id = (int) $result['by_identity_id'];
        // do it
        $exfee = null;
        if (@$_POST['exfee']) {
            $exfee     = @json_decode($_POST['exfee']);
        } else {
            $rawExfee  = @json_decode(file_get_contents('php://input'));
            if ($rawExfee) {
                $exfee = @$rawExfee->exfee;
            }
        }
        if ($exfee && is_object($exfee)) {
            $exfee->id = $exfee_id;
            $rawCross  = $modCross->getCross($cross_id);
            $timezone  = '';
            if ($rawCross['timezone']) {
                $modTime  = $this->getModelByName('Time');
                $timezone = $modTime->getTimezoneNameByRaw($rawCross['timezone']);
            }
            $udResult  = $modExfee->updateExfee($exfee, $by_identity_id, $result['uid'], false, (int) $rawCross['state'] === 0, false, $timezone); // draft
            if ($cross_id && $udResult['changed']) {
                saveUpdate(
                    $cross_id,
                    ['exfee' => ['updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id]]
                );
            }
            $rtResult = ['exfee' => $modExfee->getExfeeById($exfee_id)];
            $code = 200;
            if ($udResult['soft_quota'] || $udResult['hard_quota']) {
                $rtResult['exfee_over_quota'] = EXFEE_QUOTA_SOFT_LIMIT;
                $code = 206;
            }
            touchCross($cross_id, $result['uid']);
            apiResponse($rtResult, $code);
        }
        apiError(400, 'editing failed', '');
    }


    public function doInvite() {
        // get libs
        $params      = $this->params;
        $modUser     = $this->getModelByName('User');
        $modExfee    = $this->getModelByName('Exfee');
        $modIdentity = $this->getModelByName('Identity');
        $hlpCross    = $this->getHelperByName('Cross');
        $hlpCheck    = $this->getHelperByName('Check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        if (!($user_token = @$_POST['user_token'])) {
            apiError(400, 'no_user_token', 'user_token must be provided');
        }
        if (!($xcode = @$_POST['xcode'])) {
            apiError(400, 'no_xcode', 'xcode must be provided');
        }
        // $_POST['widget_type']
        // $_POST['widget_id']
        // check user_token
        $objToken     = $modUser->getUserToken($user_token);
        if ($objToken && ($user_id = @$objToken['data']['user_id'])) {
        } else {
            apiError(400, 'error_user_token');
        }
        // check via identity
        if (($via = @$_POST['via'] ?: '')) {
            $external_username = preg_replace('/^(.*)@[^@]*$/', '$1', $via);
            $provider          = preg_replace('/^.*@([^@]*)$/', '$1', $via);
            $via_identity      = $modIdentity->getIdentityByProviderAndExternalUsername(
                $provider, $external_username
            );
            if (!$via_identity) {
                apiError(400, 'error_via_identity');
            }
        } else {
            $via_identity = $modIdentity->getIdentityById(explode(',', SMITH_BOT)[0]);
            if (!$via_identity) {
                apiError(500, 'server_error');
            }
        }
        // check invitation
        $rawInvitation = $modExfee->getRawInvitationByToken($xcode);
        if (!$rawInvitation
         || $rawInvitation['exfee_id'] !== $exfee_id
         || $rawInvitation['state']    === 4) {
            apiError(400, 'error_xcode');
        }
        // check user
        $user = $modUser->getUserById($user_id);
        if (!$user || !$user->identities) {
            apiError(400, 'error_user');
        }
        // check exfee
        $exfee = $modExfee->getExfeeById($exfee_id, true);
        $removed  = false;
        $viaFound = false;
        foreach ($exfee->invitations as $invitaion) {
            if ($invitaion->identity->connected_user_id === $user_id) {
                if ($invitaion->rsvp_status === 'REMOVED') {
                    $removed = true;
                } else {
                    $cross = $hlpCross->getCross($rawInvitation['cross_id']);
                    $modRoutex = $this->getModelByName('Routex');
                    $rtResult = $modRoutex->getRoutexStatusBy($cross->id, $user_id);
                    if ($rtResult !== -1) {
                        $cross->widget[] = [
                            'type'      => 'routex',
                            'my_status' => $rtResult,
                        ];
                    }
                    touchCross($cross->id, $user_id);
                    apiResponse(['cross' => $cross]);
                }
            }
            if ($invitaion->identity->connected_user_id === $via_identity->connected_user_id
             || $invitaion->identity->id                === $via_identity->id) {
                $viaFound = true;
            }
        }
        if ($removed) {
            apiError(401, 'removed_user');
        }
        if (!$viaFound) {
            apiError(400, 'error_via_identity');
        }
        // action
        $exfee     = new Exfee;
        $exfee->id = $exfee_id;
        $exfee->invitations = [];
        $wechatIdentity = null;
        $phoneIdentity  = null;
        $emailIdentity  = null;
        foreach ($user->identities as $identity) {
            switch ($identity->provider) {
                case 'phone':
                    $phoneIdentity  = $phoneIdentity  ?: $identity;
                    break;
                case 'wechat':
                    $wechatIdentity = $wechatIdentity ?: $identity;
                    break;
                case 'email':
                    $emailIdentity  = $emailIdentity  ?: $identity;
            }
        }
        if ($wechatIdentity) {
            $objInvitation = new stdClass;
            $objInvitation->id = 0;
            $objInvitation->identity = $wechatIdentity;
            $objInvitation->response = 'NORESPONSE';
            $exfee->invitations[] = $objInvitation;
        }
        if ($phoneIdentity) {
            $objInvitation = new stdClass;
            $objInvitation->id = 0;
            $objInvitation->identity = $phoneIdentity;
            $objInvitation->response = 'NORESPONSE';
            $exfee->invitations[] = $objInvitation;
        } else if ($emailIdentity) {
            $objInvitation = new stdClass;
            $objInvitation->id = 0;
            $objInvitation->identity = $emailIdentity;
            $objInvitation->response = 'NORESPONSE';
            $exfee->invitations[] = $objInvitation;
        }
        $udeResult = $modExfee->updateExfee(
            $exfee, $via_identity->id, $via_identity->connected_user_id
        );
        if ($udeResult && $udeResult['changed']) {
            apiResponse(['cross' => $hlpCross->getCross($rawInvitation['cross_id'])]);
        }
        apiError(400, 'bad_request');
    }


    public function doRemoveNotificationIdentity() {
        // get libs
        $params      = $this->params;
        $modIdentity = $this->getModelByName('identity');
        $modExfee    = $this->getModelByName('exfee');
        $hlpCheck    = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], ['cross_id' => $cross_id]);
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        // getting current invitation
        $exfee = $modExfee->getExfeeById($exfee_id);
        $cur_invitation = null;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id === $result['uid']) {
                $cur_invitation = $invitation;
                break;
            }
        }
        if (!$cur_invitation) {
            apiError(403, 'not_authorized', 'You are not authorized to edit this exfee.');
        }
        $by_identity_id  = (int) $cur_invitation->identity->id;
        // check notification_identity
        $raw_identity_id = @$_POST['identity_id'];
        if (!$raw_identity_id) {
            apiError(400, 'no_identity_id', '');
        }
        $strReg            = '/(.*)@([^\@]*)/';
        $external_username = preg_replace($strReg, '$1', $raw_identity_id);
        $provider          = preg_replace($strReg, '$2', $raw_identity_id);
        $identity_id = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_username, true
        );
        if (!$identity_id) {
            apiError(400, 'error_identity_id', '');
        }
        // do it
        $udResult = $modExfee->removeNotificationIdentity($exfee_id, $identity_id, $by_identity_id);
        if ($udResult) {
            touchCross($cross_id, $result['uid']);
            apiResponse(['exfee' => $modExfee->getExfeeById($exfee_id)]);
        }
        apiError(500, 'server_error', '');
    }


    public function doRsvp() {
        // get libs
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee');
        $modCross = $this->getModelByName('Cross');
        $hlpCheck = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        if (isset($_POST['rsvp'])) {
            $rsvp = json_decode($_POST['rsvp']);
        } else {
            $rsvp = json_decode(@file_get_contents('php://input'));
        }
        if ($rsvp && is_object($rsvp) && isset($rsvp->rsvps)) {
            $rsvp = $rsvp->rsvps;
        }
        if (!$rsvp || !is_array($rsvp)) {
            apiError(400, 'input_error', 'rsvp input error');
        }
        $by_identity_id = '';
        foreach ($rsvp as $rItem) {
            if ($by_identity_id) {
                if ($by_identity_id !== $rItem->by_identity_id) {
                    apiError(400, 'input_error', 'by_identity_id input error');
                }
            } else {
                $by_identity_id = $rItem->by_identity_id;
            }
        }
        if (!$by_identity_id) {
            apiError(400, 'no_by_identity_id', 'by_identity_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross_edit', $params['token'], ['cross_id' => $cross_id, 'by_identity_id' => $by_identity_id]);
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        // do it
        $rawCross  = $modCross->getCross($cross_id);
        if ($actResult = $modExfee->updateExfeeRsvpById($exfee_id, $rsvp, $by_identity_id, $result['uid'], (int) $rawCross['state'] === 0)) {
            if ($cross_id) {
                saveUpdate(
                    $cross_id,
                    ['exfee' => ['updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id]]
                );
            }
            touchCross($cross_id, $result['uid']);
            apiResponse(['rsvp' => $actResult]);
        }
        apiError(400, 'editing failed', '');
    }


    public function doChangeIdentity() {
        // get libs
        $params      = $this->params;
        $modIdentity = $this->getModelByName('identity');
        $modExfee    = $this->getModelByName('exfee');
        $hlpCheck    = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        $identity_id = @ (int) $_POST['identity_id'];
        if (!$identity_id) {
            apiError(400, 'input_error', 'identity_id input error');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], ['cross_id' => $cross_id]);
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        $identity = $modIdentity->getIdentityById($identity_id);
        if (!$identity || $identity->connected_user_id !== $result['uid']) {
            apiError(403, 'not_authorized', 'This identity is not belongs to you.');
        }
        // getting current invitation
        $exfee = $modExfee->getExfeeById($exfee_id);
        $cur_invitation    = null;
        $cur_invitation_id = 0;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id === $result['uid']) {
                $cur_invitation = $invitation;
                break;
            }
        }
        $rawExfee = $this->getRawExfeeById($exfee->id, true);
        foreach ($rawExfee as $reItem) {
            if ((int) $reItem['identity_id'] === $identity->id) {
                $cur_invitation_id = (int) $reItem['id'];
                break;
            }
        }
        if (!$cur_invitation) {
            apiError(403, 'not_authorized', 'You are not authorized to edit this exfee.');
        }
        // adding new invitation
        $new_invitation = deepclone($cur_invitation);
        $new_invitation->identity = $identity;
        if ($cur_invitation_id) {
            $new_invitation->id = $cur_invitation_id;
            $add_result = $modExfee->updateInvitation($new_invitation, $identity->id);
        } else {
            $add_result = $modExfee->addInvitationIntoExfee($new_invitation, $exfee_id, $identity->id);
        }
        // updating current invitation
        $rmv_result = false;
        if ($add_result) {
            if ($cur_invitation->identity->id === $identity->id) {
                $rmv_result = true;
            } else {
                $cur_invitation->response = 'REMOVED';
                $rmv_result = $modExfee->updateInvitation($cur_invitation, $identity->id);
            }
        }
        // updating exfee cache
        delCache("exfee:{$exfee_id}");
        if ($add_result && $rmv_result && ($exfee = $modExfee->getExfeeById($exfee_id))) {
            touchCross($cross_id, $result['uid']);
            apiResponse(['exfee' => $exfee]);
        }
        apiError(400, 'changing failed', '');
    }


    public function doAddNotificationIdentity() {
        // get libs
        $params      = $this->params;
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        $modExfee    = $this->getModelByName('exfee');
        $hlpCheck    = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], ['cross_id' => $cross_id]);
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        //
        $provider          = @ $_POST['provider']          ?: '';
        $external_username = @ $_POST['external_username'] ?: '';
        switch ($provider) {
            case 'phone':
            case 'email':
                $user = $modUser->getUserById((int) $result['uid']);
                $rawIdentity = $modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
                if (!$rawIdentity) {
                    $identity_id = $modIdentity->addIdentity([
                        'provider'          => $provider,
                        'external_id'       => $external_username,
                        'external_username' => $external_username,
                        'locale'            => $user->locale   ?: $this->local,
                        'timezone'          => $user->timezone ?: $this->timezone,
                    ]);
                    $rawIdentity = $modIdentity->getIdentityById($identity_id);
                }
                if (!$rawIdentity) {
                    apiError(500, 'failed', '');
                }
                if ($rawIdentity->connected_user_id !== (int) $result['uid']) {
                    $viResult = $modUser->verifyIdentity($rawIdentity, 'VERIFY', (int) $result['uid']);
                    if ($viResult) {
                        $modIdentity->sendVerification(
                            'Verify',
                            $rawIdentity,
                            $viResult['token'],
                            false,
                            @$user->name ?: ''
                        );
                    } else {
                        apiError(500, 'failed', '');
                    }
                }
                // getting current invitation
                $exfee = $modExfee->getExfeeById($exfee_id);
                $cur_invitation  = null;
                $cur_identity_id = 0;
                $grouping        = 0;
                foreach ($exfee->invitations as $invitation) {
                    if ($invitation->identity->connected_user_id === (int) $result['uid']) {
                        $cur_invitation = $invitation;
                        break;
                    }
                }
                $rawExfee = $modExfee->getRawExfeeById($exfee->id, true);
                foreach ($rawExfee as $reItem) {
                    if ((int) $reItem['identity_id'] === $rawIdentity->id) {
                        $cur_invitation_id = (int) $reItem['id'];
                        $grouping          = (int) $reItem['grouping'];
                        break;
                    }
                }
                if ($cur_identity_id) {
                    apiError(400, 'already_in', '');
                }
                $objInvitation = new stdClass;
                $objInvitation->id       = 0;
                $objInvitation->identity = $rawIdentity;
                $objInvitation->response = 'NOTIFICATION';
                $objInvitation->host     = $cur_invitation->host;
                $objInvitation->mates    = $cur_invitation->mates;
                $objInvitation->grouping = $grouping;
                $result = $modExfee->addInvitationIntoExfee(
                    $objInvitation, $exfee->id, $cur_invitation->identity->id, (int) $result['uid']
                );
                if ($result) {
                    touchCross($cross_id, $result['uid']);
                    apiResponse([]);
                }
                break;
            default:
                apiError(400, 'unsupported_provider', '');
        }
        apiError(500, 'failed', '');
    }


    public function doRequest() {
        // get libs
        $params      = $this->params;
        $modExfee    = $this->getModelByName('exfee');
        $modIdentity = $this->getModelByName('Identity');
        $modRequest  = $this->getModelByName('Request');
        $hlpCheck    = $this->getHelperByName('Check');
        // basic check
        $by_identity_id = 0;
        if (!($exfee_id = intval($params['id']))) {
            $cross_id = (int) @$_POST['cross_id'];
            $invToken = @$_POST['invitation_token'] ?: '';
            if ($cross_id && strlen($invToken) === 4) {
                $invitation = $modExfee->getRawInvitationByExfeeIdAndToken($exfee_id, $invToken);
            } else if ($invToken) {
                $invitation = $modExfee->getRawInvitationByToken($invToken);
            }
            if ($invitation && @$invitation['exfee_id']) {
                $by_identity_id = $invitation['identity_id'];
                $exfee_id = $invitation['exfee_id'];
            } else {
                apiError(400, 'no_exfee_id', 'exfee_id must be provided');
            }
        }
        // get cross id
        if (!($cross_id = $modExfee->getCrossIdByExfeeId($exfee_id))) {
            apiError(404, 'exfee_not_found', '');
        }
        if ($this->tails && ($request_id = (int) $this->tails[0])
         && in_array(@$this->tails[1], ['approve', 'decline'])) {
            // check rights
            $result = $hlpCheck->isAPIAllow(
                'cross', $params['token'], ['cross_id' => $cross_id]
            );
            if (!$result['check']) {
                if ($result['uid']) {
                    apiError(403, 'not_authorized', 'You are not a member of this exfee.');
                }
                apiError(401, 'invalid_auth', '');
            }
            if (!$by_identity_id && !($by_identity_id = @ (int) $result['by_identity_id'])) {
                // getting current invitation
                $exfee = $modExfee->getExfeeById($exfee_id);
                foreach ($exfee->invitations as $invitation) {
                    if ($invitation->identity->connected_user_id === $result['uid']) {
                        $by_identity_id = $invitation->identity->id;
                        break;
                    }
                }
            }
            if (!$by_identity_id) {
                apiError(401, 'invalid_auth', '');
            }
            $rqResult = $modRequest->changeStatus(
                $this->tails[0], 0, 0,
                $this->tails[1] === 'approve' ? 1 : 2, $by_identity_id
            );
            if ($rqResult) {
                apiResponse(['request' => $rqResult]);
            }
            apiError(400, 'invalid_request', '');
        }
        if (!($provider = strtolower(@$_POST['provider']))) {
            apiError(400, 'error_provider', '');
        }
        if (!($username = strtolower(@$_POST['external_username']))) {
            apiError(400, 'error_external_username', '');
        }
        // get identity
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $username
        );
        if ($identity) {
            $identity_id = $identity->id;
        } else {
            $identity_id = $modIdentity->addIdentity([
                'provider'          => $provider,
                'external_id'       => $username,
                'external_username' => $username,
            ]);
            $identity    = $modIdentity->getIdentityById($identity_id);
        }
        if (!$identity) {
            apiError(500, 'identity_error');
        }
        // check current status
        $identity_ids = $modExfee->getIdentityIdsByExfeeId($exfee_id);
        $user_ids     = $modExfee->getUserIdsByExfeeId($exfee_id);
        if (in_array($identity_id, $identity_ids)
         || in_array($identity->connected_user_id, $user_ids)) {
            apiError(400, 'no_need', 'You are already in the exfee.');
        }
        // request
        $request = $modRequest->request(
            $identity_id, $exfee_id, @$_POST['message'] ?: ''
        );
        if ($request) {
            apiResponse(['request' => $request]);
        }
        apiError(500, 'server_error');
    }

}
