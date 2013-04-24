<?php

class EFTime extends EFObject {

    public $date_word = null;

    public $date      = null;

    public $time_word = null;

    public $time      = null;

    public $timezone  = null;


    public function __construct($date_word, $date, $time_word, $time, $timezone) {
        parent::__construct(0, 'EFTime');
        $this->date_word = $date_word;
        $this->date      = $date;
        $this->time_word = $time_word;
        $this->time      = $time;
        $this->timezone  = $timezone;
    }

}
