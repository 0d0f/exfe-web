<?php

abstract class meta_info {
    
    public $id          = null;

    public $relation    = null;

    public $category    = null;
    
    public $created_at  = null;

    public $by_identity = null;
    
    public function __construct() {
        $this->relation = array();
    }

}
