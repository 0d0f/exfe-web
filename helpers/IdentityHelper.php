<?php

class IdentityHelper extends ActionController {

    public $modIdentity = null;


    public function __construct() {
        $this->modIdentity = $this->getModelByName('Identity');
    }


    public function addIdentity($identityDetail = [], $user_id = 0) {
        return $this->modIdentity->addIdentity($identityDetail, $user_id);
    }


    public function checkIdentityById($id) {
        return $this->modIdentity->checkIdentityById($id);
    }


    public function getIdentityById($id) {
        return $this->modIdentity->getIdentityById($id);
    }


    public function getIdentityByProviderAndExternalUsername($provider, $external_username, $get_id_only = false) {
        return $this->modIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username, $get_id_only);
    }


    public function getIdentityByProviderExternalId($provider, $external_id) {
        return $this->modIdentity->getIdentityByExternalId($provider, $external_id);
    }


    public function getTwitterAvatarBySmallAvatar($strUrl) {
        return $this->modIdentity->getTwitterAvatarBySmallAvatar($strUrl);
    }


    public function getGoogleAvatarBySmallAvatar($strUrl) {
        return $this->modIdentity->getGoogleAvatarBySmallAvatar($strUrl);
    }


    public function getGravatarUrlByExternalUsername($external_username, $size = 80, $format = '', $fallback = '') {
        return $this->modIdentity->getGravatarUrlByExternalUsername($external_username, $size, $format, $fallback);
    }


    public function getFacebookAvatar($external_id) {
        return $this->modIdentity->getFacebookAvatar($external_id);
    }


    public function updateOAuthTokenById($identity_id, $tokens) {
        return $this->modIdentity->updateOAuthTokenById($identity_id, $tokens);
    }


    public function revokeIdentity($identity_id) {
        return $this->modIdentity->revokeIdentity($identity_id);
    }


    public function getOAuthTokenById($identity_id) {
        return $this->modIdentity->getOAuthTokenById($identity_id);
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
