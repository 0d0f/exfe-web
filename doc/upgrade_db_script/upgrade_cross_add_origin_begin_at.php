<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradePlaceDB extends DataModel {

    public function run() {
        $sql = 'ALTER TABLE `crosses` ADD `origin_begin_at` datetime DEFAULT NULL;';
        $this->query($sql);

        echo "upgrade success....\r\n";
    }

}

$upgradeObj = new UpgradePlaceDB();

$upgradeObj->run();
