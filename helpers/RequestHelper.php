<?php

class RequestHelper extends ActionController {

    protected $modRequest = null;


    public function __construct() {
        $this->modRequest = $this->getModelByName('Request');
    }


    public function changeStatus(
        $request_id     = 0,
        $identity_id    = 0,
        $exfee_id       = 0,
        $status         = 0,
        $by_identity_id = 0
    ) {
        return $this->modRequest->changeStatus(
            $request_id, $identity_id, $exfee_id, $status, $by_identity_id
        );
    }

}
