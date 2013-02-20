<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/DataModel.php';

class UpdateCaches extends DataModel {

    public function run() {
        $redis = new Redis();
        $redis->connect(REDIS_CACHE_ADDRESS, REDIS_CACHE_PORT);
        $redis->flushall();
        //
        echo "Done.\r\n";
    }

}

$upgradeObj = new UpdateCaches();
$upgradeObj->run();
