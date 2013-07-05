<?php

class VotesActions extends ActionController {

    public function doIndex() {
        $modVote  = $this->getModelByName('Vote');
        $hlpCheck = $this->getHelperByName('check');
        $params   = $this->params;
        $vote_id  = @ (int) $params['id'];
        $cross_id = $modVote->getCrossIdByVoteId($vote_id);
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The Vote you're requesting is private.");
            }
        }
        $objVote = $modVote->getVoteById($vote_id);
        if ($objVote) {
            apiResponse(['vote' => $objVote]);
        }
        apiError(404, 'not_found', "The Vote you're requesting is not found.");
    }


    public function doCreate() {
        $modVote  = $this->getModelByName('Vote');
        $hlpCheck = $this->getHelperByName('check');
        $modExfee = $this->getModelByName('Exfee');
        $params   = $this->params;
        $cross_id = @ (int) $params['cross_id'];
        $result   = $hlpCheck->isAPIAllow('cross', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The Vote you're requesting is private.");
            }
        }
        $identity_id = 0;
        $exfee_id    = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee       = $modExfee->getExfeeById($exfee_id);
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id === $user_id) {
                $identity_id = $invitation->identity->id;
                break;
            }
        }








        // check identity
        if ($stream_id) {



            if (!$identity_id) {
                apiError(400, 'server_error');
            }
        } else {
            $identity_id = @ (int) $_POST['identity_id'];
            if (!$identity_id) {
                apiError(400, 'no_identity_id', ''); // 需要输入identity_id
            }
            $modIdentity = $this->getModelByName('Identity');
            $identity = $modIdentity->getIdentityById($identity_id);
            if (!$identity || $identity->connected_user_id !== $user_id) {
                apiError(400, 'can_not_be_verify', 'This identity does not belong to current user.');
            }
        }















        $strPost  = @ file_get_contents('php://input');
        $objVote  = @ json_decode($strPost);
        if (!$objVote) {
            apiError(400, 'error_vote');
        }
        $title    = @ $objVote->title;
        $desc     = @ $objVote->description;
        $
    }


    public function doUpdate() {

    }


    public function doClose() {

    }


    public function doDelete() {

    }


    public function doOptionsAdd() {

    }


    public function doOptionsVote() {

    }


    public function doOptionsRemove() {

    }

}
