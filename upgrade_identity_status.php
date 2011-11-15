<?php
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeIdentityStatus extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `user_identity` ADD `status` INT( 11 ) NOT NULL DEFAULT '2' COMMENT '3 connected 1 relatived/disconnected 2 veryifing'";
        $this->query($sql);

        $sql = "select id, status from identities";
        $row = $this->getAll($sql);
        if(is_array($row)){
            foreach($row as $v){
                $sql = "UPDATE user_identity set status=".$v['status']." where identityid=".$v['id'];
                $this->query($sql);
            }
        }
        echo "upgrade success....\r\n";
        //$sql = "ALTER TABLE `identities` DROP `status`";
        //$this->query($sql);
    }
}

$upgradeObj = new UpgradeIdentityStatus();
$upgradeObj->run();
?>
