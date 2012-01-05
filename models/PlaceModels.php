<?php

class PlaceModels extends DataModel {

    public function savePlace($place) {
        $place_lines = preg_split("/\r|\n|\r\n/", $place);
        $place_line1 = $place_lines[0];
        array_shift($place_lines);
        $place_line2 = implode("\r", $place_lines);
        $time = time();

        $place_line1 = mysql_real_escape_string($place_line1);
        $place_line2 = mysql_real_escape_string($place_line2);

        $sql = "insert into places (place_line1,place_line2,created_at,updated_at) values('$place_line1','$place_line2',FROM_UNIXTIME($time),FROM_UNIXTIME($time));";
        $result = $this->query($sql);
        if (intval($result["insert_id"]) > 0) {
            return intval($result["insert_id"]);
        }
    }


    public function getPlace($place_id) {
        $sql = "select place_line1 as line1, place_line2 as line2 from places where id=$place_id;";
        $place = $this->getRow($sql);
        return $place;
    }

}
