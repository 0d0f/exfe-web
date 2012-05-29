<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';
// require_once dirname(__FILE__) . '/models/v2/IdentityModels.php';


class UpgradeIdentityAvatar extends DataModel {

    public function run() {
        // get all identities
        $ids = $this->getAll(
            "SELECT `id`, `name`, `external_identity`, `external_username`, `avatar_file_name` FROM `identities`"
        );
        // loop
        foreach ($ids as $id) {
            $name = $id['name'] ?: $id['external_username'];
            if ($id['avatar_file_name'] && !preg_match('/80_80_default\.png/', strtolower($id['avatar_file_name']))) {
                echo ":) Identity {$id['id']} : {$id['external_identity']} : {$name} is already having an avatar.\r\n";
                continue;
            }
            $this->query("UPDATE `identities` SET `avatar_file_name` = '' WHERE `id` = {$id['id']}");
            echo ":) Successful delete old default avatar for {$id['id']} : {$id['external_identity']} : {$name}.\r\n";
        }
        //
        echo "Render finished....\r\n";
    }

}

$upgradeObj = new UpgradeIdentityAvatar();
$upgradeObj->run();
