<?php

class IdentityHelper extends ActionController {

    public function sentVerifyingEmail($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","emailverifying_job" , $args, true);
            return $jobId;
    }


    public function sentWelcomeAndActiveEmail($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","welcomeandactivecode_job" , $args, true);
            return $jobId;
    }


    public function cleanIdentityBadgeNumber($device_identity_id,$uid)
    {
        //deviceToken
        $identityData = $this->getModelByName('identity');
        $belongs=$identityData->ifIdentityIdBelongsUser($device_identity_id,$uid);
        if(intval($belongs) > 0)
        {
            $identity=$identityData->getIdentityById($device_identity_id);
            $devicetoken=$identity["external_identity"];
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            $redis->HSET("iospush_badgenumber",$devicetoken,0);
        }
    }


    // upgraded
    public function sendResetPassword($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","emailresetpassword_job" , $args, true);
            return $jobId;
    }

}


