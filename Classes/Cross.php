<?php
class Cross extends Metainfo {

    public $title        = null;
    public $description  = null;
    public $time         = null;
    public $place        = null;
    public $attribute    = null;
    public $exfee        = null;
    public $widget       = null;

    public function setRelation($relative_id,$relation)
    {
        $this->relative["id"]=$relative_id;
        $this->relative["relation"]=$relation;
    }
    public function setExfee($exfee)
    {
        $this->exfee=$exfee;
    }

    public function __construct($id, $title, $description, $attribute, $exfee, $widget=array(),$time="", $place="" ) {
        parent::__construct($id,"Cross");
        $this->setRelation(0,"");
        $this->setExfee($exfee);

        $this->attribute = $attribute;
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
