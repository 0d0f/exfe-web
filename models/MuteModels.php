<?php

class MuteModels extends DataModel {

    public function getMute($cross_id, $user_id) {
        if ($cross_id && $user_id) {
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            return $redis->GET("mute:cross:{$cross_id}:{$user_id}");
        }
        return false;
    }


    public function setMute($cross_id, $user_id) {
        if ($cross_id && $user_id) {
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            return $redis->SET("mute:cross:{$cross_id}:{$user_id}", 1);
        }
        return false;
    }

}
