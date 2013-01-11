<?php

class IdentityModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';


    protected function packageIdentity($rawIdentity, $user_id = null, $withRevoked = false) {
        $hlpUser = $this->getHelperByName('user');
        if ($rawIdentity) {
            $rawUserIdentity   = null;
            $status            = null;
            $connected_user_id = - (int) $rawIdentity['id'];
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
            $rawUserIdentity = $rawUserIdentity ?: $hlpUser->getRawUserIdentityStatusByIdentityId($rawIdentity['id']);
            if ($rawUserIdentity && $rawUserIdentity['userid']) {
                $rawUser = $hlpUser->getRawUserById($rawUserIdentity['userid']);
                if ($rawUser) {
                    $rawIdentity['bio']              = $rawIdentity['bio']              ?: $rawUser['bio'];
                    $rawIdentity['avatar_file_name'] = $rawIdentity['avatar_file_name'] ?: $rawUser['avatar_file_name'];
                }
                $connected_user_id = $rawUserIdentity['userid'];
            } else if ($withRevoke) {
                $rawUserIdentity = $this->getRow(
                    "SELECT * FROM `user_identity` WHERE `identityid` = {$rawIdentity['id']} AND `status` = 4"
                );
                $revoked_user_id = $rawUserIdentity && $rawUserIdentity['userid']
                                 ? $rawUserIdentity['userid'] : 0;
            }
            if (!$rawIdentity['name']) {
                switch ($rawIdentity['provider']) {
                    case 'email':
                        $objParsed = Identity::parseEmail($rawIdentity['external_username']);
                        $rawIdentity['name'] = $objParsed['name'];
                        break;
                    case 'twitter':
                    default:
                        $rawIdentity['name'] = $rawIdentity['external_username'];
                }
            }
            $objIdentity = new Identity(
                $rawIdentity['id'],
                $rawIdentity['name'],
                '', // $rawIdentity['nickname'], // @todo;
                $rawIdentity['bio'],
                $rawIdentity['provider'],
                $connected_user_id,
                $rawIdentity['external_identity'],
                $rawIdentity['external_username'],
                getAvatarUrl($rawIdentity['avatar_file_name']) ?: getDefaultAvatarUrl($rawIdentity['name']),
                $rawIdentity['created_at'],
                $rawIdentity['updated_at'],
                0,
                $rawIdentity['unreachable']
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


    public function getRawIdentityById($id) {
        $key = "identities:{$id}";
        $rawIdentity = getCache($key);
        if (!$rawIdentity) {
            $rawIdentity = $this->getRow(
                "SELECT * FROM `identities` WHERE `id` = {$id}"
            );
            setCache($key, $rawIdentity);
        }
        return $rawIdentity;
    }


    public function checkIdentityById($id) {
        $rawIdentity = $this->getRawIdentityById($id);
        return $rawIdentity ? (int) $rawIdentity['id'] : false;
    }


    public function getIdentityById($id, $user_id = null, $withRevoked = false) {
        $rawIdentity = $this->getRawIdentityById($id);
        return $this->packageIdentity($rawIdentity, $user_id, $withRevoked);
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


    public function isIdentityBelongsUser($identity_id, $user_id, $connected = true) {
        $sql = "SELECT `identityid` FROM `user_identity` WHERE `identityid` = {$identity_id} AND `userid` = {$user_id} AND "
             . ($connected ? '`status` = 3' : '`status` > 1');
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
            $update_sql .= " `name`        = '{$identity['name']}', ";
        }
        if (isset($identity['bio'])) {
            $update_sql .= " `bio`         = '{$identity['bio']}', ";
        }
        if (isset($identity['unreachable'])) {
            $update_sql .= " `unreachable` =  {$identity['unreachable']}, ";
        }
        if ($update_sql) {
            $result = $this->query(
                "UPDATE `identities`
                 SET    {$update_sql} `updated_at` = NOW()
                 WHERE  `id` = {$identity_id}"
            );
            delCache("identities:{$identity_id}");
            $hlpUser = $this->getHelperByName('User');
            $user_id = $hlpUser->getUserIdByIdentityId($identity_id);
            if ($user_id) {
                $this->query(
                    "UPDATE `users`
                     SET    `updated_at` = NOW()
                     WHERE  `id` = {$user_id}"
                );
            }
            return $result;
        }
        return true;
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
        delCache("identities:{$chgId}");
        // merge identity
        if ($wasId > 0 && $wasId !== $id) {
            $this->query("UPDATE `invitations`
                          SET    `identity_id` = {$wasId}
                          WHERE  `identity_id` = {$id};");
            // @todo: 可能需要更新 log by @leaskh
            $this->query("DELETE FROM `identities` WHERE `id` = {$id};");
            delCache("identities:{$id}");
        }
        // return
        return $chgId;
    }


    public function getGravatarUrlByExternalUsername($external_username, $format = '', $fallback = '') {
        return $external_username
             ? ('http://www.gravatar.com/avatar/' . md5(strtolower($external_username))
              . ($format   ? ".{$format}"     : '')
              . ($fallback ? "?d={$fallback}" : ''))
             : '';
    }


    public function getGravatarByExternalUsername($external_username) {
        $url = $this->getGravatarUrlByExternalUsername($external_username, '', '404');
        if ($url) {
            $objCurl  = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, true);
            curl_setopt($objCurl, CURLOPT_NOBODY, true);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            $httpHead = curl_exec($objCurl);
            $httpCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            curl_close($objCurl);
            if ($httpCode === 200) {
                return $this->getGravatarUrlByExternalUsername($external_username);
            }
        }
        return '';
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
    public function addIdentity($identityDetail = [], $user_id = 0, $status = 2, $withVerifyInfo = false, $newUser = true, $device = '', $device_callback = '') {
        // load models
        $hlpUder = $this->getHelperByName('user');
        // collecting new identity informations
        $user_id           = (int) $user_id;
        $provider          = @mysql_real_escape_string(trim($identityDetail['provider']));
        $external_id       = @mysql_real_escape_string(strtolower(trim($identityDetail['external_id'])));
        $external_username = @mysql_real_escape_string(strtolower(trim($identityDetail['external_username'])));
        $name              = @mysql_real_escape_string(trim($identityDetail['name']));
        $nickname          = @mysql_real_escape_string(trim($identityDetail['nickname']));
        $bio               = @mysql_real_escape_string(trim($identityDetail['bio']));
        $avatar_filename   = @mysql_real_escape_string(trim($identityDetail['avatar_filename']));
        // basic check
        switch ($provider) {
            case 'email':
                if (!$external_id && !$external_username) {
                    return null;
                }
                break;
            case 'twitter':
            case 'facebook':
                if (!$external_id && !$external_username) {
                    if ($user_id && $status = 2 && $withVerifyInfo) {
                        $identity = new stdClass;
                        $identity->provider = $provider;
                        $vfyResult = $hlpUder->verifyIdentity(
                            $identity, 'VERIFY', $user_id, null,
                            $device, $device_callback
                        );
                        if ($vfyResult && isset($vfyResult['url'])) {
                            return [
                                'identity_id'  => -1,
                                'verification' => ['url' => $vfyResult['url']],
                            ];
                        }
                    }
                    return null;
                }
                break;
            default:
                return null;
        }
        // check current identity
        $curIdentity = $this->getRow(
            "SELECT `id` FROM `identities` WHERE `provider` = '{$provider}' AND " . (
                $external_id
              ? "`external_identity` = '{$external_id}'"
              : "`external_username` = '{$external_username}'"
            ) . ' LIMIT 1'
        );
        // add identity
        if (($id = intval($curIdentity['id'])) <= 0) {
            // fixed args
            $hlpOAuth = $this->getHelperByName('OAuth');
            switch ($provider) {
                case 'email':
                    $external_id = $external_username = $external_id ?: $external_username;
                    $avatar_filename = $this->getGravatarByExternalUsername($external_username);
                    break;
                case 'twitter':
                    $rawIdentity = $hlpOAuth->getTwitterProfileByExternalUsername(
                        $external_username
                    );
                    if ($rawIdentity) {
                        $external_id     = mysql_real_escape_string(strtolower(trim($rawIdentity->external_id)));
                        $name            = mysql_real_escape_string(trim($rawIdentity->name));
                        $bio             = mysql_real_escape_string(trim($rawIdentity->bio));
                        $avatar_filename = mysql_real_escape_string(trim($rawIdentity->avatar_filename));
                    }
                    break;
                case 'facebook':
                    $rawIdentity = $hlpOAuth->getFacebookProfileByExternalUsername(
                        $external_username
                    );
                    if ($rawIdentity) {
                        $external_id     = mysql_real_escape_string(strtolower(trim($rawIdentity->external_id)));
                        $name            = mysql_real_escape_string(trim($rawIdentity->name));
                        $bio             = mysql_real_escape_string(trim($rawIdentity->bio));
                        $avatar_filename = mysql_real_escape_string(trim($rawIdentity->avatar_filename));
                    }
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
            delCache("identities:{$id}");
        }
        // update user information
        if ($id) {
            if ($user_id) {
                // verify
                if ($status === 2) {
                    if ($user_id === $hlpUder->getUserIdByIdentityId($id)) {
                        return null;
                    }
                    // verify identity
                    $objIdentity = $this->getIdentityById($id);
                    $vfyResult   = $hlpUder->verifyIdentity(
                        $objIdentity, 'VERIFY', $user_id, null,
                        $device, $device_callback
                    );
                    if ($vfyResult) {
                        if (isset($vfyResult['url']) && $withVerifyInfo) {
                            return [
                                'identity_id'  => $id,
                                'verification' => ['url' => $vfyResult['url']],
                            ];
                        } else if (isset($vfyResult['token'])) {
                            $this->sendVerification(
                                $newUser ? 'Welcome' : 'Verify',
                                $objIdentity,
                                $vfyResult['token'],
                                true,
                                $userInfo['name'] ?: $objIdentity->name
                            );
                            if ($withVerifyInfo) {
                                return [
                                    'identity_id'  => $id,
                                    'verification' => ['token' => $vfyResult['token']],
                                ];
                            }
                        }
                    }
                } else {
                    $hlpUder->setUserIdentityStatus($user_id, $id, $status);
                }
            }
            return $id;
        }
        return null;
    }


    public function revokeIdentity($identity_id) {
        if (!($identity_id = (int) $identity_id)) {
            return false;
        }
        delCache("user_identity:identity_{$identity_id}");
        return $this->query(
            "UPDATE `user_identity`
             SET    `status`     = 4,
                    `updated_at` = NOW()
             WHERE  `identityid` = {$identity_id}
             AND    `status`     = 3"
        );
    }


    public function sendVerification($method, $identity, $token, $need_verify = false, $user_name = '') {
        $data = [
            'tos'       => [new Recipient(
                $identity->id,
                $identity->connected_user_id,
                $identity->name,
                $identity->auth_data ?: '',
                '',
                $token,
                '',
                $identity->provider,
                $identity->external_id,
                $identity->external_username
            )],
            'service'   => 'User',
            'method'    => $method,
            'merge_key' => '',
            'data'      => new stdClass,
        ];
        switch ($method) {
            case 'Welcome':
                $data['data']->need_verify = $need_verify;
                break;
            case 'Verify':
            case 'ResetPassword':
                $data['data']->user_name   = $user_name;
                break;
            default:
                return false;
        }
        if (DEBUG) {
            error_log('job: ' . json_encode($data));
        }
        $modGobus = $this->getHelperByName('Gobus');
        return $modGobus->useGobusApi(EXFE_GOBUS_SERVER, 'Instant', 'Push', $data);
    }


    public function getOAuthTokenById($identity_id) {
        $dbResult = $this->getRow(
            "SELECT `oauth_token` FROM `identities` WHERE `id` = $identity_id"
        );
        return $dbResult && $dbResult['oauth_token']
             ? json_decode($dbResult['oauth_token'], true) : null;
    }


    public function updateOAuthTokenById($identity_id, $tokens) {
        if ($identity_id && $tokens) {
            delCache("identities:{$identity_id}");
            return $this->query(
                "UPDATE `identities`
                 SET    `oauth_token` = '" . json_encode($tokens)
            . "' WHERE  `id`          = {$identity_id}"
            );
        }
        return false;
    }


    public function updateAvatarById($identity_id, $avatar_filename = '') {
        if ($identity_id) {
            delCache("identities:{$identity_id}");
            return $this->query(
                "UPDATE `identities`
                 SET    `avatar_file_name` = '{$avatar_filename}',
                        `updated_at`       =  NOW()
                 WHERE  `id`               =  {$identity_id}"
            );
        }
        return false;
    }


    public function deleteIdentityFromUser($identity_id, $user_id) {
        if (!$identity_id || !$user_id) {
            return false;
        }
        $identities = $this->getAll(
            "SELECT * FROM `user_identity` WHERE `userid` = {$user_id}"
        );
        if (count($identities) > 1) {
            $upResult = $this->query(
                "UPDATE `user_identity`
                    SET `status`     = 1,
                        `order`      = 999
                 WHERE  `identityid` = {$identity_id}
                 AND    `userid`     = {$user_id}"
            );
            delCache("user_identity:identity_{$identity_id}");
            if ($upResult) {
                return true;
            }
        }
        return false;
    }

}



// public function getIdentityByIdFromCache($identity_id) {
//     if ($identity_id) {
//         $redis = new Redis();
//         $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
//         $identity = $redis->HGET('identities', "id:{$identity_id}");
//         if ($identity) {
//             $identity = json_decode($identity);
//         } else {
//             $identity = $this->getIdentityById($identity_id);
//             if ($identity) {
//                 $redis->HSET(
//                     'identities', "id:{$identity_id}",
//                     json_encode($identity) // @was: json_encode_nounicode
//                 );
//             }
//         }
//         if ($identity) {
//             return $identity;
//         }
//     }
//     return null;
// }
