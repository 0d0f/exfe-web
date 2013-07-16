<?php

class Option extends EFObject {

    public $title      = null;

    public $data       = null;

    public $created_by = null;

    public $updated_by = null;

    public $created_at = null;

    public $updated_at = null;

    public function __construct(
        $id         = 0,
        $title      = '',
        $data       = '',
        $created_by = null,
        $updated_by = null,
        $created_at = '',
        $updated_at = ''
    ) {
        parent::__construct($id, 'option');

        $created_at       = $created_at ?: date('Y-m-d H:i:s');
        $updated_at       = $updated_at && $updated_at !== '0000-00-00 00:00:00'
                          ? $updated_at  : $created_at;

        $this->title      = $title;
        $this->data       = json_decode($data);
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at . ' +0000';
        $this->updated_at = $updated_at . ' +0000';
    }

}
