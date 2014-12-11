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
        if ($objVote && $objVote->status !== 'deleted') {
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
        if (!$objVote || (isset($objVote->status) && strtolower($objVote->status) === 'deleted')) {
            apiError(400, 'error_vote');
        }
        $options  = @$objVote->options;
        $vote_id  = $modVote->createVote(
            $cross_id, $identity_id, @$objVote->title, @$objVote->description,
            in_array(@$objVote->choice, ['radio', 'multiple'])
          ? @$objVote->choice : 'radio', @$objVote->anonymous
        );
        if ($vote_id) {
            foreach (@$objVote->options ?: [] as $option) {
                $modVote->addVoteOption(
                    $vote_id, $identity_id, @$option->data, @$option->title
                );
            }
            apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
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
        $curVote  = $modVote->getVoteById($vote_id);
        if ($curVote->status === 'deleted') {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        $identity_id = 0;
        $exfee_id    = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee       = $modExfee->getExfeeById($exfee_id);
        $host        = false;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id ===  $result['uid']
             || $invitation->identity->id                === @$result['by_identity_id']) {
                $identity_id = $invitation->identity->id;
            }
            if ($invitation->host) {
                $host = true;
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
        if ((isset($objVote->choice)    && (strtolower($objVote->choice) !== $curVote->choice))
         || (isset($objVote->anonymous) && (!!$objVote->anonymous   !==   $curVote->anonymous))
         || (isset($objVote->status)    && (strtolower($objVote->status) !== $curVote->status))) {
            if (!$host && $curVote->created_by !== $identity_id) {
                apiError(403, 'not_authorized', "The Vote you're requesting is private.");
            }
        }
        if ($modVote->updateVote(
            $vote_id, $identity_id, @$objVote->title, @$objVote->description,
            in_array(@$objVote->choice, ['radio', 'multiple'])
          ? @$objVote->choice : 'radio', @$objVote->anonymous, @$objVote->status
        )) {
            $objVote = $modVote->getVoteById($vote_id);
            if ($objVote && $objVote->status === 'deleted') {
                $modExfee->updateExfeeTime($exfee_id);
                apiResponse(['vote_id' => $vote_id]);
            } else {
                apiResponse(['vote'    => $objVote]);
            }
        }
        apiError(400, 'error_vote');
    }


    public function doClose() {
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
        $curVote = $modVote->getVoteById($vote_id);
        if ($curVote->status === 'deleted') {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        $identity_id = 0;
        $exfee_id    = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee       = $modExfee->getExfeeById($exfee_id);
        $host        = false;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id ===  $result['uid']
             || $invitation->identity->id                === @$result['by_identity_id']) {
                $identity_id = $invitation->identity->id;
            }
            if ($invitation->host) {
                $host = true;
            }
        }
        if (!$identity_id || (!$host && $curVote->created_by !== $identity_id)) {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        if ($modVote->updateVote(
            $vote_id, $identity_id, null, null, null, null, 3
        )) {
            $modExfee->updateExfeeTime($exfee_id);
            apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
        }
        apiError(400, 'error_vote');
    }


    public function doDelete() {
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
        $curVote = $modVote->getVoteById($vote_id);
        if ($curVote->status === 'deleted') {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        $identity_id = 0;
        $exfee_id    = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee       = $modExfee->getExfeeById($exfee_id);
        $host        = false;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id ===  $result['uid']
             || $invitation->identity->id                === @$result['by_identity_id']) {
                $identity_id = $invitation->identity->id;
            }
            if ($invitation->host) {
                $host = true;
            }
        }
        if (!$identity_id || (!$host && $curVote->created_by !== $identity_id)) {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
        }
        if ($modVote->updateVote(
            $vote_id, $identity_id, null, null, null, null, 4
        )) {
            $modExfee->updateExfeeTime($exfee_id);
            apiResponse(['vote_id' => $vote_id]);
        }
        apiError(400, 'error_vote');
    }


    public function doOptions() {
        $tails  = $this->tails;
        if (!$tails) {
            apiError(404, 'not_found', "The Options you're requesting is not found.");
        }
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
        $curVote = $modVote->getVoteById($vote_id);
        if ($curVote->status === 'deleted') {
            apiError(403, 'not_authorized', "The Vote you're requesting is private.");
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
        $strPost   = @file_get_contents('php://input');
        $objOption = @json_decode($strPost);
        if ($tails[0] === 'add') {
            $success = false;
            foreach ($objOption && is_array($objOption) ? $objOption : [] as $oItem) {
                if ($oItem) {
                    $sucItem = $modVote->addVoteOption(
                        $vote_id, $identity_id, @$oItem->data, @$oItem->title
                    );
                    if ($sucItem) {
                        $success = true;
                    }
                }
            }
            if ($success) {
                apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
            }
            apiError(400, 'error_option');
        } else if (($option_id = $tails[0])) {
            switch (@$tails[1]) {
                case 'update':
                    if (!$objOption) {
                        apiError(400, 'error_option');
                    }
                    if ($modVote->updateVoteOption(
                        $option_id,        $identity_id,
                        @$objOption->data, @$objOption->title
                    )) {
                        $modExfee->updateExfeeTime($exfee_id);
                        apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
                    }
                    apiError(400, 'error_option');
                    break;
                case 'remove':
                    if ($modVote->removeVoteOption($option_id, $identity_id)) {
                        $modExfee->updateExfeeTime($exfee_id);
                        apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
                    }
                    apiError(400, 'error_option');
                    break;
                case 'vote':
                    $objVote = $modVote->getVoteById($vote_id);
                    if (!$objVote || $objVote->status !== 'opening') {
                        apiError(403, 'not_authorized', "This vote in not opening currently.");
                    }
                    $action = strtolower(trim(@$_POST['vote']));
                    $action = in_array($action, ['', 'agree', 'disagree'])
                            ? $action : 'agree';
                    if ($modVote->vote(
                        $option_id, $identity_id, $action, $objVote->choice === 'multiple'
                    )) {
                        $modExfee->updateExfeeTime($exfee_id);
                        apiResponse(['vote' => $modVote->getVoteById($vote_id)]);
                    }
                    apiError(400, 'error_option');
            }
        }
        apiError(404, 'not_found', "The Vote you're requesting is not found.");
    }

}
