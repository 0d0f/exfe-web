<?php
require_once 'meta_info.php';

class cross extends meta_info {

    public $title        = null;

    public $description  = null;

    public $time         = null;

    public $place        = null;

    public $attribute    = null;

    public $exfee_id     = null;

    public $widget       = null;

    public function __construct($title, $description, $time, $place, $attribute, $exfee_id, $widget) {
        parent::__construct();
        $this->type      = 'cross';
        $this->attribute = array();
        $this->widget    = array();
        
        $this->title       = $title
        $this->description = $description
        $this->time        = $time
        $this->place       = $place
    }

}
