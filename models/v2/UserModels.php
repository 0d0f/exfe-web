<?php

class UserModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';

    public  $arrUserIdentityStatus = array('', 'RELATED', 'VERIFYING', 'CONNECTED', 'REVOKED');


    protected function getUserPasswdByUserId($user_id) {
        return $this->getRow(
            "SELECT `cookie_logintoken`, `cookie_loginsequ`, `auth_token`,
             `encrypted_password`, `password_salt`, `current_sign_in_ip`,
             `reset_password_token`
             FROM   `users` WHERE `id` = {$user_id}"
        );
    }


    protected function encryptPassword($password, $password_salt) {
        return md5($password.( // compatible with the old users
            $password_salt === $this->salt ? $this->salt
          : (substr($password_salt, 3, 23) . EXFE_PASSWORD_SALT)
        ));
    }


    public function getUserById($id, $withCrossQuantity = false, $identityStatus = 3) {
        $rawUser = $this->getRow("SELECT * FROM `users` WHERE `id` = {$id}");
        if ($rawUser) {
            // build user object
            $rawUser['avatar_file_name'] = $rawUser['avatar_file_name'] ? getAvatarUrl('', '', $rawUser['avatar_file_name']) : '';
            $user = new User(
                $rawUser['id'],
                $rawUser['name'],
                $rawUser['bio'],
                null, // default_identity
                $rawUser['avatar_file_name'],
                $rawUser['timezone']
            );
            if ($withCrossQuantity) {
                $user->cross_quantity = 0;
            }
            // get all identity ids of user
            $identityStatus = ($identityStatus = intval($identityStatus))
                            ? "`status` = {$identityStatus}" : '(`status` > 1 AND `status` < 5)';
            $rawIdentityIds = $this->getAll(
                "SELECT `identityid`, `status` FROM `user_identity` WHERE `userid` = {$rawUser['id']} AND {$identityStatus}"
            );
            // insert identities into user
            if ($rawIdentityIds) {
                $identity_status = array();
                foreach ($rawIdentityIds as $i => $item) {
                    $identity_status[intval($item['identityid'])] = $this->arrUserIdentityStatus[intval($item['status'])];
                }
                $identityIds = implode(array_keys($identity_status), ', ');
                $identities  = $this->getAll("SELECT * FROM `identities` WHERE `id` IN ({$identityIds})");
                if ($identities) {
                    foreach ($identities as $i => $item) {
                        $item['avatar_file_name'] = getAvatarUrl(
                            $item['provider'],
                            $item['external_identity'],
                            $item['avatar_file_name']
                        );
                        $identity = new Identity(
                            $item['id'],
                            $item['name'],
                            '', // $item['nickname'], // @todo;
                            $item['bio'],
                            $item['provider'],
                            $rawUser['id'],
                            $item['external_identity'],
                            $item['external_username'],
                            $item['avatar_file_name'],
                            $item['created_at'],
                            $item['updated_at']
                        );
                        $identity->status = $identity_status[$identity->id];
                        $intLength = array_push($user->identities, $identity);
                        // catch default identity
                        if ($identity->id === intval($rawUser['default_identity'])) {
                            $user->default_identity = $user->identities[$intLength];
                        }
                    }
                    $user->default_identity = $user->default_identity ?: $user->identities[0];
                    if ($withCrossQuantity) {
                        $cross_quantity = $this->getRow(
                            "SELECT COUNT(DISTINCT `cross_id`) AS `cross_quantity` FROM `invitations` WHERE `identity_id` IN ({$identityIds})"
                        );
                        $user->cross_quantity = (int)$cross_quantity['cross_quantity'];
                    }
                    if (!$user->avatar_filename) {
                        $user->avatar_filename = $user->default_identity->avatar_filename;
                    }
                }
            }
            return $user;
        } else {
            return null;
        }
    }


    public function getUserIdByIdentityId($identity_id) {
        $dbResult = $this->getRow(
            "SELECT `userid` FROM `user_identity` WHERE `identityid`= {$identity_id}"
        );
        $user_id = intval($dbResult["userid"]);
        return $user_id ?: null;
    }


    public function newUserByPassword($password) {
        $passwordSalt = md5(createToken());
        $passwordInDb = $this->encryptPassword($password, $passwordSalt);
        $dbResult = $this->query(
            "INSERT INTO `users` SET
             `encrypted_password` = '{$passwordInDb}',
             `password_salt`      = '{$passwordSalt}',
             `created_at`         = NOW()"
        );
        return intval($dbResult['insert_id']);
    }


    public function getUserIdsByIdentityIds($identity_ids) {
        $identity_ids = implode($identity_ids, ' OR `identityid` = ');
        $sql= "SELECT `userid` FROM `user_identity`
             WHERE `identityid` = {$identity_ids} AND `status` = 3";
        $dbResult = $this->getAll($sql);
        $user_ids = array();
        if ($dbResult) {
            foreach ($dbResult as $uI => $uItem) {
                $user_ids[] = $uItem['userid'];
            }
        }
        return $user_ids;
    }


    public function getUserIdentityInfoByUserId($user_id) {
        if (!$user_id) {
            return null;
        }
        $passwd  = $this->getUserPasswdByUserId($user_id);
        $ids     = $this->getAll(
            "SELECT `identityid`, `status` FROM `user_identity` WHERE `userid` = {$user_id}"
        );
        $result  = array(
            'user_id'           => $user_id,
            'password'          => !!$passwd['encrypted_password'],
            'identities_status' => array(),
        );
        foreach ($ids as $id) {
            $result['identities_status'][$id['identityid']]
          = $this->arrUserIdentityStatus[intval($id['status'])];
        }
        return $result;
    }


    public function getUserIdentityInfoByIdentityId($identity_id) {
        if (!$identity_id) {
            return null;
        }
        $rawStatus = $this->getRow(
            "SELECT `userid`, `status` FROM `user_identity` WHERE `identityid` = {$identity_id}"
        );
        if (!$rawStatus) {
            return null;
        }
        $user_id = intval($rawStatus['userid']);
        $passwd  = $this->getUserPasswdByUserId($user_id);
        $ids     = $this->getAll(
            "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$user_id}"
        );
        return array(
            'user_id'     => intval($rawStatus['userid']),
            'status'      => $this->arrUserIdentityStatus[intval($rawStatus['status'])],
            'password'    => !!$passwd['encrypted_password'],
            'id_quantity' => count($ids),
        );
    }


    public function getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id) {
        if (!$user_id || !$identity_id) {
            return null;
        }
        $rawStatus = $this->getRow(
            "SELECT `status` FROM `user_identity` WHERE `identityid` = {$identity_id} AND `userid` = {$user_id}"
        );
        return $rawStatus ? $this->arrUserIdentityStatus[intval($rawStatus['status'])] : null;
    }


    public function signinForAuthToken($provider, $external_id, $password) {
        $sql = "SELECT `user_identity`.`userid`, `user_identity`.`status`
                FROM   `identities`, `user_identity`
                WHERE  `identities`.`provider`          = '{$provider}'
                AND    `identities`.`external_identity` = '{$external_id}'
                AND    `identities`.`id` = `user_identity`.`identityid`";
        $rawUser = $this->getRow($sql);
        if ($rawUser && ($user_id = intval($rawUser['userid']))) {
            $status      = intval($rawUser['status']);
            $passwdInDb  = $this->getUserPasswdByUserId($user_id);
            $password    = $this->encryptPassword($password, $passwdInDb['password_salt']);
            $id_quantity = count($this->getAll(
                "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$user_id}"
            ));
            if ((($status === 2 && $id_quantity === 1) || $status === 3)
             && $password === $passwdInDb['encrypted_password']) {
                if (!$passwdInDb['auth_token']) {
                    $passwdInDb['auth_token'] = md5($time.uniqid());
                    $sql = "UPDATE `users` SET `auth_token` = '{$passwdInDb['auth_token']}' WHERE `id` = {$user_id}";
                    $this->query($sql);
                }
                return array('user_id' => $user_id, 'token' => $passwdInDb['auth_token']);
            }
        }
        return null;
    }


    public function signinForAuthTokenByOAuth($provider,$identity_id,$user_id)
    {
        if(intval($identity_id)>0 && intval($user_id)>0)
        {
            $sql="select userid from user_identity where identityid={$identity_id} and userid={$user_id};";
            $rawUser = $this->getRow($sql);
            if(intval($rawUser["userid"])>0)
            {
                $rtResult   = array('user_id' => $user_id);
                $passwdInDb = $this->getUserPasswdByUserId($user_id);
                if (!$passwdInDb['auth_token']) {
                    $passwdInDb['auth_token'] = md5($time.uniqid());
                    $sql = "UPDATE `users` SET `auth_token` = '{$passwdInDb['auth_token']}' WHERE `id` = {$user_id}";
                    $this->query($sql);
                }
                $rtResult['token'] = $passwdInDb['auth_token'];
                return $rtResult;
            }

        }
        return null;
    }


    public function signinByIdentityId($identity_id, $user_id = 0, $user = null, $identity = null, $authBy = 'password', $setCookie = false) {
        // init
        $hlpIdentity = getHelperByName('identity', 'v2');
        // get objects
        $user_id     = $user_id  ?: $this->getUserIdByIdentityId($identity_id);
        $user        = $user     ?: $this->getUserById($user_id);
        $identity    = $identity ?: $hlpIdentity->getIdentityById($identity_id);
        $ipAddress   = getRealIpAddr();
        // set cookie
        if ($setCookie && $authBy === 'password') {
            $userPasswd = $this->getUserPasswdByUserId($user_id);
            $cookie_signintoken  = md5("{$userPasswd['encrypted_password']}3firwkF");
            $cookie_signinsequ   = md5(time() . 'glkfFDks.F');
            if ($userPasswd['cookie_loginsequ'] && $userPasswd['cookie_logintoken']) { // first time login, setup cookie
                $cookie_signintoken = $userPasswd['cookie_logintoken'];
                $cookie_signinsequ  = $userPasswd['cookie_loginsequ'];
            } else {
                $this->query(
                    "UPDATE `users` SET
                     `current_sign_in_ip` = '{$ipAddress}',
                     `created_at`         = NOW(),
                     `cookie_loginsequ`   = '{$cookie_signinsequ}',
                     `cookie_logintoken`  = '{$cookie_signintoken}'
                     WHERE `id` = {$user_id}"
                );
            }
            $identity_ids = array();
            foreach ($user->identities as $i => $item) {
                $identity_ids[] = $item['id'];
            }
            $identity_ids = json_encode($identity_ids);
            // setcookie for one year
            setcookie('user_id',      $user_id,            time() + 31536000, '/', COOKIES_DOMAIN);
            setcookie('identity_ids', $identity_ids,       time() + 31536000, '/', COOKIES_DOMAIN);
            setcookie('signin_sequ',  $cookie_signinsequ,  time() + 31536000, '/', COOKIES_DOMAIN);
            setcookie('signin_token', $cookie_signintoken, time() + 31536000, '/', COOKIES_DOMAIN);
        }
        // set session
        $_SESSION['signin_user'] = $user;
        unset($_SESSION['signin_token']);
        // log and return
        $this->query(
            "UPDATE `users` SET `current_sign_in_ip` = '{$ipAddress}', `last_sign_in_at` = NOW() WHERE `id` = $user_id"
        );
        return $user;
    }


    public function signin($identityInfo, $password, $setCookie = false, $password_hashed = false, $oauth_signin = false) {
        // init
        $hlpIdentity = getHelperByName('identity', 'v2');
        // get identity object
        if ($oauth_signin) {
            $provider          = $identityInfo['provider'];
            $external_username = $identityInfo['external_username'];
            $identity = $hlpIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
        } else {
            $provider          = $identityInfo['provider'];
            $external_id       = $identityInfo['external_id'];
            $identity = $hlpIdentity->getIdentityByProviderExternalId($provider, $external_id);
        }
        // sign in
        if ($identity) {
            if (($user_id = $this->getUserIdByIdentityId($identity->id))) {
                $passwdInDb = $this->getUserPasswdByUserId($user_id);
                // hash the password
                $password = $password_hashed ? $password : $this->encryptPassword($password, $passwdInDb['password_salt']);
                // check password!
                if ($password === $passwdInDb['encrypted_password']) {
                    $user = $this->getUserById($user_id);
                    $this->signinByIdentityId($identity->id, $user_id, $user, $identity, 'password', $setCookie);
                    return $user;
                }
            }
        }
        return null;
    }


    public function getUserIdByToken($token) {
        if(trim($token)==='')
            return 0;
        $sql="select id from users where auth_token='$token';";
        $row=$this->getRow($sql);
        return intval($row["id"]);
    }


    public function verifyUserPassword($user_id, $password, $ignore_empty_passwd = false) {
        if (!$user_id) {
            return false;
        }
        $passwdInDb = $this->getUserPasswdByUserId($user_id);
        $password   = $this->encryptPassword($password, $passwdInDb['password_salt']);
        return $password === $passwdInDb['encrypted_password']
            || ($ignore_empty_passwd && !$passwdInDb['encrypted_password']);
    }


    public function addUserAndSetRelation($password, $name, $identity_id) {
        $modIdentity = getModelByName('identity', 'v2');
        $name        = mysql_real_escape_string($name);
        $external_id = mysql_real_escape_string($external_id);
        if (!$identity_id) {
            return false;
        }
        if (($user_id = $this->getUserIdByIdentityId($identity_id))) {
            return $user_id;
        } else {
            $insResult = $this->query(
                // @todo: need to add salt!!!
                "INSERT INTO `users` SET
                 `encrypted_password` = '{$password}',
                 `name`               = '{$name}',
                 `created_at`         = NOW()"
            );
            if (($user_id = intval($insResult))) {
                $this->query(
                    "INSERT INTO `user_identity` SET
                     `identityid` = {$identity_id},
                     `userid`     = {$user_id},
                     `created_at` = NOW()"
                );
                if ($name) {
                    $this->query(
                        "UPDATE `identities` SET `name` = '{$displayname}' WHERE `id` = {$identity_id}"
                    );
                    $this->query(
                        "UPDATE `user_identity` SET `status` = 3 WHERE `identityid` = {$identity_id}"
                    );
                }
                return $user_id;
            }
        }
        return false;
    }


    public function getResetPasswordTokenByIdentityId($identity_id) {
        $user_id = getUserIdByIdentityId($identity_id)
                ?: $this->addUserAndSetRelation('', '', $identity_id);
        if ($user_id) {
            $passwdInfo = $this->getUserPasswdByUserId($user_id);
            $reset_password_token = $passwdInfo['reset_password_token'];
            if (!$reset_password_token
             || (intval(substr($reset_password_token, 32)) + 5 * 24 * 60 * 60 < time())) { // token timeout
                $reset_password_token = createToken();
                $this->query(
                    "UPDATE `users` SET `reset_password_token` = '{$reset_password_token}' WHERE `id` = $uid"
                );
            }
            return array(
                'user_id' => $user_id,
                'token'   => $reset_password_token
            );
        }
        return null;
    }


    public function getIdentityIdByUserId($user_id){
        $sql="SELECT identityid FROM  `user_identity` where userid=$user_id;";
        $identity_ids=$this->getColumn($sql);
        return $identity_ids;
    }


    public function getMobileIdentitiesByUserId($user_id) {
        $rawIdentityIds = $this->getAll(
            "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$user_id} AND `status` = 3"
        );
        $objIdentities = array();
        if ($rawIdentityIds) {
            $identityIds = array();
            foreach ($rawIdentityIds as $i => $item) {
                $identityIds[] = $item['identityid'];
            }
            $identityIds = implode($identityIds, ' OR `id` = ');
            $identities  = $this->getAll("SELECT * FROM `identities` WHERE (`id` = {$identityIds}) AND (`provider` = 'iOSAPN' OR `provider` = 'Android')");

            if ($identities) {
                foreach ($identities as $i => $item) {
                    array_push(
                        $objIdentities,
                        new Identity(
                            $item['id'],
                            $item['name'],
                            '', // $item['nickname'], // @todo;
                            $item['bio'],
                            $item['provider'],
                            $user_id,
                            $item['external_identity'],
                            $item['external_username'],
                            $item['avatar_file_name'],
                            $item['created_at'],
                            $item['updated_at']
                        )
                    );
                }
            }
        }
        return $objIdentities;
    }


    public function setUserPassword($user_id, $password) {
        $password = $this->encryptPassword($password, $passwordSalt = md5(createToken()));
        return $this->query(
            "UPDATE `users` SET
             `encrypted_password` = '{$password}',
             `password_salt`      = '{$passwordSalt}'
             WHERE `id` = {$user_id}"
        );
    }


    public function updateUserById($user_id, $user = array()) {
        $update_sql = '';
        if (isset($user['name'])) {
            $update_sql .= " `name` = '{$user['name']}', ";
        }
        return $update_sql
             ? $this->query("UPDATE `users` SET {$update_sql} `updated_at` = NOW() WHERE `id` = {$user_id}")
             : true;
    }


    public function getUserAvatarByProviderAndExternalId($provider, $external_id) {
        $rawIdentity = $this->getRow(
            "SELECT `id`, `name` FROM `identities`
             WHERE  `provider`          = '{$provider}'
             AND    `external_identity` = '{$external_id}'"
        );
        if ($rawIdentity && $rawIdentity['id']) {
            $rawUser = $this->getRow(
                "SELECT `users`.`avatar_file_name` FROM `users`, `user_identity`
                 WHERE  `user_identity`.`userid`         = `users`.`id`
                 AND    `user_identity`.`identityid`     = {$rawIdentity['id']}
                 AND    `user_identity`.`status`         = 3"
            );
            if ($rawUser && $rawUser['avatar_file_name']) {
                header("Location: {$rawUser['avatar_file_name']}");
            } else {
                $this->makeDefaultAvatar($rawIdentity['name']);
            }
            return true;
        }
        return false;
    }


    public function makeDefaultAvatar($name) {
        // image config
        $specification = array(
            'width'       => 80,
            'height'      => 80,
            'bg_quantity' => 3,
        );
        $colors = array(
            array(138,  59, 197),
            array(189,  53,  55),
            array(219,  98,  11),
            array( 66, 163,  36),
            array( 41,  95, 204),
        );
        $ftSize = 36;
        $intHsh = base62_to_int(substr(md5($name), 0, 3));
        // init path
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/../../default_avatar_portrait/";
        $fLatin = "{$resDir}OpenSans-Regular.ttf";
        $fCjk   = "{$resDir}wqy-microhei-lite.ttc";
        // get image
        $bgIdx  = fmod($intHsh, $specification['bg_quantity']);
        $image  = ImageCreateFromPNG("{$resDir}bg_{$bgIdx}.png");
        // get color
        $clIdx  = fmod($intHsh, count($colors));
        $fColor = imagecolorallocate($image, $colors[$clIdx][0], $colors[$clIdx][1], $colors[$clIdx][2]);
        // get name & check CJK
        $ftFile = checkCjk($name = mb_substr($name, 0, 3, 'UTF-8'))
               && checkCjk($name = mb_substr($name, 0, 2, 'UTF-8')) ? $fCjk : $fLatin;
        $name   = mb_convert_encoding($name, 'html-entities', 'utf-8');
        // calcular font size
        do {
            $posArr = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 3, 65, $fColor, $ftFile, $name);
            $fWidth = $posArr[2] - $posArr[0];
            $ftSize--;
        } while ($fWidth > (80 - 2));
        imagettftext($image, $ftSize, 0, ($specification['width'] - $fWidth) / 2, 65, $fColor, $ftFile, $name);
        // show image
        header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        $actResult = imagepng($image);
        imagedestroy($image);
        // return
        return $actResult;
    }

}
