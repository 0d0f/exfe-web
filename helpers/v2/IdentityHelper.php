<?php
require 'lib/Resque.php';
date_default_timezone_set('GMT');
Resque::setBackend(RESQUE_SERVER);


class IdentityHelper extends ActionController {

    public function sentVerifyingEmail($args) {
        return Resque::enqueue('email', 'emailverifying_job', $args, true);
    }


    public function sendResetPassword($args) {
        return Resque::enqueue('email', 'emailresetpassword_job', $args, true);
    }


    public function sentWelcomeAndActiveEmail($args) {
        return Resque::enqueue('email', 'welcomeandactivecode_job', $args, true);
    }


    public function cleanIdentityBadgeNumber($device_identity_id, $user_id) {
        //device token
        $identityData = $this->getModelByName('identity');
        $belongs      = $identityData->ifIdentityIdBelongsUser($device_identity_id, $user_id);
        if(intval($belongs)) {
            $identity    = $identityData->getIdentityById($device_identity_id);
            $deviceToken = $identity->external_id;
            $objRedis    = new Redis();
            $objRedis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            $objRedis->HSET('iospush_badgenumber', $deviceToken, 0);
        }
    }

}
