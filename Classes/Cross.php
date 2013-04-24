<?php

class Cross extends Metainfo {

    public $title              = null;

    public $description        = null;

    public $time               = null;

    public $place              = null;

    public $attribute          = null;

    public $exfee              = null;

    public $widget             = null;

    public $conversation_count = null;


    public function setRelation($relative_id, $relation) {
        $relative['id']       = $relative_id;
        $relative['relation'] = $relation;
        array_push($this->relative, $relative);
    }


    public function setExfee($exfee) {
        $this->exfee = $exfee;
    }


    public function __construct(
        $id,
        $title,
        $description,
        $attribute,
        $exfee,
        $widget             = [],
        $time               = '',
        $place              = '',
        $conversation_count = 0
    ) {
        parent::__construct($id, 'Cross');
        $this->setExfee($exfee);
        $this->attribute          = $attribute;
        $this->widget             = $widget;

        $this->title              = $title;
        $this->description        = $description;
        $this->conversation_count = (int) $conversation_count;
        $this->time               = $time  ?: new CrossTime();
        $this->place              = $place ?: new Place();
    }

}
