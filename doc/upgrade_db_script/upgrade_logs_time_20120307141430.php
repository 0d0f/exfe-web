<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeCrossUTCTime extends DataModel{

    public function run(){
        $time_zone = -5*60*60;

        /*
        echo "start update table logs...\r\n";
        $sql = "SELECT id, time FROM logs";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $value){
            $t_utc_time = "0000-00-00 00:00:00";
            if($value["time"] != $t_utc_time){
                $t_utc_time = date("Y-m-d H:i:s",intval(strtotime($value["time"])-$time_zone));
            }
            echo $value["id"]."|".$t_utc_time."|".$value["time"]."\r\n";
        }
        exit;
         */


        $fp = fopen("logs_utc_time", "r");
        while($s = fgets($fp,128)){
            $time_arr = explode("|", $s);
            $logs_id = $time_arr[0];
            $utc_time = $time_arr[1];
            $sql = "UPDATE logs SET time='{$utc_time}' WHERE id={$logs_id}";
            $this->query($sql);
        }

        /*
        foreach($cross_time_arr as $k=>$v){
            $t_utc_time = "0000-00-00 00:00:00";
            if($v["time"] != $t_utc_time){
                $t_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["time"])-$time_zone));
            }
            $sql = "UPDATE logs SET time='{$t_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
         */
        echo "update table logs suscess...\r\n\r\n";

        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeCrossUTCTime();
$upgradeObj->run();
?>
