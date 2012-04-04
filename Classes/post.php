<?php
class Post extends metainfo {

    public $content  = null;

    public $exfee_id = null;

    public function __construct() {
        parent::__construct();
        $this->type  = 'post';
    }

}
