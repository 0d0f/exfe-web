<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('max_input_time', 3600);
ini_set('memory_limit', '1024M');

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeGeoipDatas extends DataModel {

    public function run() {
       /*--------------+------------------+------+-----+---------+----------------+
        | Field        | Type             | Null | Key | Default | Extra          |
        +--------------+------------------+------+-----+---------+----------------+
        | id           | bigint(20)       | NO   | PRI | NULL    | auto_increment |
        | start_ip_num | int(11) unsigned | NO   |     | NULL    |                |
        | end_ip_num   | int(11) unsigned | NO   |     | NULL    |                |
        | loc_id       | int(7) unsigned  | NO   |     | NULL    |                |
        +--------------+------------------+------+-----+---------+----------------*/
        echo "Start processing IP Address datas:\r\n";
        $start_time = time();
        $this->query("DELETE FROM `geoip_blocks`");
        $filename = 'doc/GeoLiteCity_20121106/GeoLiteCity-Blocks.csv';
        $file_handle = fopen($filename, "r");
        $i = 0;
        while (!feof($file_handle)) {
            $line = fgets($file_handle);
            $i++;
            if ($i < 3) {
                continue;
            }
            if (trim($line) !== '') {
                $arrLine = explode(',', $line);
                if (!$arrLine) {
                    continue;
                }
                foreach ($arrLine as $i => $item) {
                    $arrLine[$i] = (int) trim($item, '"');
                }
                printf(
                    "loc_id = %06d, start_ip_num = %10d, end_ip_num = %10d\r\n",
                    $arrLine[2], $arrLine[0], $arrLine[1]
                );
                $this->query(
                    "INSERT INTO `geoip_blocks` SET
                     `loc_id`       = {$arrLine[2]},
                     `start_ip_num` = {$arrLine[0]},
                     `end_ip_num`   = {$arrLine[1]}"
                );
            }
        }
        fclose($file_handle);
        echo '😃Finished ' . ($i - 2) . ' items in ' . (time() - $start_time) . " seconds.\r\n\r\n";

       /*-------------+-----------------+------+-----+---------+-------+
        | Field       | Type            | Null | Key | Default | Extra |
        +-------------+-----------------+------+-----+---------+-------+
        | loc_id      | int(7) unsigned | NO   | PRI | NULL    |       |
        | country     | varchar(7)      | NO   |     | NULL    |       |
        | region      | varchar(7)      | NO   |     | NULL    |       |
        | city        | varchar(255)    | NO   |     | NULL    |       |
        | postal_code | varchar(10)     | NO   |     | NULL    |       |
        | latitude    | float(10,6)     | NO   |     | NULL    |       |
        | longitude   | float(10,6)     | NO   |     | NULL    |       |
        | metro_code  | varchar(7)      | NO   |     | NULL    |       |
        | area_code   | varchar(7)      | NO   |     | NULL    |       |
        +-------------+-----------------+------+-----+---------+-------*/
        echo "Start processing City Location datas:\r\n";
        $start_time = time();
        $this->query("DELETE FROM `geoip_locations`");
        $filename = 'doc/GeoLiteCity_20121106/GeoLiteCity-Location.csv';
        $file_handle = fopen($filename, "r");
        $i = 0;
        while (!feof($file_handle)) {
            $line = fgets($file_handle);
            $i++;
            if ($i < 3) {
                continue;
            }
            if (trim($line) !== '') {
                $arrLine = explode(',', $line);
                if (!$arrLine) {
                    continue;
                }




                $arrLine[$i] = (int) trim($item, '"');
                foreach ($arrLine as $i => $item) {
                    case 0:
                        $arrLine[0] = (int) trim($item, '"');
                        break;
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 8:
                    case 9:
                        $arrLine[0] = trim($item, '"');
                        break;
                    case 6:
                    case 7:
                        $arrLine[0] = (float) trim($item, '"');
                }
                printf("loc_id = , start_ip_num = %10d, end_ip_num = %10d\r\n", $arrLine[2], $arrLine[0], $arrLine[1]);
                    "loc_id = %06d, country =, region =, city =, postal_code =, latitude =, longitude =, metro_code =, area_code =",
                    $arrLine[0], $arrLine[1], $arrLine[2], $arrLine[3], $arrLine[4], $arrLine[5], $arrLine[6], $arrLine[7], $arrLine[8], $arrLine[9]
                );

                $this->query(
                    "INSERT INTO `geoip_blocks` SET
                     `loc_id`       = {$arrLine[2]},
                     `start_ip_num` = {$arrLine[0]},
                     `end_ip_num`   = {$arrLine[1]}"
                );
            }
        }
        fclose($file_handle);
        echo '😃Finished ' . ($i - 2) . ' items in ' . (time() - $start_time) . " seconds.\r\n\r\n";



Copyright (c) 2012 MaxMind LLC.  All Rights Reserved.
locId,country,region,city,postalCode,latitude,longitude,metroCode,areaCode
1,"O1","","","",0.0000,0.0000,,
2,"AP","","","",35.0000,105.0000,,

1,"O1","","","",0.0000,0.0000,,





    }

}

$upgradeObj = new UpgradeGeoipDatas();
$upgradeObj->run();
