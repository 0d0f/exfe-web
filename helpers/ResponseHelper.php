<?php

class ResponseHelper extends ActionController {

    protected $modResponse = null;


    public function __construct() {
        $this->modResponse = $this->getModelByName('Response');
    }


    public function getResponsesByObjectTypeAndObjectIds($object_type, $object_ids, $identity_id = 0) {
        $this->modResponse->getResponsesByObjectTypeAndObjectIds($object_type, $object_ids, $identity_id);
    }

}
