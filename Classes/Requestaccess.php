<?php

class Requestaccess extends Widget {

    public $requests = [];


    public function __construct($id = 0, $requests = []) {
        parent::__construct((int) $id, 'requestaccess');

        $this->requests = $requests;
    }

}
