<?php

class BackgroundModels extends DataModel {

    public function getAllBackground() {
        $sql    = 'SELECT `image` FROM `background`;';
        $images = $this->getAll($sql);

        $result = array();
        foreach ($images as $iI => $iItem) {
            $result[] = $iItem['image'];
        }

        return $result;
    }

}
