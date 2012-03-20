<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeXBackground extends DataModel {

    public function run() {
        // get all crosses
        $sql    = "SELECT `id` FROM `crosses`;";
        $xes    = $this->getAll($sql);

        // get all backgrounds
        $sql    = "SELECT `image` FROM `background`;";
        $rawImg = $this->getAll($sql);
        $images = array();
        foreach ($rawImg as $iI => $iItem) {
            $images[] = $iItem['image'];
        }

        // apply backgrounds into crosses
        foreach ($xes as $xI => $xItem) {
            $background = $images[rand(0, count($images) - 1)];
            $sql = "UPDATE `crosses` SET `background` = '{$background}' WHERE `id` = {$xItem['id']};";
            $this->query($sql);
        }

        echo "upgrade success....\r\n";
    }

}

$upgradeObj = new UpgradeXBackground();
$upgradeObj->run();
