<?php
require_once 'meta_info.php';

class post extends meta_info {

    public $content  = null;

    public $exfee_id = null;

    public function __construct() {
        parent::__construct();
        $this->type  = 'post';
    }

}
