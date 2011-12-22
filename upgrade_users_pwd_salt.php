<?php
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeUsersPasswordSalt extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `users` ADD `password_salt` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'password salt' AFTER `encrypted_password`";
        $this->query($sql);
        $sql = "SELECT id,encrypted_password FROM users";
        $row = $this->getAll($sql);
        if(is_array($row)){
            foreach($row as $v){
                if(trim($v["encrypted_password"]) != ""){
                    $sql = "UPDATE users SET `password_salt`='_4f9g18t9VEdi2if' WHERE id=".$v["id"];
                    $this->query($sql);
                }
            }
        }
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeUsersPasswordSalt();
$upgradeObj->run();
?>
