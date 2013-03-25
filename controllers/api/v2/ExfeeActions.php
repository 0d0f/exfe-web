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
                $exfee = @$rawExfee['exfee'];
            }
        }
        if ($exfee && is_object($exfee)) {
            $exfee->id = $exfee_id;
            $rawCross  = $modCross->getCross($cross_id);
            $udResult  = $modExfee->updateExfee($exfee, $by_identity_id, $result['uid'], false, $rawCross['state'] === 0); // draft
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
            apiResponse($rtResult, $code);
        }
        apiError(400, 'editing failed', '');
    }


    public function doRsvp() {
        // get libs
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee');
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
        if ($actResult = $modExfee->updateExfeeRsvpById($exfee_id, $rsvp, $by_identity_id, $result['uid'], $rawCross['state'] === 0)) {
            if ($cross_id) {
                saveUpdate(
                    $cross_id,
                    ['exfee' => ['updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id]]
                );
            }
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
                switch ($invitation->rsvp_status) {
                    case 'NORESPONSE':
                    case 'ACCEPTED':
                    case 'INTERESTED':
                    case 'DECLINED':
                    case 'IGNORED':
                        $cur_invitation = $invitation;
                }
            }
            if ($invitation->identity->id === $identity->id) {
                $cur_invitation_id = $invitation->id;
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
                $cur_invitation->rsvp_status = 'REMOVED';
                $rmv_result = $modExfee->updateInvitation($cur_invitation, $identity->id);
            }
        }
        // updating exfee cache
        delCache("exfee:{$exfee_id}");
        if ($add_result && $rmv_result && ($exfee = $modExfee->getExfeeById($exfee_id))) {
            apiResponse(['exfee' => $exfee]);
        }
        apiError(400, 'changing failed', '');
    }

}
