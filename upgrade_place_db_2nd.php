/**
 * http://code.google.com/intl/en-us/apis/maps/articles/phpsqlajax.html
 * by @Leaskh
 */

<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradePlaceDB extends DataModel{

    public function run(){
        $sql = 'ALTER TABLE `places`
                CHANGE `lng` `lng` FLOAT (12, 8) NOT NULL,
                CHANGE `lat` `lat` FLOAT (12, 8) NOT NULL;';
        $this->query($sql);
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradePlaceDB();

$upgradeObj->run();
