<?php

class IdentityModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';


    protected function packageIdentity($rawIdentity, $user_id = null, $withRevoked = false) {
        $hlpUser = $this->getHelperByName('user', 'v2');
        if ($rawIdentity) {
            $rawUserIdentity   = null;
            $status            = null;
            $connected_user_id = 0;
            $revoked_user_id   = 0;
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
                    $rawIdentity['bio']
                  = $rawIdentity['bio'] === ''
                  ? $rawUser['bio'] : $rawIdentity['bio'];
                }
                $connected_user_id = $rawUserIdentity['userid'];
            } elseif ($withRevoke) {
                $rawUserIdentity = $this->getRow(
                    "SELECT * FROM `user_identity` WHERE `identityid` = {$rawIdentity['id']} AND `status` = 4"
                );
                $revoked_user_id = $rawUserIdentity && $rawUserIdentity['userid']
                                 ? $rawUserIdentity['userid'] : 0;
            }
            $rawIdentity['avatar_file_name'] = getAvatarUrl(
                $rawIdentity['provider'],
                $rawIdentity['external_username'],
                $rawIdentity['avatar_file_name']
            );
            $objIdentity = new Identity(
                $rawIdentity['id'],
                $rawIdentity['name'],
                '', // $rawIdentity['nickname'], // @todo;
                $rawIdentity['bio'],
                $rawIdentity['provider'],
                $connected_user_id,
                $rawIdentity['external_identity'],
                $rawIdentity['external_username'],
                $rawIdentity['avatar_file_name'],
                $rawIdentity['created_at'],
                $rawIdentity['updated_at']
            );
            if ($status !== null) {
                $objIdentity->status = $status;
            }
            if ($withRevoked) {
                $objIdentity->revoked_user_id = $revoked_user_id;
            }
            return $objIdentity;
        } else {
            return null;
        }
    }


    public function getIdentityById($id, $user_id = null, $withRevoked = false) {
        return $this->packageIdentity($this->getRow(
            "SELECT * FROM `identities` WHERE `id` = {$id}"
        ), $user_id, $withRevoked);
    }


    public function getIdentityByProviderAndExternalUsername($provider, $external_username, $withRevoked = false, $get_id_only = false) {
        $rawIdentity = $this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_username` = '{$external_username}'"
        );
        return $get_id_only ? intval($rawIdentity['id']) : $this->packageIdentity($rawIdentity, null, $withRevoked);
    }


    public function getIdentityByProviderExternalId($provider, $external_id, $get_id_only = false) {
        $rawIdentity = $this->getRow(
            "SELECT * FROM `identities` WHERE
             `provider`          = '{$provider}' AND
             `external_identity` = '{$external_id}'"
        );
        return $get_id_only ? intval($rawIdentity['id']) : $this->packageIdentity($rawIdentity);
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


    public function updateIdentityById($identity_id, $identity = array()) {
        if (!$identity_id) {
            return false;
        }
        $update_sql = '';
        if (isset($identity['name'])) {
            $update_sql .= " `name` = '{$identity['name']}', ";
        }
        if (isset($identity['bio'])) {
            $update_sql .= " `bio`  = '{$identity['bio']}', ";
        }
        return $update_sql
             ? $this->query("UPDATE `identities` SET {$update_sql} `updated_at` = NOW() WHERE `id` = {$identity_id}")
             : true;

    }


    public function updateIdentityByGobus($id, $identityDetail = array()) {
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
     *     $provider,
     *     $external_id,
     *     $external_username,
     *     $name,
     *     $nickname,
     *     $bio,
     *     $avatar_filename,
     * }
     * if ($user_id === 0) without adding it to a user
     */
    public function addIdentity($identityDetail = array(), $user_id = 0, $status = 2) {
        // collecting new identity informations
        $provider          = mysql_real_escape_string(trim($identityDetail['provider']));
        $external_id       = mysql_real_escape_string(trim($identityDetail['external_id']));
        $external_username = mysql_real_escape_string(trim($identityDetail['external_username']));
        $name              = mysql_real_escape_string(trim($identityDetail['name']));
        $nickname          = mysql_real_escape_string(trim($identityDetail['nickname']));
        $bio               = mysql_real_escape_string(trim($identityDetail['bio']));
        $avatar_filename   = mysql_real_escape_string(trim($identityDetail['avatar_filename']));
        // basic check
        if (!$provider || (!$external_id && !$external_username)) {
            return null;
        }
        // check current identity
        $curIdentity = $this->getRow(
            "SELECT `id` FROM `identities` WHERE `provider` = '{$provider}' AND " + (
                $external_id
              ? "`external_identity` = '{$external_id}'"
              : "`external_username` = '{$external_username}'"
            ) + ' LIMIT 1'
        );
        if (intval($curIdentity['id']) > 0) {
            return intval($curIdentity['id']);
        }
        // fixed args
        switch ($provider) {
            case 'email':
                $external_id = $external_username;
                break;
            case 'twitter':
            case 'facebook':
                break;
            default:
                return null;
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
                // load models
                $hlpUder  = $this->getHelperByName('user', 'v2');
                // do update
                $userInfo = $this->getRow("SELECT `name`, `bio`, `default_identity` FROM `users` WHERE `id` = {$user_id}");
                $userInfo['name']             = $userInfo['name']             == '' ? $name : $userInfo['name'];
                $userInfo['bio']              = $userInfo['bio']              == '' ? $bio  : $userInfo['bio'];
                $userInfo['default_identity'] = $userInfo['default_identity'] == 0  ? $id   : $userInfo['default_identity'];
                $this->query(
                    "UPDATE `users` SET
                     `name`             = '{$userInfo['name']}',
                     `bio`              = '{$userInfo['bio']}',
                     `default_identity` =  {$userInfo['default_identity']}
                     WHERE `id`         =  {$user_id}"
                );
                if ($status === 2) {
                    // welcome and verify user via Gobus {
                    if ($provider === 'email') {
                        $hlpGobus = $this->getHelperByName('gobus', 'v2');
                        $objIdentity = $this->getIdentityById($id);
                        $vfyResult   = $hlpUder->verifyIdentity($objIdentity, 'VERIFY', $user_id);
                        if ($vfyResult) {
                            $hlpGobus->send('user', 'Welcome', [
                                'To_identity' => $objIdentity,
                                'User_name'   => $userInfo['name'],
                                'Token'       => $vfyResult['token'],
                            ]);
                        }
                    }
                    // }
                } else {
                    $hlpUder->setUserIdentityStatus($user_id, $id, $status);
                }
            }
            return $id;
        }
        return null;
    }


    public function getOAuthTokenById($identity_id) {
        return json_decode($this->queryRow(
            "SELECT `oauth_token` FROM `identities` WHERE `id` = $identity_id"
        )) ?: null;
    }


    public function updateOAuthTokenById($identity_id, $tokens) {
        if ($identity_id && $tokens) {
            return $this->query(
                "UPDATE `identities`
                 SET    `oauth_token` = '" . json_encode($tokens)
            . "' WHERE  `id`          = $identity_id"
            );
        }
        return false;
    }


    public function updateAvatarById($identity_id, $avatar_filename = '') {
        return $identity_id && $this->query(
            "UPDATE `identities`
             SET    `avatar_file_name` = '{$avatar_filename}',
                    `updated_at`       =  NOW()
             WHERE  `id`               =  {$identity_id}"
        );
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


    public function getIdentityByIdFromCache($identity_id) {
        if ($identity_id) {
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            $identity = $redis->HGET('identities', "id:{$identity_id}");
            if ($identity) {
                $identity = json_decode($identity);
            } else {
                $identity = $this->getIdentityById($identity_id);
                if ($identity) {
                    $redis->HSET(
                        'identities', "id:{$identity_id}",
                        json_encode($identity) // @was: json_encode_nounicode
                    );
                }
            }
            if ($identity) {
                return $identity;
            }
        }
        return null;
    }

}
