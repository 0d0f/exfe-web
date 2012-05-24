<?php

class UserModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';

    protected $arrUserIdentityStatus = array('', 'RELATED', 'VERIFYING', 'CONNECTED', 'REVOKED');


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


    public function getUserById($id, $withCrossQuantity = false) {
        $rawUser = $this->getRow("SELECT * FROM `users` WHERE `id` = {$id}");
        if ($rawUser) {
            // build user object
            $user = new User(
                $rawUser['id'],
                $rawUser['name'],
                $rawUser['bio'],
                null, // $rawUser[''] default_identity
                $rawUser['avatar_file_name'],
                $rawUser['avatar_updated_at'],
                $rawUser['timezone']
            );
            if ($withCrossQuantity) {
                $user->cross_quantity = 0;
            }
            // get all identity ids connetced to the user
            $rawIdentityIds = $this->getAll(
                "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$rawUser['id']} AND `status` = 3"
            );
            // insert identities into user
            if ($rawIdentityIds) {
                $identityIds = array();
                foreach ($rawIdentityIds as $i => $item) {
                    $identityIds[] = $item['identityid'];
                }
                $identityIds = implode($identityIds, ' OR `id` = ');
                $identities  = $this->getAll("SELECT * FROM `identities` WHERE `id` = {$identityIds}");
                if ($identities) {
                    foreach ($identities as $i => $item) {
                        $intLength = array_push(
                            $user->identities,
                            new Identity(
                                $item['id'],
                                $item['name'],
                                '', // $item['nickname'], // @todo;
                                $item['bio'],
                                $item['provider'],
                                $rawUser['id'],
                                $item['external_identity'],
                                $item['external_username'],
                                $item['avatar_file_name'],
                                $item['avatar_updated_at'],
                                $item['created_at'],
                                $item['updated_at']
                            )
                        );
                        // catch default identity
                        if (intval($item['id']) === intval($rawUser['id'])) {
                            $user->default_identity = $user->identities[$intLength];
                        }
                    }
                    if ($withCrossQuantity) {
                        $cross_quantity = $this->getRow(
                            "SELECT COUNT(DISTINCT `cross_id`) AS `cross_quantity` FROM `invitations` WHERE `identity_id` = {$identityIds}"
                        );
                        $user->cross_quantity = (int)$cross_quantity['cross_quantity'];
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
        $sql = "SELECT `user_identity`.`userid` FROM `identities`, `user_identity`
                WHERE  `identities`.`provider`          = '{$provider}'
                AND    `identities`.`external_identity` = '{$external_id}'
                AND    `identities`.`id` = `user_identity`.`identityid`";
        $rawUser = $this->getRow($sql);
        $user_id = intval($rawUser['userid']);
        if ($user_id) {
            $rtResult   = array('user_id' => $user_id);
            $passwdInDb = $this->getUserPasswdByUserId($user_id);
            $password   = $this->encryptPassword($password, $passwdInDb['password_salt']);
            if ($password === $passwdInDb['encrypted_password']) {
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
                            '', // $$item['nickname'], // @todo;
                            $item['bio'],
                            $item['provider'],
                            $user_id,
                            $item['external_identity'],
                            $item['external_username'],
                            $item['avatar_file_name'],
                            $item['avatar_updated_at'],
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

}
