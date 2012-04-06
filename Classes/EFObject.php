<?php
require_once 'Metainfo.php';
require_once 'Widget.php';
require_once 'User.php';
require_once 'Identity.php';

abstract class EFobject{
    
    public $id   = null;

    public $type = null;
    
    public function __construct($id=0,$type="EFObject") {
        $this->id=$id;
        $this->type=$type;

    }

}

