<?php

class UserModels extends DataModel {
    
    private $salt = '_4f9g18t9VEdi2if';
    
    
    protected function getUserPasswordInfoByUserId($user_id) {
        return $this->getRow(
            "SELECT `encrypted_password`, `password_salt` FROM `users` WHERE `id` = {$user_id}"
        );
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
    
    
    public function newUserByPassword($password)
    {
        $passwordSalt = md5(createToken());
        $passwordInDb = md5($password . substr($passwordSalt, 3, 23) . EXFE_PASSWORD_SALT);
        $result = $this->query(
            "INSERT INTO `users` SET 
             `encrypted_password` = '{$password}',
             `password_salt`      = '{$passwordSalt}',
             `created_at`         = NOW()"
        );
        $userId = intval($result['insert_id']);
        return $userId > 0 ? $userId : null;
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
            $userPasswd = $this->getRow(
                "SELECT `cookie_logintoken`, `cookie_loginsequ`, `encrypted_password`, `current_sign_in_ip`
                 FROM   `users` WHERE `id` = $user_id"
            );
            $cookie_logintoken  = md5("{$userPasswd['encrypted_password']}3firwkF");
            $cookie_loginsequ   = md5(time() . 'glkfFDks.F');
            if ($userPasswd['cookie_loginsequ'] && $userPasswd['cookie_logintoken']) { // first time login, setup cookie
                $cookie_logintoken = $userPasswd['cookie_logintoken'];
                $cookie_loginsequ  = $userPasswd['cookie_loginsequ'];
            } else {    
                $this->query(
                    "UPDATE `users` SET
                     `current_sign_in_ip` = '{$ipAddress}',
                     `created_at`         = NOW(),
                     `cookie_loginsequ`   = '{$cookie_loginsequ}',
                     `cookie_logintoken`  = '{$cookie_logintoken}'
                     WHERE `id` = {$user_id}"
                );
            }
            $identity_ids = array();
            foreach ($user->identities as $i => $item) {
                $identity_ids[] = $item['id'];
            }
            $identity_ids = json_encode($identity_ids);
            setcookie('user_id',      $user_id,           time() + 31536000, '/', COOKIES_DOMAIN); // one year
            setcookie('identity_ids', $identity_ids,      time() + 31536000, '/', COOKIES_DOMAIN);
            setcookie('loginsequ',    $cookie_loginsequ,  time() + 31536000, '/', COOKIES_DOMAIN);
            setcookie('logintoken',   $cookie_logintoken, time() + 31536000, '/', COOKIES_DOMAIN);
        }
        // set session
        $_SESSION['signin_user'] = $user_id;
        unset($_SESSION['signin_token']);
        // log and return
        $this->query(
            "UPDATE `users` SET `current_sign_in_ip` = '{$ipAddress}', `last_sign_in_at` = NOW() WHERE `id` = $user_id"
        );
        return $userid;
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
                $passwdInDb = $this->getUserPasswordInfoByUserId($user_id);
                // hash the password
                if (!$password_hashed) {
                    $passwordSalt = $passwdInDb['password_salt'];
                    $password = md5(
                        // compatible with the old users
                        $passwordSalt === $this->salt
                      ? ($password . $this->salt)
                      : ($password . substr($passwordSalt, 3, 23) . EXFE_PASSWORD_SALT)
                    );
                }
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
