<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";


class ConvertIPDataToMySQL extends DataModel{
    public function run(){
        $sql = "SELECT * FROM `ip_data` WHERE region LIKE '%北京交通大学%'";
        $tsinghuaResult = $this->getAll($sql);
        if(count($tsinghuaResult) != 0){
            foreach($tsinghuaResult as $k=>$v){
                $address = $v["region"].$v["address"];
                $sql = "update `ip_data` set region='北京市', address='{$address}' where start=".$v["start"]." and end=".$v["end"];
                $this->query($sql);
                echo $sql."\r\n";
            }
        }

        echo "Insert IP data succuss NUM: {$insertNum} \r\n";
    }


}

$convertObj = new ConvertIPDataToMySQL();
$convertObj->run();
