<?php
require_once 'Metainfo.php';
require_once 'cross.php';
require_once 'post.php';

abstract class EFobject{
    
    public $id   = null;

    public $type = null;
    
    public function __construct() {

    }

}

