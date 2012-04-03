<?php
require_once 'meta_info.php';

class cross extends meta_info {

    public $category    = null;

    public $title       = null;

    public $description = null;

    public $time        = null;

    public $place       = null;

    public $attribute   = null;

    public $exfee_id    = null;

    public $widget      = null;

    public function __construct() {
        parent::__construct();
        $this->category = 'cross';
        $this->widget   = array();
    }

}
