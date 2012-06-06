<?php

class IdentityModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';


    protected function packageIdentity($rawIdentity, $user_id = null) {
        $hlpUser = $this->getHelperByName('user', 'v2');
        if ($rawIdentity) {
            $rawUserIdentity = null;
            $status          = null;
            if ($user_id) {
                $chkUserIdentity = $this->getRow(
                    "SELECT * FROM `user_identity` WHERE `identityid` = {$rawIdentity['id']} AND `userid` = $user_id"
                );
                if (($chkUserIdentity['status'] = (int)$chkUserIdentity['status']) === 3) {
                    $rawUserIdentity = $chkUserIdentity;
                }
                $status = $hlpUser->getUserIdentityStatus($chkUserIdentity['status']);
            }
            $rawUserIdentity = $rawUserIdentity ?: $this->getRow(
                "SELECT * FROM `user_identity` WHERE `identityid` = {$rawIdentity['id']} AND `status` = 3"
            );
            if ($rawUserIdentity && $rawUserIdentity['userid']) {
                $rawUser = $this->getRow(
                    "SELECT * FROM `users` WHERE `id` = {$rawUserIdentity['userid']}"
                );
                if ($rawUser) {
                    $rawIdentity['bio'] = $rawIdentity['bio'] === '' ? $rawUser['bio'] : $rawIdentity['bio'];
                }
            }
            $rawIdentity['avatar_file_name'] = getAvatarUrl(
                $rawIdentity['provider'],
                $rawIdentity['external_identity'],
                $rawIdentity['avatar_file_name']
            );
            $objIdentity = new Identity(
                $rawIdentity['id'],
                $rawIdentity['name'],
                '', // $rawIdentity['nickname'], // @todo;
                $rawIdentity['bio'],
                $rawIdentity['provider'],
                $rawUserIdentity && $rawUserIdentity['userid'] ? $rawUserIdentity['userid'] : 0,
                $rawIdentity['external_identity'],
                $rawIdentity['external_username'],
                $rawIdentity['avatar_file_name'],
                $rawIdentity['created_at'],
                $rawIdentity['updated_at']
            );
            if ($status !== null) {
                $objIdentity->status = $status;
            }
            return $objIdentity;
        } else {
            return null;
        }
    }


    public function getIdentityById($id, $user_id = null) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE `id` = {$id}"
        ), $user_id);
    }


    public function getIdentityByProviderAndExternalUsername($provider, $external_username) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_username` = '{$external_username}'"
        ));
    }


    public function getIdentityByProviderExternalId($provider, $external_id, $get_id_only = false) {
        $rawIdentity = $this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_identity` = '{$external_id}'"
        );
        return $get_id_only ? intval($rawIdentity) : $this->packageIdentity($rawIdentity);
    }


    public function isIdentityBelongsUser($identity_id,$user_id) {
        $sql="select identityid from user_identity where identityid=$identity_id and userid=$user_id;";
        $row = $this->getRow($sql);
        if(intval($row["identityid"])>0)
            return true;
        return false;
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
                 `updated_at`        = NOW()
             WHERE `id` = {$chgId}"////////////$nickname pending
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
        $provider          = trim(mysql_real_escape_string(strtolower($provider)));
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
        // insert new identity into database
        $dbResult = $this->query(
            "INSERT INTO `identities` SET
             `provider`          = '{$provider}',
             `external_identity` = '{$external_id}',
             `created_at`        = NOW(),
             `name`              = '{$name}',
             `bio`               = '{$bio}',
             `avatar_file_name`  = '{$avatar_filename}',
             `external_username` = '{$external_username}'"
        );
        $id = intval($dbResult['insert_id']);
        // update user information
        if ($id) {
            if ($user_id) {
                // create new token
                $activecode = createToken();
                // do update
                $userInfo = $this->getRow("SELECT `name`, `bio`, `avatar_file_name`, `default_identity` FROM `users` WHERE `id` = {$user_id}");
                $userInfo['name']             = $userInfo['name']             == '' ? $name            : $userInfo['name'];
                $userInfo['bio']              = $userInfo['bio']              == '' ? $bio             : $userInfo['bio'];
                $userInfo['avatar_file_name'] = $userInfo['avatar_file_name'] == '' ? $avatar_filename : $userInfo['avatar_file_name'];
                $userInfo['default_identity'] = $userInfo['default_identity'] == 0  ? $id              : 0;
                // @todo: commit these two query as a transaction
                $this->query(
                    "UPDATE `users` SET
                     `name`             = '{$userInfo['name']}',
                     `bio`              = '{$userInfo['bio']}',
                     `avatar_file_name` = '{$userInfo['avatar_file_name']}',
                     `default_identity` =  {$userInfo['default_identity']}
                     WHERE `id`         =  {$user_id}"
                );
                $this->query(
                    "INSERT INTO `user_identity` SET
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


    public function setIdentityAsDefaultIdentityOfUser($identity_id, $user_id) {
        if (!$identity_id || !$user_id) {
            return false;
        }
        return $this->query(
            "UPDATE `users` SET `default_identity` = {$identity_id} WHERE `id` = {$user_id}"
        );
    }


    public function deleteIdentityFromUser($identity_id, $user_id) {
        if (!$identity_id || !$user_id) {
            return false;
        }
        $identities = $this->getRow(
            "SELECT * FROM `user_identity` WHERE `userid` = {$user_id}"
        );
        if (count($identities) > 1) {
            $upResult = $this->query(
                "UPDATE `user_identity` SET `status` = 1
                 WHERE  `identityid` = {$identity_id}
                 AND    `userid`     = {$user_id}"
            );
            if (!$upResult) {
                return false;
            }
            foreach ($identities as $item){
                if ($item['identityid'] !== $identity_id) {
                    return $this->setIdentityAsDefaultIdentityOfUser($item['identityid'], $user_id);
                }
            }
        }
        return false;
    }


    public function parseEmail($email) {
        $email = trim($email);
        if (preg_match('/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/', $email)) {
            $name  = preg_replace('/^[\s\"\']*|[\s\"\']*$/', '', preg_replace('/^([^<]*).*$/', '$1', $email));
            $email = trim(preg_replace('/^.*<([^<^>]*).*>$/', '$1', $email));
        } else if (preg_match('/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $email)) {
            $name  = trim(preg_replace('/^([^@]*).*$/', '$1', $email));
        } else {
            return null;
        }
        return array('name' => $name, 'email' => $email);
    }


}
