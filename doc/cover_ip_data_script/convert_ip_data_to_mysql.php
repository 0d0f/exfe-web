<?php
ini_set('memory_limit', '512M');
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";


class ConvertIPDataToMySQL extends DataModel{
    public function run($ipDataFile){
        //创建表。
        $sql = "CREATE TABLE IF NOT EXISTS `ip_data` (
                `start` int(11) unsigned NOT NULL,
                `end` int(11) unsigned NOT NULL,
                `region` varchar(255) NOT NULL,
                `address` varchar(255) NOT NULL,
                PRIMARY KEY (`start`,`end`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->query($sql);
        //清空数据库。
        $sql = 'TRUNCATE TABLE `ip_data`';
        $this->query($sql);
        $fp = fopen($ipDataFile, "r");
        $ipAddress = array();
        while($s = fgets($fp, 128)){
            $ipInfo = explode(" ", addslashes(trim($s)));
            $tmpArr = array();
            foreach($ipInfo as $k=>$v){
                if(trim($v) != ""){
                    $v = @iconv("GBK", "UTF-8//IGNORE", $v);
                    array_push($tmpArr, str_replace('CZ88.NET', '', trim($v)));
                }
            }
            if(count($tmpArr) == 4){
                array_push($ipAddress, $tmpArr);
            }
        }
        $insertNum = 0;
        foreach($ipAddress as $kk=>$vv){
            $intIPStart = $this->ipToInt($vv[0]);
            $intIPEnd = $this->ipToInt($vv[1]);
            $region = trim($vv[2]);
            $address = trim($vv[3]);
            if($intIPStart != 0 && $intIPEnd != 0){
                $sql = "INSERT INTO ip_data (start, end, region, address) VALUES (";
                $sql .= "{$intIPStart}, {$intIPEnd}, '{$region}', '{$address}')";
                $this->query($sql);
                $insertNum++;
                echo $sql."\r\n";
            }
        }
        echo "Insert IP data succuss NUM: {$insertNum} \r\n";
    }

    public function ipToInt($IPAddress) {
        $ipArr = explode('.', $IPAddress);
        if (count($ipArr) != 4) return 0;
        $intIP = 0;
        foreach ($ipArr as $k => $v){
            $intIP += (int)$v*pow(256, intval(3-$k));
        }
        return $intIP;
    }
}

$convertObj = new ConvertIPDataToMySQL();
$convertObj->run("ip.txt");
