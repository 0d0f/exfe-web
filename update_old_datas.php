<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeOldDatas extends DataModel {

    public function run() {
        // parse all crosses
        echo "Start checking cross datas:\r\n";
        $crosses = $this->getAll("SELECT * FROM `crosses`");
        // loop
        foreach ($crosses as $item) {
            echo "Checking cross: {$item['id']}... ";
            $title           = formatTitle($item['title'], 233);
            $description     = formatDescription($item['description'], 0);
            $origin_begin_at = formatTitle($item['origin_begin_at']);
            if ($title           !== $item['title']
             || $description     !== $item['description']
             || $origin_begin_at !== $item['origin_begin_at']) {
                $title           = mysql_real_escape_string($title);
                $description     = mysql_real_escape_string($description);
                $origin_begin_at = mysql_real_escape_string($origin_begin_at);
                $sql = "UPDATE `crosses`
                        SET    `title`           = '{$title}',
                               `description`     = '{$description}',
                               `origin_begin_at` = '{$origin_begin_at}'
                        WHERE  `id`              =  {$item['id']}";
                echo '[UPDATED]';
                // $this->query(
                // );
            } else {
                echo '[OK]';
            }
            echo "\r\n";
        }

        // parse all places
        echo "Start checking place datas:\r\n";
        $places = $this->getAll("SELECT * FROM `places`");
        // loop
        print_r($places);
        foreach ($places as $item) {
            echo "Checking place: {$item['id']}...";
            $place_line1 = formatTitle($place_line1);
            $place_line2 = formatDescription($place_line2);
            if ($place_line1 !== $item['place_line1']
             || $place_line2 !== $item['place_line2']) {
                $place_line1 = mysql_real_escape_string($place_line1);
                $place_line2 = mysql_real_escape_string($place_line2);
                $sql = "UPDATE `places`
                        SET    `place_line1`     = '{$place_line1}',
                               `place_line2`     = '{$place_line2}'
                        WHERE  `id`              =  {$item['id']}";
                echo '[UPDATED]';
                // $this->query(
                // );
            } else {
                echo '[OK]';
            }
            echo "\r\n";
        }

        // parse all times
        echo "Start checking place datas:\r\n";

        // done
        echo "\r\nAll Done. 😃\r\n";
    }

}

$upgradeObj = new UpgradeOldDatas();
$upgradeObj->run();
