<?php
class Cross extends Metainfo {

    public $id_base62    = null;
    public $title        = null;
    public $description  = null;
    public $time         = null;
    public $place        = null;
    public $attribute    = null;
    public $exfee        = null;
    public $widget       = null;
    public $host_identity = null;

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
    
    public function base62Id($id) {
        return int_to_base62($id);
    }

    public function __construct($id, $title, $description, $host_identity,$attribute, $exfee, $widget=array(),$time="", $place="" ) {
        parent::__construct($id,"Cross");
        $this->setExfee($exfee);
        $this->id_base62 = $id ? $this->base62Id($id) : '';
        $this->host_identity=$host_identity;
        
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
