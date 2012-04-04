<?php

abstract class Metainfo extends EFObject{
    
    public $relative    = null;

    public $created_at  = null;

    public $by_identity = null;
    
    public function __construct() {
        $this->relative = array();
    }

}
