<?php
class Cross extends Metainfo {

    public $title         = null;
    public $description   = null;
    public $time          = null;
    public $place         = null;
    public $attribute     = null;
    public $exfee         = null;
    public $widget        = null;
    public $host_identity = null;
    public $conversation_count = null;

    public function setRelation($relative_id,$relation)
    {
        $relative["id"]=$relative_id;
        $relative["relation"]=$relation;
        array_push($this->relative,$relative);
    }

    public function setExfee($exfee)
    {
        $this->exfee=$exfee;
    }

    public function __construct($id, $title, $description, $host_identity,$attribute, $exfee, $widget=array(),$time="", $place="",$conversation_count=0 ) {
        parent::__construct($id,"Cross");
        $this->setExfee($exfee);
        $this->host_identity=$host_identity;

        $this->attribute = $attribute;
        $this->widget    =$widget;

        $this->title       = $title;
        $this->description = $description;
        $this->conversation_count = 0;

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
