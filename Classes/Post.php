<?php
class Post extends Metainfo{

    public $content       = null;
    public $postable_id   = null;
    public $postable_type = null;
    public $via           = null;

    public function __construct($id,$identity,$content, $postable_id,$postable_type,$via) {
        parent::__construct($id,"Post");

        $this->by_identity = $identity;
        $this->content=$content;
        $this->postable_id=$postable_id;
        $this->postable_type=$postable_type;
        $this->via=$via;
    }

}
