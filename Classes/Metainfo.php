<?php

abstract class Metainfo extends EFObject{
    
    public $id          = null;

    public $relative    = null;

    public $type        = null;

    public $created_at  = null;

    public $by_identity = null;
    
    public function __construct() {
        $this->relative = array();
    }

}
