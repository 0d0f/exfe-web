<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeCrossUTCTime extends DataModel{

    public function run(){
        $time_zone = -5*60*60;

        echo "start update crosses table...\r\n";
        $sql = "SELECT id, begin_at, end_at FROM crosses";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $b_utc_time = "0000-00-00 00:00:00";
            $e_utc_time = "0000-00-00 00:00:00";
            if($v["begin_at"] != $b_utc_time){
                $b_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["begin_at"])-$time_zone));
            }
            if($v["end_at"] != $e_utc_time){
                $e_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["end_at"])-$time_zone));
            }
            $sql = "UPDATE crosses SET begin_at='{$b_utc_time}', end_at='{$e_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table crosses suscess...\r\n\r\n";
    }
}

$upgradeObj = new UpgradeCrossUTCTime();
$upgradeObj->run();
?>
