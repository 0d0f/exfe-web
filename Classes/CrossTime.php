<?php

define('TIME_OUTPUT_FORMAT', '0');
define('TIME_OUTPUT_ORIGIN', '1');

class CrossTime extends EFObject{

    public $begin_at=null;
    public $origin=null;
    public $outputformat=null;


    public function __construct($date_word,$date,$time_word,$time,$timezone,$origin,$outputformat) {
        parent::__construct(0,"CrossTime");
        $this->begin_at=new EFTime($date_word,$date,$time_word,$time,$timezone);
        $this->outputformat=$outputformat;
        $this->origin=$origin ?: '';
    }


    public function stringInZone($targetTimezone) {
        $begin_at=$this->begin_at;
        if($this->outputformat==TIME_OUTPUT_FORMAT)
        {
            $timestr="";
            $time="";
            $date="";
            if($begin_at->time!="")
               $time=date("g:iA",strtotime($begin_at->time));
            if($begin_at->date!="")
               $date=date("D, M n",strtotime($begin_at->date));

            if($begin_at->time_word!="" && $begin_at->time!="")
                $timestr=$begin_at->time_word." at ".$time;
            else if($begin_at->time_word!="" || $begin_at->time!="")
                $timestr=$begin_at->time_word.$time;

            if($targetTimezone!=$begin_at->timezone && $timestr!="")
                $timestr.=" ".$begin_at->timezone;

            if($timestr!="")
                $timestr.=" ";

            $datestr="";
            if($begin_at->date_word!="" && $begin_at->date!="")
                    $datestr=$begin_at->date_word." on ".$date;
            else if($begin_at->date_word!="" || $begin_at->date!="")
            {
                if($begin_at->date!="")
                    if(($timestr)==="")
                        $datestr=$begin_at->date_word.$date;
                    else
                        $datestr="on ".$begin_at->date_word.$date;
                else
                    $datestr=$begin_at->date_word.$date;
            }

            return rtrim($timestr.trim($datestr));
        }
        else if($this->outputformat==TIME_OUTPUT_ORIGIN)
        {
            return $this->origin;
        }
    }

}


