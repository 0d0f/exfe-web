<?php
require_once 'Background.php';

abstract class Widget extends EFObject{
    
    public $widget_id;

    public function __construct($widget_id) {
        $this->type="widget";
        $this->widget_id=$widget_id;
    }

}

