<?php

class ResponseHelper extends ActionController {

    protected $modResponse = null;


    public function __construct() {
        $this->modResponse = $this->getModelByName('Response');
    }


    public function getResponsesByObjectTypeAndObjectIds($object_type, $object_ids, $identity_id = 0) {
        return $this->modResponse->getResponsesByObjectTypeAndObjectIds($object_type, $object_ids, $identity_id);
    }


    public function responseToObject($object_type, $object_id, $identity_id, $response) {
        return $this->modResponse->responseToObject($object_type, $object_id, $identity_id, $response);
    }


    public function clearResponseBy($object_type, $object_ids, $identity_id) {
        return $this->modResponse->clearResponseBy($object_type, $object_ids, $identity_id);
    }

}
