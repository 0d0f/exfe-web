<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeXBackground extends DataModel {

    public function run() {
        $sql = "ALTER TABLE `crosses` ADD `background` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;";
        $this->query($sql);

        $sql = "CREATE TABLE `background` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `image` VARCHAR(255) NULL, PRIMARY KEY (`id`)) ENGINE = MyISAM DEFAULT CHARSET = utf8;";
        $this->query($sql);

        echo "upgrade success....\r\n";
    }

}

$upgradeObj = new UpgradeXBackground();
$upgradeObj->run();
