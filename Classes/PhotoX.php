<?php

class PhotoX extends EFObject {

    public $id                = null;

    public $photos            = null;

    public $created_at        = null;

    public $updated_at        = null;


    public function __construct(
        $id         = 0,
        $photos     = [],
        $created_at = '',
        $updated_at = ''
    ) {
        parent::__construct(intval($id), 'PhotoX');

        $created_at       = $created_at ?: date('Y-m-d H:i:s');
        $updated_at       = $updated_at && $updated_at !== '0000-00-00 00:00:00'
                          ? $updated_at  : $created_at;

        $this->photos     = $photos ?: [];
        $this->created_at = $created_at . ' +0000';
        $this->updated_at = $updated_at . ' +0000';
    }

}
