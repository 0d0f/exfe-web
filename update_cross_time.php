<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeCrossTime extends DataModel {

    public function run() {
        $times = $this->getAll("SELECT `id`, `date` FROM `crosses`");
        // loop
        foreach ($times as $time) {
            if ($time['date'] && ($date = strtotime($time['date']))) {
                echo "Updating Cross time: {$time['id']}\r\n";
                $date = date('Y-m-d', $date);
                $this->query(
                    "UPDATE `crosses` SET `date` = '{$date}' WHERE `id` = {$time['id']}"
                );
            }
        }
        //
        echo "\r\nDone. 😃\r\n";
    }

}

$upgradeObj = new UpgradeCrossTime();
$upgradeObj->run();
