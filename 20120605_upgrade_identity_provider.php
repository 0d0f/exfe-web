<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeIdentities extends DataModel {

    public function run() {
        $identities = $this->getAll(
            "SELECT `id`, `provider`, `external_identity` FROM `identities` WHERE `provider` <> 'email'"
        );
        // loop
        foreach ($identities as $item) {
            $item['external_identity'] = preg_replace("/({$item['provider']}_)/", '', $item['external_identity']);
            $this->query(
        		"UPDATE `identities` SET `external_identity` = '{$item['external_identity']}' WHERE `id` = {$item['id']}"
        	);
        }
        //
        echo "Done.\r\n";
    }

}

$upgradeObj = new UpgradeIdentities();
$upgradeObj->run();
