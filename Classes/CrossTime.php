<?php

class CrossTime extends EFObject{
    
    public $begin_date=null;
    public $begin_time=null;
    public $origin=null;
    public $timezone=null;

    public $end_at=null;
    public $duration=null;

    public function __construct($begin_date="",$begin_time="",$origin="",$timezone="",$keyword="") {
        $this->type="CrossTime";
        $this->id=0;
        $this->begin_at=$begin_at;
        $this->timezone=$timezone;
        $this->origin=$origin;

        $this->keyword=$keyword;
        $this->end_at=$end_at;
        $this->duration=$duration;
    }

}


