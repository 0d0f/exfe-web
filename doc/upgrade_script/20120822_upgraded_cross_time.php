<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpgradeCrosstime extends DataModel {

    public function run() {
        $crosses = $this->getAll("SELECT * FROM `crosses`");
        // loop
        foreach ($crosses as $item) {
            // 分解老格式时间
            $item['begin_at'] = explode(' ', $item['begin_at']);
            $date             = $item['begin_at'][0] === '0000-00-00' ? '' : $item['begin_at'][0];
            $time             = $item['begin_at'][1] === '00:00:00'   ? '' : $item['begin_at'][1];
            // 如果存在时间字段
            if ($time) {
                // 处理时区
                $item['begin_at']  = strtotime("{$item['begin_at'][0]} {$item['begin_at'][1]}");
                $arrTimezone       = explode(':', $item['timezone']);
                $item['begin_at'] += (int) $arrTimezone[0] * 60 * 60;
                $item['begin_at']  = date("Y-m-d H:i:s", $item['begin_at']);
                $item['begin_at']  = explode(' ', $item['begin_at']);
                // 初始化新 Origin
                $strOrigin = $item['begin_at'][0];
                // 分解时间字段
                $arrTime = explode(':', $item['begin_at'][1]);
                $intHour = (int) $arrTime[0];
                if ($intHour < 12) {
                    $strOrigin .= " {$intHour}:$arrTime[1] AM";
                } else if ($intHour === 12) {
                    $strOrigin .= " {$intHour}:$arrTime[1] PM";
                } else if ($intHour > 12) {
                    $intHour -= 12;
                    $strOrigin .= " {$intHour}:$arrTime[1] PM";
                }
            } else {
                // 初始化新 Origin
                $strOrigin = $date;
            }
            // 更新数据
            $this->query(
                "UPDATE `crosses`
                 SET    `date`            = '{$date}',
                        `time`            = '{$time}',
                        `origin_begin_at` = '{$strOrigin}'
                 WHERE  `id`              = '{$item['id']}'"
            );
        }
        //
        echo "Done. 😃\r\n";
    }

}

$upgradeObj = new UpgradeCrosstime();
$upgradeObj->run();
