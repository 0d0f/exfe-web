<?php

class UserModels extends DataModel {
    
    private $salt = '_4f9g18t9VEdi2if';
    
    
    protected function getUserPasswdByUserId($user_id) {
        return $this->getRow(
            "SELECT `cookie_logintoken`, `cookie_loginsequ`, `auth_token`
             `encrypted_password`, `password_salt`, `current_sign_in_ip`
             FROM   `users` WHERE `id` = {$user_id}"
        );
    }
    
    
    protected function encryptPassword($user_password, $password_salt) {
        return md5($password.( // compatible with the old users
            $password_salt === $this->salt ? $this->salt
          : (substr($password_salt, 3, 23) . EXFE_PASSWORD_SALT)
        ));
    }
    

    public function getUserById($id) {
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
                $identityIds = implode($identityIds, ', OR `id` = ');
                $identities  = $this->getAll("SELECT * FROM `identities` WHERE `id` = {$identityIds}");
                if ($identities) {
                    foreach ($identities as $i => $item) {
                        $intLength = array_push(
                            $user->identities,
                            new Identity(
                                $item['id'],
                                $item['name'],
                                '', // $$item['nickname'], // @todo;
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
        $passwordInDb = md5($password . substr($passwordSalt, 3, 23) . EXFE_PASSWORD_SALT);
        $dbResult = $this->query(
            "INSERT INTO `users` SET
             `encrypted_password` = '{$password}',
             `password_salt`      = '{$passwordSalt}',
             `created_at`         = NOW()"
        );
        return intval($dbResult['insert_id']);
    }
    
    
    public function getUserIdsByIdentityIds($identity_ids) {
        $identity_ids = implode($identity_ids, ' OR `identityid` = ');
        $dbResult = $this->getAll(
            "SELECT `userid` FROM `user_identity`
             WHERE `identityid` = {$identity_ids} AND `status` = 3"
        );
        $user_ids = array();
        if ($dbResult) {
            foreach ($dbResult as $uI => $uItem) {
                $user_ids[] = $uItem['userid'];
            }
        }
        return $user_ids;
    }
    
    
    public function signinForAuthToken($external_id, $password) {
        $sql = "SELECT `user_identity`.`userid` FROM `identities`, `user_identity`
                WHERE  `identities`.`external_identity` = '{$external_id}'
                AND    `identities`.`id` = `user_identity`.`identityid`";
        $rawUser = $this->getRow($sql);
        $user_id = intval($rawUser['userid']);
        if ($user_id) {
            $rtResult   = array('user_id' => $user_id);
            $passwdInDb = $this->getUserPasswdByUserId($user_id);
            $password   = $this->encryptPassword($password, $userPasswd['password_salt']);
            if ($password === $passwdInDb['encrypted_password']) {
                if (!$passwdInDb['auth_token']) {
                    $passwdInDb['auth_token'] = md5($time.uniqid());
                    $sql = "UPDATE `users` SET `auth_token` = '{$passwdInDb['auth_token']}' WHERE `id` = {$user_id}";
                }
                $rtResult['auth_token'] = $passwdInDb['auth_token'];
                return $rtResult;
            }
        }
        return null;
    }


    public function signinByCookie() {
        // get vars
        $user_id      = intval($_COOKIE['user_id']);
        $signin_sequ  = $_COOKIE['signin_sequ'];
        $signin_token = $_COOKIE['signin_token'];
        // try sign in
        if ($user_id) {
            $userPasswd = $this->getUserPasswdByUserId($user_id);
            $ipAddress  = getRealIpAddr();
            if ($ipAddress    === $userPasswd['current_sign_in_ip']
             && $signin_sequ  === $userPasswd['cookie_loginsequ']
             && $signin_token === $userPasswd['cookie_logintoken']) {
                return $this->loginByIdentityId( $identity_id,$uid,$identity ,NULL,NULL,"cookie",false);
            } 
            // unset seesion
            unset($_SESSION['signin_user']);
            unset($_SESSION['signin_token']);
            session_destroy();
            // unset cookie
            unset($_COOKIE['user_id']);    
            unset($_COOKIE['identity_ids']);
            unset($_COOKIE['signin_sequ']);
            unset($_COOKIE['signin_token']);
            setcookie('user_id',      null, -1, '/', COOKIES_DOMAIN);
            setcookie('identity_ids', null, -1, '/', COOKIES_DOMAIN);
            setcookie('signin_sequ',  null, -1, '/', COOKIES_DOMAIN);
            setcookie('signin_token', null, -1, '/', COOKIES_DOMAIN);
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
            $external_username = $identityInfo["ex_username"];
            $identity = $hlpIdentity->getIdentityByProviderAndExternalUsername($provider, $external_username);
        } else {
            $identity = $hlpIdentity->getIdentityByExternalId($identityInfo);
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
                    $this->loginByIdentityId($identity->id, $user_id, $user, $identity, 'password', $setCookie);
                    return $user;
                }

            }
        }
        return null;
    }

}
