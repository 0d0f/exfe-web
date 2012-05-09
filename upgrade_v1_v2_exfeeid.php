<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeExfeeId extends DataModel{

    public function run(){
        echo "start update crosses table...\r\n";

        $sql = "SELECT id FROM crosses where exfee_id=0 or exfee_id=1;";
        $crosses = $this->getAll($sql);
        foreach($crosses as $cross)
        {
            $cross_id=$cross["id"];
            $sql="update crosses set exfee_id=$cross_id where id=$cross_id;";
            $this->query($sql);
            echo "update table crosses $cross_id suscess...\r\n\r\n";
        }
    }
}

$upgradeObj = new UpgradeExfeeId();
$upgradeObj->run();
?>

