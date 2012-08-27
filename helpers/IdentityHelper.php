<?php
require 'lib/Resque.php';
date_default_timezone_set('GMT');
Resque::setBackend(RESQUE_SERVER);


class IdentityHelper extends ActionController {

    protected $modIdentity = null;


    public function __construct() {
        $this->modIdentity = $this->getModelByName('Identity');
    }


    public function addIdentity($identityDetail = array(), $user_id = 0) {
        return $this->modIdentity->addIdentity($identityDetail, $user_id);
    }


    public function checkIdentityById($id) {
        return $this->modIdentity->checkIdentityById($id);
    }


    public function getIdentityById($id) {
        return $this->modIdentity->getIdentityById($id);
    }


    public function getIdentityByProviderAndExternalUsername($provider, $external_username) {
        return $this->modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
    }


    public function getIdentityByProviderExternalId($provider, $external_id) {
        return $this->modIdentity->getIdentityByExternalId($provider, $external_id);
    }


    public function getTwitterLargeAvatarBySmallAvatar($strUrl) {
        return $this->modIdentity->getTwitterLargeAvatarBySmallAvatar($strUrl);
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
