<?php

class Background extends Widget{

    public $image;


    public function __construct($image) {
        parent::__construct(0);
        $this->type  = 'Background';
        $this->image = $image;
    }

}
