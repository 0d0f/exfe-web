<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeBackground extends DataModel {

    public function run() {
        $crosses = $this->getAll(
            "SELECT `id`, `background` FROM `crosses` WHERE `background` <> ''"
        );
        // loop
        foreach ($crosses as $item) {
            if ($item['background']) {
                $this->query(
                    "UPDATE `crosses` SET `background` = '{$item['background']}.jpg' WHERE `id` = {$item['id']}"
                );
            }
        }
        //
        echo "Done. 😃\r\n";
    }

}

$upgradeObj = new UpgradeBackground();
$upgradeObj->run();
