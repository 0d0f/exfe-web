<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradePlaceDB extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `places` ADD `provider` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `place_line2` , ADD `external_id` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `provider` , ADD `lng` FLOAT NOT NULL AFTER `external_id` , ADD `lat` FLOAT NOT NULL AFTER `lng` ";
        $this->query($sql);
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradePlaceDB();
$upgradeObj->run();
?>
