<?php

class HomeActions extends ActionController {

    public function doIndex() {
        $modBackground = $this->getModelByName('background');
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->displayView();
    }

}

