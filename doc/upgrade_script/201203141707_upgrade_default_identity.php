<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeDefaultIdentity extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `users` ADD `default_identity` INT(11) NOT NULL";
        $this->query($sql);

        $sql = "SELECT identityid, userid FROM user_identity";
        $user_identity_arr = $this->getAll($sql);
        foreach($user_identity_arr as $value){
            $sql = "UPDATE `users` SET `default_identity`=".$value["identityid"]." WHERE `id`=".$value["userid"];
            $this->query($sql);
            echo $sql."\r\n";
            $this->query($sql);
        }
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeDefaultIdentity();
$upgradeObj->run();
?>
