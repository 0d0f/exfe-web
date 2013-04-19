<?php

class BackgroundModels extends DataModel {

    public function getAllBackground() {
        $images = $this->getAll('SELECT `image` FROM `background`');
        $result = array();
        foreach ($images as $iI => $iItem) {
            $result[] = $iItem['image'];
        }
        return $result;
    }

}
