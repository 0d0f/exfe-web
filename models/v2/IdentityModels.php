<?php

class IdentityModels extends DataModel {
    
    private $salt = '_4f9g18t9VEdi2if';
    
    
    protected function packageIdentity($rawIdentity) {
        if ($rawIdentity) {
            $rawUserIdentity = $this->getRow(
                "SELECT * FROM `user_identity` WHERE `identityid` = {$id} AND `status` = 3"
            );
            return new Identity(
                $rawIdentity['id'],
                $rawIdentity['name'],
                '', // $rawIdentity['nickname'], // @todo;
                $rawIdentity['bio'],
                $rawIdentity['provider'],
                $rawUserIdentity ? $rawUserIdentity['userid'] : 0,
                $rawIdentity['external_identity'],
                $rawIdentity['external_username'],
                $rawIdentity['avatar_file_name'],
                $rawIdentity['avatar_updated_at'],
                $rawIdentity['created_at'],
                $rawIdentity['updated_at']
            );
        } else {
            return null;
        }
    }


    public function getIdentityById($id) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE `id` = {$id}"
        ));
    }
    

    public function getIdentityByProviderAndExternalUsername($provider, $external_username) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_username` = '{$external_username}'"
        ));
    }


    public function getIdentityByExternalId($external_id) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE `external_identity` = '{$external_id}'"
        ));
    }


    /**
     * add a new identity into database
     * all parameters allow in $identityDetail:
     * {
     *     $name,
     *     $nickname,
     *     $bio,
     *     $provider,
     *     $external_id,
     *     $external_username,
     *     $avatar_filename,
     * } 
     */
    public function addIdentity($user_id, $provider, $external_id, $identityDetail = array()) {
        // create new token
        $activecode = createToken();
        // collecting new identity informations
        $name              = trim(mysql_real_escape_string($identityDetail['name']));
        $nickname          = trim(mysql_real_escape_string($identityDetail['nickname']));
        $bio               = trim(mysql_real_escape_string($identityDetail['bio']));
        $provider          = trim(mysql_real_escape_string(strtolower($identityDetail['provider'])));
        $external_id       = trim(mysql_real_escape_string(strtolower($external_id)));
        $external_username = trim(mysql_real_escape_string($identityDetail['external_username'] ?: $external_id));
        $avatar_filename   = trim(mysql_real_escape_string($identityDetail['avatar_filename']));
        // check current identity
        $curIdentity = $this->getRow("SELECT `id` FROM `identities` WHERE `external_identity` = '{$external_id}' LIMIT 1");
        if (intval($curIdentity['id']) > 0) {
            return intval($curIdentity['id']);
        }
        // set identity default avatar as Gravatar
        if ($provider === 'email' && !$avatar_filename) {
            $avatar_filename = 'http://www.gravatar.com/avatar/' . md5($external_id) . '?d=' . urlencode(DEFAULT_AVATAR_URL);
        }
        // insert new identity into database
        $dbResult = $this->query("INSERT INTO `identities` SET
                                  `provider`          = '{$provider}',
                                  `external_identity` = '{$external_id}',
                                  `created_at`        = NOW(),
                                  `name`              = '{$name}',
                                  `bio`               = '{$bio}',
                                  `avatar_file_name`  = '{$avatar_filename}',
                                  `avatar_updated_at` = NOW(),
                                  `external_username` = '{$external_username}'");
        $id = intval($dbResult['insert_id']);
        // update user information
        if ($id) {
            $userInfo = $this->getRow("SELECT `name`, `bio`, `avatar_file_name` FROM `users` WHERE `id` = {$user_id}");
            $userInfo['name']             = $userInfo['name']             === '' ? $name            : $userInfo['name'];
            $userInfo['bio']              = $userInfo['bio']              === '' ? $bio             : $userInfo['bio'];
            $userInfo['avatar_file_name'] = $userInfo['avatar_file_name'] === '' ? $avatar_filename : $userInfo['avatar_file_name'];
            // @todo: commit these two query as a transaction
            $this->query("UPDATE `users` SET
                          `name`             = '{$userInfo['name']}',
                          `bio`              = '{$userInfo["bio"]}',
                          `avatar_file_name` = '{$userInfo['avatar_file_name']}',
                          `default_identity` =  {$id}
                          WHERE `id`         =  {$user_id}");
            $this->query("INSERT INFO `user_identity` SET
                          `identityid` =  {$id},
                          `userid`     =  {$user_id},
                          `created_at` = NOW(),
                          `activecode` = '{$activecode}'");
            // make verify token
            $verifyToken = packArray(array('identity_id' => $id, 'activecode' => $activecode));
            // send welcome and active email
            if ($provider === 'email') {
                $hlpIdentity = $this->getHelperByName('identity', 'v2');
                $hlpIdentity-> sentWelcomeAndActiveEmail(array(
                    'identityid'        => $id,
                    'external_identity' => $external_id,
                    'name'              => $name,
                    'avatar_file_name'  => $avatar_filename,
                    'activecode'        => $activecode,
                    'token'             => $verifyToken
                ));
            }
            return $id;
        }
        return null;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

}
