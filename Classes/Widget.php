<?php

require_once 'Background.php';

abstract class Widget extends EFObject{

    public $widget_id;

    public function __construct($widget_id) {
        parent::__construct($widget_id, 'Widget');
        $this->widget_id = $widget_id;
    }

}
