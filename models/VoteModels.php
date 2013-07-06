<?php

class VoteModels extends DataModel {

    public function createVote($cross_id, $identity_id, $title = '', $description = '', $type = '') {
        $cross_id    = (int) $cross_id;
        $identity_id = (int) $identity_id;
        $title       = @mysql_real_escape_string(trim($title));
        $description = @mysql_real_escape_string(trim($description));
        $type        = @mysql_real_escape_string(trim($type));
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


    public function getCrossIdByVoteId($vote_id) {
        $vote_id = (int) $vote_id;
        if ($vote_id) {
            $dbResult = $this->getRow(
                "SELECT `cross_id` FROM `votes` WHERE `id` = {$vote_id}"
            );
            if ($dbResult && @$dbResult['cross_id']) {
                return (int) @$dbResult['cross_id'];
            }
        }
        return null;
    }


    public function getVoteById($id, $withResponses = true) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $rawVote     = $this->getRow("SELECT * FROM `votes` WHERE `id` = {$id}");
        $created_by  = $hlpIdentity->getIdentityById($rawVote['created_by']);
        $updated_by  = $hlpIdentity->getIdentityById($rawVote['updated_by']);
        if ($rawVote && $created_by && $updated_by) {
            $vote = new Vote(
                $rawVote['id'],
                $rawVote['status'],
                $rawVote['title'],
                $rawVote['description'],
                $rawVote['vote_type'],
                $created_by,
                $updated_by,
                $rawVote['created_at'],
                $rawVote['updated_at']
            );
            $rawOptions = $this->getAll(
                "SELECT * FROM `vote_options` WHERE `vote_id` = {$id}"
            );
            $optionIds  = [];
            foreach ($rawOptions ?: [] as $item) {
                $created_by = $hlpIdentity->getIdentityById($item['created_by']);
                $updated_by = $hlpIdentity->getIdentityById($item['updated_by']);
                $optionIds[]     = $item['id'];
                $vote->options[] = new Option(
                    $item['id'],
                    $item['title'],
                    $item['data'],
                    $created_by,
                    $updated_by,
                    $item['created_at'],
                    $item['updated_at']
                );
            }
            if ($withResponses) {
                $vote->responses = $this->getResponsesByVoteId($optionIds);
            }
            return $vote;
        }
        return null;
    }


    public function getResponsesByVoteId($vote_ids) {
        $hlpResponse  = $this->getHelperByName('Response');
        $rawResponses = $hlpResponse->getResponsesByObjectTypeAndObjectIds(
            'vote', $vote_ids
        );
        $result = [];
        foreach ($rawResponses ?: [] as $rsItem) {
            if ($rsItem->response === 'AGREE') {
                if (!isset($result["{$rsItem->object_id}"])) {
                    $result["{$rsItem->object_id}"] = [];
                }
                $result["{$rsItem->object_id}"][] = $rsItem;
            }
        }
        return $result;
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
                 `vote_id`    =  {$vote_id},
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
        if ($id && $identity_id) {
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
                         `updated_at` = NOW() {$sqlAppend}
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
        if ($id && $identity_id) {
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
        if (in_array($action, ['', 'AGREE'])) {
            $hlpResponse = $this->getHelperByName('Response');
            if ($action === 'AGREE') {
                $rawOptions = $this->getAll(
                    "SELECT * FROM `vote_options` WHERE `vote_id` = {$id}"
                );
                $optionIds  = [];
                foreach ($rawOptions ?: [] as $item) {
                    $optionIds[] = $item['id'];
                }
                $hlpResponse->clearResponseBy('vote', $optionIds, $identity_id);
            }
            return $hlpResponse->responseToObject('vote', $id, $identity_id, $action);
        }
        return null;
    }

}
