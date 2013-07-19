<?php

class RequestModels extends DataModel {

    protected $modIdentity = null;


    protected function packRequest($rawRequest) {
        if ($rawRequest) {
            $requested_by = $this->modIdentity->getIdentityById(
                $rawRequest['requested_by']
            );
            $updated_by   = $this->modIdentity->getIdentityById(
                $rawRequest['updated_by']
            );
            if ($requested_by && $updated_by) {
                return new Request(
                    $rawRequest['id'], $rawRequest['exfee_id'], $requested_by,
                    $updated_by, $rawRequest['status'], $rawRequest['message'],
                    $rawRequest['requested_at'], $rawRequest['updated_at']
                )
            }
        }
    }


    public function __construct() {
        $this->modIdentity = $this->getModelByName('Identity');
    }


    public function getRequestAccessBy($exfee_id) {
        $requests = $this->getRequestsBy($exfee_id);
        if ($requests) {
            return new Requestaccess($exfee_id, $requests);
        }
        return null;
    }


    public function request($identity_id, $exfee_id, $message = '') {
        $identity_id = (int) $identity_id;
        $exfee_id    = (int) $exfee_id;
        $message     = @mysql_real_escape_string(trim($message));
        if ($identity_id && $exfee_id) {
            $curRequest = $this->getRequestBy($identity_id, $exfee_id);
            $rawResult  = $curRequest ? $this->query(
                "UPDATE `requests` SET
                 `updated_by`   =  {$identity_id},
                 `status`       =  0,
                 `updated_at`   =  now(),
                 `message`      = '{$message}' WHERE
                 `exfee_id`     =  {$exfee_id} AND
                 `requested_by` =  {$identity_id}"
            ) : $this->query(
                "INSERT INTO `requests` SET
                 `exfee_id`     =  {$exfee_id},
                 `requested_by` =  {$identity_id},
                 `updated_by`   =  {$identity_id},
                 `status`       =  0,
                 `requested_at` =  now(),
                 `updated_at`   =  now(),
                 `message`      = '{$message}'"
            );
            if ($rawResult) {
                return $this->getRequestBy($identity_id, $exfee_id);
            }
        }
        return null;
    }


    public function getRequestBy($identity_id, $exfee_id) {
        $identity_id = (int) $identity_id;
        $exfee_id    = (int) $exfee_id;
        if ($identity_id && $exfee_id) {
            $rawResult = $this->getRow(
                "SELECT * FROM `requests`
                 WHERE `requested_by` = {$identity_id}
                 AND   `exfee_id`     = {$exfee_id}"
            );
            if ($rawResult) {
                return $this->packRequest($rawResult);
            }
        }
        return null;
    }


    public function getRequestsBy($exfee_id) {
        $exfee_id = (int) $exfee_id;
        if ($exfee_id) {
            $rawResult = $this->getAll(
                "SELECT * FROM `requests` WHERE `exfee_id` = {$exfee_id}"
            );
            $arrResult = [];
            foreach ($rawResult ?: [] as $rrItem) {
                $arrResult[] = $this->packRequest($rrItem);
            }
            if ($arrResult) {
                return $arrResult;
            }
        }
        return null;
    }


    public function changeStatus($identity_id, $exfee_id, $status) {
        $identity_id = (int) $identity_id;
        $exfee_id    = (int) $exfee_id;
        $status      = (int) $status;
        if ($identity_id && $exfee_id) {
            return $this->query(
                "UPDATE `requests`
                 SET    `status`       = {$status}
                 WHERE  `requested_by` = {$identity_id}
                 AND    `exfee_id`     = {$exfee_id}"
            );
        }
        return null;
    }


    public function approve($identity_id, $exfee_id) {
        return $this->changeStatus($identity_id, $exfee_id, 1);
    }


    public function declined($identity_id, $exfee_id) {
        return $this->changeStatus($identity_id, $exfee_id, 2);
    }


    public function giveupRequest($identity_id, $exfee_id) {
        return $this->changeStatus($identity_id, $exfee_id, 3);
    }

}
