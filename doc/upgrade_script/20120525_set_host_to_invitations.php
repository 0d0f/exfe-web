<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeInvitations extends DataModel {

    public function run() {
        $crosses = $this->getAll("SELECT * FROM `crosses`");
        // loop
        foreach ($crosses as $cross) {
        	$this->query(
        		"UPDATE `invitations` SET `host` = TRUE
        		 WHERE  `cross_id`    = {$cross['exfee_id']}
        		 AND    `identity_id` = {$cross['host_id']}"
        	);
        }
        //
        echo "Done.\r\n";
    }

}

$upgradeObj = new UpgradeInvitations();
$upgradeObj->run();
