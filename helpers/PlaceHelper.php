<?php

class PlaceHelper extends ActionController {

    protected $modPlace = null;


    public function __construct() {
        $this->modPlace = $this->getModelByName('Place');
    }


    public function validatePlace($place) {
        return $this->modPlace->validatePlace($place);
    }

}
