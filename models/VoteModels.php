<?php

class VoteModels extends DataModel {

    public function createVote($cross_id, $identity_id, $title = '', $description = '', $type = '') {
        $cross_id    = (int) $cross_id;
        $identity_id = (int) $identity_id;
        $title       = @mysql_real_escape_string(trim($title));
        $description = @mysql_real_escape_string(trim($description));
        if ($cross_id && $identity_id) {
            $isResult = $this->query(
                "INSERT INTO `votes` SET
                 `cross_id`    =  {$cross_id},
                 `status`      =  1,
                 `title`       = '{$title}',
                 `description` = '{$description}',
                 `vote_type`   = '{$type}',
                 `created_by`  =  {$identity_id},
                 `updated_by`  =  {$identity_id},
                 `created_at`  =  NOW(),
                 `updated_at`  =  NOW()"
            );
            if ($isResult && ($id = (int) $isResult['insert_id'])) {
                return $id;
            }
        }
        return null;
    }


    public function getVoteIdsByCrossId($cross_id) {
        $cross_id = (int) $cross_id;
        if ($cross_id) {
            $dbResult = $this->getColumn(
                "SELECT `id` FROM `votes` WHERE `cross_id` = {$cross_id}"
            );
            $ids = [];
            foreach ($dbResult ?: [] as $item) {
                $ids[] = $item;
            }
            return $ids;
        }
        return null;
    }


    public function getVoteById($id) {

    }


    public function updateVoteRaw($id, $identity_id, $sql = '') {
        return $this->query(
            "UPDATE `votes` SET
             `updated_by` = {$identity_id},
             `updated_at` = NOW() {$sql}
             WHERE `id`   = {$id}"
        );
    }


    public function updateVote($id, $identity_id, $title = null, $description = null, $status = null) {
        $id          = (int) $id;
        $identity_id = (int) $identity_id;
        $intStatus   = (int) $status;
        $strTitle    = @mysql_real_escape_string(trim($title));
        $strDesc     = @mysql_real_escape_string(trim($description));
        if ($id && $identity_id) {
            $sqlAppend = '';
            if ($title       !== null) {
                $sqlAppend .= ", `title`       = '{$strTitle}'";
            }
            if ($description !== null) {
                $sqlAppend .= ", `description` = '{$strDesc}'";
            }
            if ($status      !== null) {
                $sqlAppend .= ", `status`      =  {$intStatus}";
            }
            if ($sqlAppend) {
                return $this->updateVoteRaw($id, $identity_id, $sqlAppend);
            }
        }
        return null;
    }


    public function changeVoteStatus($id, $identity_id, $status) {
        return $this->updateVote($id, $identity_id, null, null, $status);
    }


    public function closeVote($id, $identity_id) {
        return $this->changeVoteStatus($id, $identity_id, 4);
    }


    public function addVoteOption($vote_id, $identity_id, $data, $title = '') {
        $vote_id     = (int) $vote_id;
        $identity_id = (int) $identity_id;
        $title       = @mysql_real_escape_string(trim($title));
        $data        = @mysql_real_escape_string(json_encode($data));
        if ($vote_id && $identity_id && $data) {
            $isResult = $this->query(
                "INSERT INTO `vote_options` SET
                 `title`      = '{$title}',
                 `data`       = '{$data}',
                 `vote_id`    = '{$vote_id}',
                 `created_by` =  {$identity_id},
                 `updated_by` =  {$identity_id},
                 `created_at` =  NOW(),
                 `updated_at` =  NOW()"
            );
            if ($isResult && ($id = (int) $isResult['insert_id'])) {
                $this->updateVoteRaw($vote_id, $identity_id);
                return $id;
            }
        }
        return null;
    }


    public function updateVoteOption($id, $identity_id, $data = null, $title = null) {
        $id          = (int) $id;
        $identity_id = (int) $identity_id;
        $strTitle    = @mysql_real_escape_string(trim($title));
        $strData     = @mysql_real_escape_string(json_encode($data));
        if ($vote_id && $identity_id) {
            $sqlAppend = '';
            if ($title !== null) {
                $sqlAppend .= ", `title` = '{$strTitle}'";
            }
            if ($data  !== null) {
                $sqlAppend .= ", `data`  = '{$strData}'";
            }
            if ($sqlAppend) {
                $dbResult = $this->getRow(
                    "SELECT `vote_id` FROM `vote_options` WHERE `id` = {$id}"
                );
                if ($dbResult && ($vote_id = @ (int) $dbResult['vote_id'])) {
                    $upResult = $this->query(
                        "UPDATE `vote_options` SET
                         `updated_by` = {$identity_id},
                         `updated_at` = NOW() {$sql}
                         WHERE `id`   = {$id}"
                    );
                    if ($upResult) {
                        $this->updateVoteRaw($vote_id, $identity_id);
                        return $upResult;
                    }
                }
            }
        }
        return null;
    }


    public function removeVoteOption($id, $identity_id) {
        $id          = (int) $id;
        $identity_id = (int) $identity_id;
        if ($vote_id && $identity_id) {
            $dbResult = $this->getRow(
                "SELECT `vote_id` FROM `vote_options` WHERE `id` = {$id}"
            );
            if ($dbResult && ($vote_id = @ (int) $dbResult['vote_id'])) {
                $upResult = $this->query(
                    "DELETE FROM `vote_options` WHERE `id` = {$id}"
                );
                if ($upResult) {
                    $this->updateVoteRaw($vote_id, $identity_id);
                    return $upResult;
                }
            }
        }
        return null;
    }


    public function vote($id, $identity_id, $action = 'AGREE') {
        if (in_array($action, ['', 'AGREE', 'DISAGREE'])) {
            $hlpResponse = $this->getHelperByName('Response');
            return $hlpResponse->rresponseToObject('vote', $id, $identity_id, $action);
        }
        return null;
    }

}
