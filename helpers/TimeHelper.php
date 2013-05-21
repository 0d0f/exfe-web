<?php

class TimeHelper extends ActionController {

    protected $modTime = null;


    public function __construct() {
        $this->modTime = $this->getModelByName('Time');
    }


    public function parseTimeString($string, $timezone) {
        return $this->modTime->parseTimeString($string, $timezone);
    }


    public function getTimezoneNameByRaw($timezone) {
        return $this->modTime->getTimezoneNameByRaw($timezone);
    }

}
