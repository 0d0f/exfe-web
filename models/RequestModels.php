<?php

class RequestModels extends DataModel {

    protected $hlpIdentity = null;


    protected function packRequest($rawRequest) {
        if ($rawRequest) {
            $requested_by = $this->hlpIdentity->getIdentityById(
                $rawRequest['requested_by']
            );
            $updated_by   = $this->hlpIdentity->getIdentityById(
                $rawRequest['updated_by']
            );
            if ($requested_by && $updated_by) {
                return new Request(
                    $rawRequest['id'], $rawRequest['exfee_id'], $requested_by,
                    $updated_by, $rawRequest['status'], $rawRequest['message'],
                    $rawRequest['requested_at'], $rawRequest['updated_at']
                );
            }
        }
        return null;
    }


    public function __construct() {
        $this->hlpIdentity = $this->getHelperByName('Identity');
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
            $curRequest = $this->getRequestBy(0, $identity_id, $exfee_id);
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
                return $this->getRequestBy(0, $identity_id, $exfee_id);
            }
        }
        return null;
    }


    public function getRequestBy($request_id = 0, $identity_id = 0, $exfee_id = 0) {
        $request_id  = (int) $request_id;
        $identity_id = (int) $identity_id;
        $exfee_id    = (int) $exfee_id;
        if ($request_id) {
            $sqlAppend = "`id` = {$request_id}";
        } else if ($identity_id && $exfee_id) {
            $sqlAppend = "`requested_by` = {$identity_id} AND `exfee_id` = {$exfee_id}";
        } else {
            return null;
        }
        $rawResult = $this->getRow("SELECT * FROM `requests` WHERE {$sqlAppend}");
        return $this->packRequest($rawResult);
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


    public function changeStatus($request_id = 0, $identity_id = 0, $exfee_id = 0, $status = 0) {
        $request_id  = (int) $request_id;
        $identity_id = (int) $identity_id;
        $exfee_id    = (int) $exfee_id;
        $status      = (int) $status;
        if ($request_id) {
            $sqlAppend = "`id` = {$request_id}";
        } else if ($identity_id && $exfee_id) {
            $sqlAppend = "`requested_by` = {$identity_id} AND `exfee_id` = {$exfee_id}";
        } else {
            return null;
        }
        $rqResult = $this->query(
            "UPDATE `requests`
             SET    `status` = {$status}, `updated_at` = NOW()
             WHERE {$sqlAppend}"
        );
        return $rqResult
             ? $this->getRequestBy($request_id, $identity_id, $exfee_id)
             : false;
    }


    public function approve($request_id) {
        return $this->changeStatus($request_id, 0, 0, 1);
    }


    public function decline($request_id) {
        return $this->changeStatus($request_id, 0, 0, 2);
    }


    public function giveupRequest($identity_id, $exfee_id) {
        return $this->changeStatus($identity_id, $exfee_id, 3);
    }

}
