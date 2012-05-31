<?php

class ExfeeActions extends ActionController {

    public function doIndex() {
        // get libs
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee',  'v2');
        $hlpCheck = $this->getHelperByName('check', 'v2');
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
        $modExfee = $this->getModelByName('exfee',  'v2');
        $hlpCheck = $this->getHelperByName('check', 'v2');
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
        // do it
        $exfee = json_decode($_POST['exfee']);
        if ($exfee && isset($exfee->invitations) && is_array($exfee->invitations)) {
            $modExfee->updateExfeeById($exfee_id, $exfee->invitations, $by_identity_id);
            if ($cross_id) {
                saveUpdate(
                    $cross_id,
                    array('exfee' => array('updated_at' => date('Y-m-d H:i:s',time()), 'identity_id' => $by_identity_id))
                );
            }
            apiResponse(array('exfee' => $modExfee->getExfeeById($exfee_id)));
        }
        apiError(400, 'editing failed', '');
    }

}
