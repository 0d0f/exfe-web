<?php
require_once 'Cross.php';
require_once 'CrossTime.php';
require_once 'Place.php';

abstract class Metainfo extends EFObject{
    
    public $relative_id          = null;
    public $relation = null;
    public $type        = null;
    public $created_at  = null;
    public $by_identity = null;
    
    public function __construct() {
        $this->type="metainfo";
        $this->relative = array();
    }

}
