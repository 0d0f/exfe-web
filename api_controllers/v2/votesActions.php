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
            if ($invitation->identity->connected_user_id ===  $result['uid']
             || $invitation->identity->id                === @$result['by_identity_id']) {
                $identity_id = $invitation->identity->id;
                break;
            }
        }
        if (!$identity_id) {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        $strPost  = @file_get_contents('php://input');
        $objVote  = @json_decode($strPost);
        if (!$objVote) {
            apiError(400, 'error_vote');
        }
        $options  = @$objVote->options;
        $vote_id  = $modVote->createVote(
            $cross_id, $identity_id, @$objVote->title, @$objVote->description
        );
        if ($vote_id) {
            foreach (@$objVote->options ?: [] as $option) {
                $modVote->addVoteOption(
                    $vote_id, $identity_id, @$option->data, @$option->title
                );
            }
            $objVote = $modVote->getVoteById($vote_id);
            if ($objVote) {
                apiResponse(['vote' => $objVote]);
            }
        }
        apiError(400, 'error_vote');
    }


    public function doUpdate() {
        $modVote  = $this->getModelByName('Vote');
        $hlpCheck = $this->getHelperByName('check');
        $modExfee = $this->getModelByName('Exfee');
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
        $identity_id = 0;
        $exfee_id    = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee       = $modExfee->getExfeeById($exfee_id);
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id ===  $result['uid']
             || $invitation->identity->id                === @$result['by_identity_id']) {
                $identity_id = $invitation->identity->id;
                break;
            }
        }
        if (!$identity_id) {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        $strPost  = @file_get_contents('php://input');
        $objVote  = @json_decode($strPost);
        if (!$objVote) {
            apiError(400, 'error_vote');
        }
        if ($modVote->updateVote(
            $vote_id, $identity_id, @$objVote->title, @$objVote->description
        ) && ($objVote = $modVote->getVoteById($vote_id))) {
            apiResponse(['vote' => $objVote]);
        }
        apiError(400, 'error_vote');
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
