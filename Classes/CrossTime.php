<?php

class CrossTime extends EFObject{
    
    public $begin_at=null;
    public $origin=null;
    public $originMark=null;

    public function __construct($date_word,$date,$time_word,$time,$timezone,$origin,$originMark) {
        parent::__construct(0,"CrossTime");
        $this->begin_at=new EFTime($date_word,$date,$time_word,$time,$timezone);
        $this->origin=$origin;
        $this->originMark=$originMark;
    }

    public function toString()
    {
    }
}


