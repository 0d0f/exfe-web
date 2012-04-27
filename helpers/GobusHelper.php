<?php
date_default_timezone_set('GMT');
require_once 'lib/Redisent/Redisent.php';


class GobusHelper extends ActionController {
    
    public $redis = null;
    
    
    public function __construct() {
        $this->setBackend(RESQUE_SERVER);
    }
    
    
    public function setBackend($server) {
        list($host, $port) = explode(':', $server);
        $this->redis = new Redisent($host, $port);
    }


    public function send($queue_name, $method, $arg, $max_retry = 5) {
        $queue = "gobus:queue:{$queue_name}"
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
