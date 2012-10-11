<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeOldDatas extends DataModel {

    public function run() {
        // get all crosses
        $crosses = $this->getAll("SELECT * FROM `crosses`");
        // loop
        foreach ($crosses as $item) {
            $title = formatTitle
        }
        //
        echo "\r\nDone. 😃\r\n";
    }

}

$upgradeObj = new UpgradeOldDatas();
$upgradeObj->run();
