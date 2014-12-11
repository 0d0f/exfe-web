<?php

require_once 'Cross.php';
require_once 'CrossTime.php';
require_once 'Place.php';
require_once 'Post.php';


abstract class Metainfo extends EFObject{

    public $relative    = null;

    public $type        = null;

    public $created_at  = null;

    public $by_identity = null;


    public function __construct($id = 0, $type = 'Metainfo') {
        parent::__construct($id, $type);
        $this->relative = array();
    }

}
