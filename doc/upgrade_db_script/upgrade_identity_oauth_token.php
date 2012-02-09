<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeIdentityOAuthToken extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `identities` ADD `oauth_token` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'oauth token'";
        $this->query($sql);
        $sql = "SELECT id,external_identity,external_username FROM identities WHERE provider='email'";
        $rows = $this->getAll($sql);
        if(is_array($rows)){
            foreach($rows as $v){
                if(trim($v["external_username"]) == ""){
                    $external_identity = $v["external_identity"];
                    $identity_id = $v["id"];
                    $sql = "UPDATE identities SET external_username='{$external_identity}' WHERE id={$identity_id}";
                    $this->query($sql);
                    echo "Upgrade success {$external_identity} ...\r\n";
                }
            }
        }
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeIdentityOAuthToken();
$upgradeObj->run();
?>
