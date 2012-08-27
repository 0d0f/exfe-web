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
        $hlpCheck = $this->getHelperByName('check');
        // basic check
        if (!($exfee_id = intval($params['id']))) {
            apiError(400, 'no_exfee_id', 'exfee_id must be provided');
        }
        if (!($by_identity_id = intval($_POST['by_identity_id']))) {
            apiError(400, 'no_by_identity_id', 'by_identity_id must be provided');
        }
        // get cross id
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        // check rights
        $result   = $hlpCheck->isAPIAllow('cross_edit', $params['token'], array('cross_id' => $cross_id, "by_identity_id"=>$by_identity_id));
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        $by_identity_id = (int) $result['by_identity_id'];
        // do it
        $exfee = json_decode($_POST['exfee']);
        if ($exfee && isset($exfee->invitations) && is_array($exfee->invitations)
        && ($udResult = $modExfee->updateExfeeById($exfee_id, $exfee->invitations, $by_identity_id, $result['uid']))) {
            if ($cross_id) {
                saveUpdate(
                    $cross_id,
                    array('exfee' => array('updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id))
                );
            }
            $rtResult = ['exfee' => $modExfee->getExfeeById($exfee_id)];
            if ($udResult['over_quota']) {
                $rtResult['over_quota'] = true;
            }
            apiResponse($rtResult);
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
        $rsvp = json_decode($_POST['rsvp']);
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
        $result   = $hlpCheck->isAPIAllow('cross_edit', $params['token'], array('cross_id' => $cross_id, "by_identity_id"=>$by_identity_id));
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', 'You are not a member of this exfee.');
            }
            apiError(401, 'invalid_auth', '');
        }
        // do it
        if ($actResult = $modExfee->updateExfeeRsvpById($exfee_id, $rsvp, $by_identity_id)) {
            if ($cross_id) {
                saveUpdate(
                    $cross_id,
                    array('exfee' => array('updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id))
                );
            }
            apiResponse(array('rsvp' => $actResult));
        }
        apiError(400, 'editing failed', '');
    }

}
