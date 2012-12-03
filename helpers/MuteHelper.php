<?php

class MuteHelper extends ActionController {

    protected $modMute = null;


    public function __construct() {
        $this->modMute = $this->getModelByName('Mute');
    }


    public function getMute($cross_id, $user_id) {
        return $this->modMute->getMute($cross_id, $user_id);
    }


    public function setMute($cross_id, $user_id) {
        return $this->modMute->setMute($cross_id, $user_id);
    }

}
