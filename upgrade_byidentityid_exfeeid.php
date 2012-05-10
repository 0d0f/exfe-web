<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeExfeeId extends DataModel{

    public function run(){
        echo "start update crosses table...\r\n";

        $sql = "SELECT id,host_id FROM crosses where by_identity_id=0;";
        $crosses = $this->getAll($sql);
        foreach($crosses as $cross)
        {
            $host_id=$cross["host_id"];
            $id=$cross["id"];
            $sql="update crosses set by_identity_id=$host_id where id=$id;";
            print $sql;
            $this->query($sql);
            echo "update table crosses $id suscess...\r\n\r\n";
        }
    }
}

$upgradeObj = new UpgradeExfeeId();
$upgradeObj->run();
?>

