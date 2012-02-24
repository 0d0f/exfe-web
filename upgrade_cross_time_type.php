<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeCrossTimeType extends DataModel{

    public function run(){
        $sql = "ALTER TABLE `crosses` CHANGE `time_type` `time_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL";
        $this->query($sql);

        $sql = "SELECT id, time_type FROM crosses";
        $time_type_arr = $this->getAll($sql);
        foreach($time_type_arr as $k=>$v){
            if(intval($v["time_type"]) == 0){
                $sql = "UPDATE `crosses` SET `time_type`='' WHERE `id`=".$v["id"];
            }
            if(intval($v["time_type"]) == 1){
                $sql = "UPDATE `crosses` SET `time_type`='All day' WHERE `id`=".$v["id"];
            }
            if(intval($v["time_type"]) == 2){
                $sql = "UPDATE `crosses` SET `time_type`='Anytime' WHERE `id`=".$v["id"];
            }
            echo $sql."\r\n";
            $this->query($sql);
        }
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeCrossTimeType();
$upgradeObj->run();
?>
