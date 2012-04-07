<?php

class Place extends EFObject{
    
    public $title=null;
    public $description=null;
    public $lng=0.0;
    public $lat=0.0;
    public $provider=null;
    public $external_id=0;
    public $created_at=null;
    public $updated_at=null;

    public function __construct($title="",$description="",$lng=0.0,$lat=0.0,$provider="",$external_id="",$created_at="") {
        $this->title=$title;
        $this->description=$description;
        $this->type="Place";
        $this->lng=$lng;
        $this->lat=$lat;
        $this->provider=$provider;
        $this->external_id=$external_id;
        if($created_at="")
            $this->created_at=time();
        $this->updated_at=time();
    }
}



