<?php

class IdentityModels extends DataModel {
    
    private $salt = '_4f9g18t9VEdi2if';
    
    
    protected function packageIdentity($rawIdentity) {
        if ($rawIdentity) {
            $rawUserIdentity = $this->getRow(
                "SELECT * FROM `user_identity` WHERE `identityid` = {$rawIdentity['id']} AND `status` = 3"
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


    public function getIdentityByProviderExternalId($provider, $external_id) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_identity` = '{$external_id}'"
        ));
    }
    
    
    public function getTwitterLargeAvatarBySmallAvatar($strUrl) {
        return preg_replace(
            '/normal(\.[a-z]{1,5})$/i', 'reasonably_small$1', $strUrl
        );
    }
    
    
    public function updateIdentityById($id, $identityDetail = array()) {
        $id = intval($id);
        if (!$id || !$identityDetail['provider'] || !$identityDetail['external_id']) {
            return null;
        }
        // improve data
        if ($identityDetail['provider'] !== 'email') {
            $identityDetail['external_id'] = "{$identityDetail['provider']}_{$identityDetail['external_id']}";
        }
        switch ($identityDetail['provider']) {
            case 'twitter':
                $identityDetail['avatar_filename'] = $this->getTwitterLargeAvatarBySmallAvatar(
                    $identityDetail['avatar_filename']
                );
        }
        // check old identity
        $rawIdentity = $this->getRow(
            "SELECT `id` FROM `identities`
             WHERE  `provider`          = '{$identityDetail['provider']}'
             AND    `external_identity` = '{$identityDetail['external_id']}'"
        );
        $wasId = intval($rawIdentity['id']);
        // update identity
        $chgId = $wasId > 0 ? $wasId : $id;
        $this->query(
            "UPDATE `identities`
             SET `external_identity` = '{$identityDetail['external_id']}',
                 `name`              = '{$identityDetail['name']}',
                 `bio`               = '{$identityDetail['bio']}',
                 `avatar_file_name`  = '{$identityDetail['avatar_filename']}',
                 `external_username` = '{$identityDetail['external_username']}',
                 `updated_at`        = NOW(),
                 `avatar_updated_at` = NOW()
             WHERE `id` = {$chgId}"////////////$nickname pending and avatar_updated_at removing
        );
        // merge identity
        if ($wasId > 0 && $wasId !== $id) {
            $this->query("UPDATE `invitations`
                          SET    `identity_id` = {$wasId}
                          WHERE  `identity_id` = {$id};");
            // @todo: 可能需要更新 log by @leaskh
            $this->query("DELETE FROM `identities` WHERE `id` = {$id};");
        }
        // return
        return $chgId;
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
     * if ($user_id === 0) without adding it to a user
     */
    public function addIdentity($provider, $external_id, $identityDetail = array(), $user_id = 0) {
        // init
        if (!$provider || (!$external_id && !$identityDetail['external_username'])) {
            return null;
        }
        // collecting new identity informations
        $name              = trim(mysql_real_escape_string($identityDetail['name']));
        $nickname          = trim(mysql_real_escape_string($identityDetail['nickname']));
        $bio               = trim(mysql_real_escape_string($identityDetail['bio']));
        $provider          = trim(mysql_real_escape_string(strtolower($identityDetail['provider'])));
        $external_id       = trim(mysql_real_escape_string(strtolower($external_id)));
        $external_username = trim(mysql_real_escape_string($identityDetail['external_username'] ?: $external_id));
        $avatar_filename   = trim(mysql_real_escape_string($identityDetail['avatar_filename']));
        // check current identity
        $curIdentity = $this->getRow(
            $external_id
          ? "SELECT `id` FROM `identities` WHERE `external_identity` = '{$external_id}' LIMIT 1"
          : "SELECT `id` FROM `identities` WHERE `provider` = '{$provider}' AND `external_username` = '{$external_username}' LIMIT 1"
        );
        if (intval($curIdentity['id']) > 0) {
            return intval($curIdentity['id']);
        }
        // set identity default avatar as Gravatar
        if ($provider === 'email' && !$avatar_filename) {
            $avatar_filename = 'http://www.gravatar.com/avatar/' . md5($external_id) . '?d=' . urlencode(DEFAULT_AVATAR_URL);
        }
        // insert new identity into database
        $dbResult = $this->query(
            "INSERT INTO `identities` SET
             `provider`          = '{$provider}',
             `external_identity` = '{$external_id}',
             `created_at`        = NOW(),
             `name`              = '{$name}',
             `bio`               = '{$bio}',
             `avatar_file_name`  = '{$avatar_filename}',
             `avatar_updated_at` = NOW(),
             `external_username` = '{$external_username}'"
        );
        $id = intval($dbResult['insert_id']);
        // update user information
        if ($id) {
            if ($user_id) {
                // create new token
                $activecode = createToken();
                // do update
                $userInfo = $this->getRow("SELECT `name`, `bio`, `avatar_file_name` FROM `users` WHERE `id` = {$user_id}");
                $userInfo['name']             = $userInfo['name']             === '' ? $name            : $userInfo['name'];
                $userInfo['bio']              = $userInfo['bio']              === '' ? $bio             : $userInfo['bio'];
                $userInfo['avatar_file_name'] = $userInfo['avatar_file_name'] === '' ? $avatar_filename : $userInfo['avatar_file_name'];
                // @todo: commit these two query as a transaction
                $this->query(
                    "UPDATE `users` SET
                     `name`             = '{$userInfo['name']}',
                     `bio`              = '{$userInfo["bio"]}',
                     `avatar_file_name` = '{$userInfo['avatar_file_name']}',
                     `default_identity` =  {$id}
                     WHERE `id`         =  {$user_id}"
                );
                $this->query(
                    "INSERT INFO `user_identity` SET
                     `identityid` =  {$id},
                     `userid`     =  {$user_id},
                     `created_at` = NOW(),
                     `activecode` = '{$activecode}'"
                );
                // make verify token
                $verifyToken = packArray(array('identity_id' => $id, 'activecode' => $activecode));
                // send welcome and active email
                if ($provider === 'email') {
                    $hlpIdentity = $this->getHelperByName('identity');
                    $hlpIdentity-> sentWelcomeAndActiveEmail(array(
                        'identityid'        => $id,
                        'external_identity' => $external_id,
                        'name'              => $name,
                        'avatar_file_name'  => $avatar_filename,
                        'activecode'        => $activecode,
                        'token'             => $verifyToken
                    ));
                }
            }
            return $id;
        }
        return null;
    }

}
