<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeIdentityActiveCode extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `user_identity` ADD `activecode` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
        $this->query($sql);

        $sql = "SELECT id, activecode FROM identities";
        $row = $this->getAll($sql);
        print_r($row);
        if(is_array($row)){
            foreach($row as $v){
                $sql = "UPDATE user_identity SET activecode='".$v['activecode']."' WHERE identityid=".$v['id'];
                $this->query($sql);
            }
        }
        echo "upgrade success....\r\n";
        $sql = "ALTER TABLE `identities` DROP `activecode`";
        $this->query($sql);
    }
}

$upgradeObj = new UpgradeIdentityActiveCode();
$upgradeObj->run();
?>
