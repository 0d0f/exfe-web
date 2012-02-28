<?php

class PlaceHelper extends ActionController {

    public function savePlace($place, $placeId = null) {
        $placedata = $this->getModelByName('place');
        return $placedata->savePlace($place, $placeId);
    }

}
