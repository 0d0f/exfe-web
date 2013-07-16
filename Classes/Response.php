<?php

class Response extends EFObject {

    public $object_type = null;

    public $object_id   = null;

    public $response    = null;

    public $by_identity = null;

    public $created_at  = null;

    public $updated_at  = null;


    public function __construct(
        $id          = 0,
        $object_type = 0,
        $object_id   = 0,
        $response    = '',
        $by_identity = null,
        $created_at  = '',
        $updated_at  = ''
    ) {
        parent::__construct($id, 'Response');

        $created_at        = $created_at ?: date('Y-m-d H:i:s');
        $updated_at        = $updated_at && $updated_at !== '0000-00-00 00:00:00'
                           ? $updated_at  : $created_at;

        $this->object_type = $object_type;
        $this->object_id   = $object_id;
        $this->response    = $response;
        $this->by_identity = $by_identity;
        $this->created_at  = $created_at . ' +0000';
        $this->updated_at  = $updated_at . ' +0000';
    }

}
