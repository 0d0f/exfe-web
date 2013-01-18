<?php

class Photo extends EFObject {

    public $id          = null;

    public $caption     = null;

    public $by_identity = null;

    public $created_at  = null;

    public $updated_at  = null;

    public $provider    = null;

    public $external_id = null;

    public $location    = null;

    public $image       = null;

    public $siblings    = null;


    public function __construct($id          = 0,
                                $caption     = '',
                                $by_identity = null,
                                $created_at  = '',
                                $updated_at  = '',
                                $provider    = '',
                                $external_id = '',
                                $location    = null,
                                $image       = [],
                                $siblings    = []) {
        parent::__construct(intval($id), 'Photo');

        $created_at        = $created_at  ?: date('Y-m-d H:i:s');
        $updated_at        = $updated_at  && $updated_at !== '0000-00-00 00:00:00'
                           ? $updated_at   : $created_at;

        $this->caption     = $caption;
        $this->by_identity = $by_identity;
        $this->provider    = $provider;
        $this->external_id = $external_id;
        $this->location    = $location ?: null;
        $this->image       = $image    ?: [];
        $this->siblings    = $siblings ?: [];
        $this->created_at  = $created_at . ' +0000';
        $this->updated_at  = $updated_at . ' +0000';
    }

}
