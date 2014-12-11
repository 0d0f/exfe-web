<?php

class Place extends EFObject {

    public $title       = null;

    public $description = null;

    public $lng         = null;

    public $lat         = null;

    public $provider    = null;

    public $external_id = null;

    public $created_at  = null;

    public $updated_at  = null;


    public function __construct(
        $id          = 0,
        $title       = '',
        $description = '',
        $lng         = '',
        $lat         = '',
        $provider    = '',
        $external_id = '',
        $created_at  = '',
        $updated_at  = ''
    ) {
        parent::__construct(intval($id), 'Place');

        $created_at        = $created_at  ?: date('Y-m-d H:i:s');
        $updated_at        = $updated_at  && $updated_at !== '0000-00-00 00:00:00'
                           ? $updated_at   : $created_at;

        $this->title       = $title       ?: '';
        $this->description = $description ?: '';
        $this->lng         = $lng         ?: '';
        $this->lat         = $lat         ?: '';
        $this->provider    = $provider    ?: '';
        $this->external_id = $external_id ?: '';
        $this->created_at  = $created_at . ' +0000';
        $this->updated_at  = $updated_at . ' +0000';
    }

}
