<?php

class PlaceHelper extends ActionController {

    public function savePlace($place) {
        $placedata = $this->getModelByName('place');
        return $placedata->savePlace($place);
    }

}
