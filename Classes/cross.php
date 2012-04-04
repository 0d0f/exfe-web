<?php
class Cross extends Metainfo {

    public $title        = null;
    public $description  = null;
    public $time         = null;
    public $place        = null;
    public $attribute    = null;
    public $exfee_id     = null;
    public $widget       = null;

    public function setRelation($relative_id,$relation)
    {
        $this->relative_id=$relative_id;
        $this->$relation=$relation;
    }

    public function __construct($title, $description, $time="", $place="", $attribute, $exfee_id, $widget=array()) {
        parent::__construct();
        $this->type      = 'cross';
        $this->attribute = array();
        $this->widget    =$widget;
        
        $this->title       = $title;
        $this->description = $description;

        if($time=="")
            $this->time        = new CrossTime();
        else
            $this->time=$time;

        if($place=="")
            $this->place       = new Place();
        else
            $this->place=$place;

    }

}
