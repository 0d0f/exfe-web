<?php
date_default_timezone_set('GMT');


class GobusHelper extends ActionController {

    public $redis = null;


    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
    }


    public function send($queue_name, $method, $arg, $max_retry = 5) {
        $queue = "gobus:queue:{$queue_name}";
        $id    = $this->redis->incr("{$queue}:idcount");
        $meta  = array(
            'id'        => "{$queue}:{$id}",
            'method'    => $method,
            'arg'       => $arg,
            'maxRetry'  => intval($max_retry),
            'needReply' => false
        );
        $data = json_encode($method).json_encode($meta);
        $this->redis->rpush($queue, $data);
    }

}
