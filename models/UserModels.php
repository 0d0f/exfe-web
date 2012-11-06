<?php

class UserModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';

    public  $arrUserIdentityStatus = array('', 'RELATED', 'VERIFYING', 'CONNECTED', 'REVOKED');


    protected function encryptPassword($password, $password_salt) {
        return md5($password.( // compatible with the old users
            $password_salt === $this->salt ? $this->salt
          : (substr($password_salt, 3, 23) . EXFE_PASSWORD_SALT)
        ));
    }


    public function getUserPasswdByUserId($user_id) {
        return $user_id ? $this->getRow(
            "SELECT `name`, `encrypted_password`, `password_salt`,
             `current_sign_in_ip` FROM `users` WHERE `id` = {$user_id}"
        ) : null;
    }


    public function getUserById($id, $withCrossQuantity = false, $identityStatus = 3) {
        $rawUser = $this->getRow("SELECT * FROM `users` WHERE `id` = {$id}");
        if ($rawUser) {
            // build user object
            $user = new User(
                $rawUser['id'],
                $rawUser['name'],
                $rawUser['bio'],
                getAvatarUrl($rawUser['avatar_file_name']),
                $rawUser['timezone']
            );
            if ($withCrossQuantity) {
                $user->cross_quantity = 0;
            }
            // get all identity ids of user
            $identityStatus = ($identityStatus = intval($identityStatus))
                            ? "`status` = {$identityStatus}" : '(`status` > 1 AND `status` < 5)';
            $rawIdentityIds = $this->getAll(
                "SELECT `identityid`, `status`, `order`, `updated_at` FROM `user_identity` WHERE `userid` = {$rawUser['id']} AND {$identityStatus}"
            );
            // insert identities into user
            if ($rawIdentityIds) {
                $identity_infos = [];
                foreach ($rawIdentityIds as $i => $item) {
                    $identity_infos[(int) $item['identityid']] = [
                        'status'     => $this->arrUserIdentityStatus[(int) $item['status']],
                        'order'      => (int) $item['order'],
                        'updated_at' => strtotime($item['updated_at']),
                    ];
                }
                $identityIds = implode(array_keys($identity_infos), ', ');
                $identities  = $this->getAll("SELECT * FROM `identities` WHERE `id` IN ({$identityIds}) ORDER BY `id`");
                if ($identities) {
                    $sorting_identities = [];
                    foreach ($identities as $i => $item) {
                        $item['id'] = (int) $item['id'];
                        $identity = new Identity(
                            $item['id'],
                            $item['name'],
                            '', // $item['nickname'], // @todo;
                            $item['bio'],
                            $item['provider'],
                            $identity_infos[$item['id']]['status'] === 'CONNECTED' ? $rawUser['id'] : - $item['id'],
                            $item['external_identity'],
                            $item['external_username'],
                            $item['avatar_file_name'],
                            $item['updated_at'],
                            $item['updated_at']
                        );
                        $identity->avatar_filename = getAvatarUrl($identity->avatar_filename)
                                                  ?: ($user->avatar_filename
                                                  ?: getDefaultAvatarUrl($identity->name));
                        $identity->status  = $identity_infos[$identity->id]['status'];
                        if (!isset($sorting_identities[$identity_infos[$identity->id]['order']])) {
                            $sorting_identities[$identity_infos[$identity->id]['order']] = [];
                        }
                        if (!isset($sorting_identities[$identity_infos[$identity->id]['order']][$identity_infos[$identity->id]['updated_at']])) {
                            $sorting_identities[$identity_infos[$identity->id]['order']][$identity_infos[$identity->id]['updated_at']] = [];
                        }
                        $sorting_identities[$identity_infos[$identity->id]['order']][$identity_infos[$identity->id]['updated_at']][$identity->id] = $identity;

                    }
                    ksort($sorting_identities);
                    foreach ($sorting_identities as $soryByCreated) {
                        ksort($soryByCreated);
                        foreach ($soryByCreated as $sortById) {
                            foreach ($sortById as $identity) {
                                $identity->order    = count($user->identities);
                                $user->identities[] = $identity;
                            }
                        }
                    }
                    $user->name            = $user->name            ?: $user->identities[0]->name;
                    $user->bio             = $user->bio             ?: $user->identities[0]->bio;
                    $user->avatar_filename = $user->avatar_filename ?: $user->identities[0]->avatar_filename;
                    if ($withCrossQuantity) {
                        $cross_quantity = $this->getRow(
                            "SELECT COUNT(DISTINCT `cross_id`) AS `cross_quantity` FROM `invitations` WHERE `identity_id` IN ({$identityIds})"
                        );
                        $user->cross_quantity = (int) $cross_quantity['cross_quantity'];
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
            "SELECT `userid` FROM `user_identity` WHERE `identityid` = {$identity_id} AND `status` = 3"
        );
        return $dbResult && ($user_id = (int) $dbResult['userid']) ? $user_id : null;
    }


    public function addUser($password = '', $name = '') {
        $passwordSql = '';
        if ($password) {
            $passwordSalt = md5(createToken());
            $passwordInDb = $this->encryptPassword($password, $passwordSalt);
            $passwordSql  = "`encrypted_password` = '{$passwordInDb}',
                             `password_salt`      = '{$passwordSalt}',";
        }
        $nameSql = '';
        if ($name) {
            $nameSql      = "`name` = '{$name}',";
        }
        $dbResult = $this->query(
            "INSERT INTO `users` SET {$passwordSql} {$nameSql}
             `created_at` = NOW(), `updated_at` = NOW()"
        );
        return intval($dbResult['insert_id']);
    }


    public function getUserIdsByIdentityIds($identity_ids, $notConnected = false) {
        $identity_ids = implode($identity_ids, ' OR `identityid` = ');
        $sql = "SELECT `userid` FROM `user_identity`
                WHERE  `identityid` = {$identity_ids} AND (`status` = 3"
             . ($notConnected ? ' OR `status` = 2 OR `status` = 4' : '') . ')';
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
        $rawStatus = $this->getAll(
            "SELECT `userid`, `status`
             FROM   `user_identity`
             WHERE  `identityid` = {$identity_id}"
        );
        if (!$rawStatus) {
            return null;
        }
        $arrStatus = array();
        foreach ($rawStatus as $rsItem) {
            $user_id = intval($rsItem['userid']);
            $status  = $this->arrUserIdentityStatus[intval($rsItem['status'])];
            $passwd  = $this->getUserPasswdByUserId($user_id);
            $ids     = $this->getAll(
                "SELECT `identityid`
                 FROM   `user_identity`
                 WHERE  `userid` = {$user_id}"
            );
            if ($status) {
                if (!isset($arrStatus[$status])) {
                    $arrStatus[$status] = array();
                }
                $arrStatus[$status][] = array(
                    'user_id'     => (int) $user_id,
                    'password'    => !!$passwd['encrypted_password'],
                    'id_quantity' => count($ids),
                );
            }
        }
        return $arrStatus;
    }


    public function sortIdentities($user_id, $identity_order) {
        if (!$user_id || !$identity_order) {
            return false;
        }
        $rawStatus = $this->getAll(
            "SELECT   `identityid`
             FROM     `user_identity`
             WHERE    `userid` = {$user_id}
             ORDER BY `order`"
        );
        $order      = 0;
        $orderd_ids = [];
        foreach ($identity_order as $identity_id) {
            @$this->query(
                "UPDATE `user_identity`
                 SET    `order`      = {$order}
                 WHERE  `userid`     = {$user_id}
                 AND    `identityid` = {$identity_id}"
            );
            $orderd_ids[$identity_id] = true;
            $order++;
        }
        foreach ($rawStatus as $identity) {
            if (!isset($orderd_ids[$identity_id])) {
                @$this->query(
                    "UPDATE `user_identity`
                     SET    `order`      = {$order}
                     WHERE  `userid`     = {$user_id}
                     AND    `identityid` = {$identity['identityid']}"
                );
                $order++;
            }
        }
        return true;
    }


    public function getRegistrationFlag($identity) {
        // get user info
        $user_infos = $this->getUserIdentityInfoByIdentityId($identity->id);
        // no user
        if (!$user_infos) {
            $rtResult = ['reason' => 'NO_USER'];
            switch ($identity->provider) {
                case 'email':
                    $rtResult['flag'] = 'VERIFY';
                    break;
                case 'twitter':
                case 'facebook':
                    $rtResult['flag'] = 'AUTHENTICATE';
                    break;
                default:
                    return null;
            }
            return $rtResult;
        }
        // get flag {
        // try connected user
        if (isset($user_infos['CONNECTED'])) {
            $rtResult = ['user_id' => $user_infos['CONNECTED'][0]['user_id']];
            if ($user_infos['CONNECTED'][0]['password']) {
                $rtResult['flag'] = 'SIGN_IN';
                return $rtResult;
            }
            $rtResult['reason'] = 'NO_PASSWORD';
            switch ($identity->provider) {
                case 'email':
                    $rtResult['flag'] = 'VERIFY';
                    break;
                case 'twitter':
                case 'facebook':
                    $rtResult['flag'] = 'AUTHENTICATE';
                    break;
                default:
                    return null;
            }
            return $rtResult;
        }
        // try verifying user
        if (isset($user_infos['VERIFYING'])) {
            foreach ($user_infos['VERIFYING'] as $uItem) {
                if ($uItem['password'] && $uItem['id_quantity'] === 1) {
                    return [
                        'flag'    => 'SIGN_IN',
                        'reason'  => 'NEW_USER',
                        'user_id' => $uItem['user_id'],
                    ];
                }
            }
        }
        // try revoked user
        if (isset($user_infos['REVOKED'])) {
            $rtResult = array('reason'  => 'REVOKED');
            switch ($identity->provider) {
                case 'email':
                    $rtResult['flag'] = 'VERIFY';
                    break;
                case 'twitter':
                case 'facebook':
                    $rtResult['flag'] = 'AUTHENTICATE';
                    break;
                default:
                    return null;
            }
            return $rtResult;
        }
        // try verifying or related user
        if (isset($user_infos['VERIFYING']) || isset($user_infos['RELATED'])) {
            $rtResult = ['reason' => 'RELATED'];
            switch ($identity->provider) {
                case 'email':
                    $rtResult['flag'] = 'VERIFY';
                    break;
                case 'twitter':
                case 'facebook':
                    $rtResult['flag'] = 'AUTHENTICATE';
                    break;
                default:
                    return null;
            }
            return $rtResult;
        }
        // }
        // failed
        return null;
    }


    public function verifyIdentity($identity, $action, $user_id = 0, $args = null) {
        // basic check
        if (!$identity || !$action) {
            return null;
        }
        $identity->id = (int) $identity->id;
        $user_id      = (int) $user_id;
        // check action
        switch ($action) {
            case 'VERIFY':
                $user_id = $user_id ?: $this->addUser();
                switch ($identity->provider) {
                    case 'email':
                        if (!$this->setUserIdentityStatus($user_id, $identity->id, 2)) {
                            return null;
                        }
                }
                break;
            case 'SET_PASSWORD':
                break;
            default:
                return null;
        }
        if (!$user_id) {
            return null;
        }
        // get ready
        $result      = ['user_id' => $user_id];
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        // make token seed
        $resource  = ['token_type'   => 'verification_token',
                      'action'       => $action,
                      'identity_id'  => @$identity->id];
        $data      = $resource
                   + ['user_id'      => $user_id,
                      'created_time' => time(),
                      'updated_time' => time()];
        if ($args) {
            $data['args'] = $args;
        }
        // get current token
        $expireSec = 60 * 24 * 2; // 2 days
        $result['token'] = '';
        $curTokens = $hlpExfeAuth->findToken($resource);
        if ($curTokens && is_array($curTokens)) {
            foreach ($curTokens as $cI => $cItem) {
                if ($cItem['data']['token_type'] === 'verification_token'
                 && $cItem['data']['user_id']    === $user_id
                 && !$cItem['is_expire']) {
                    $result['token'] = $cItem['token'];
                    $data            = $cItem['data'];
                    break;
                }
            }
        }
        $data['updated_time'] = time();
        // case provider
        switch ($identity->provider) {
            case 'email':
                // update database
                if ($result['token']) {
                    $hlpExfeAuth->updateToken($result['token'], $data);       // update
                    $hlpExfeAuth->refreshToken($result['token'], $expireSec); // extension
                    $actResult = true;
                } else {
                    $actResult = $result['token'] = $hlpExfeAuth->generateToken( // make new token
                        $resource, $data, $expireSec
                    );
                }
                // return
                if ($actResult) {
                    return $result;
                }
                break;
            case 'twitter':
            case 'facebook':
                $hlpOAuth = $this->getHelperByName('OAuth');
                $workflow = ['user_id' => $user_id];
                switch ($action) {
                    case 'SET_PASSWORD':
                        // update database
                        if ($result['token']) {
                            $hlpExfeAuth->updateToken($result['token'], $data);       // update
                            $hlpExfeAuth->refreshToken($result['token'], $expireSec); // extension
                            $actResult = true;
                        } else {
                            $actResult = $result['token'] = $hlpExfeAuth->generateToken( // make new token
                                $resource, $data, $expireSec
                            );
                        }
                        if ($actResult) {
                            $workflow['verification_token'] = $result['token'];
                        }
                }
                if ($args) {
                    $workflow['callback'] = ['args' => $args];
                }
                switch ($identity->provider) {
                    case 'twitter':
                        $urlOauth = $hlpOAuth->getTwitterRequestToken($workflow);
                        break;
                    case 'facebook':
                        $urlOauth = $hlpOAuth->facebookRedirect($workflow);
                }

                if ($urlOauth) {
                    $result['url'] = $urlOauth;
                    return $result;
                }
                $hlpOAuth->resetSession();
        }
        // return
        return null;
    }


    public function resolveToken($token) {
        $hlpExfeAuth   = $this->getHelperByName('ExfeAuth');
        if (($curToken = $hlpExfeAuth->getToken($token))
          && $curToken['data']['token_type'] === 'verification_token'
          && !$curToken['is_expire']) {
            $resource  = ['token_type'  => $curToken['data']['token_type'],
                          'action'      => $curToken['data']['action'],
                          'identity_id' => $curToken['data']['identity_id']];
            switch ($curToken['data']['action']) {
                case 'VERIFY':
                    // get current connecting user id
                    $current_user_id = $this->getUserIdByIdentityId(
                        $curToken['data']['identity_id']
                    );
                    // connect identity id to new user id
                    $stResult = $this->setUserIdentityStatus(
                        $curToken['data']['user_id'],
                        $curToken['data']['identity_id'], 3
                    );
                    // success
                    if ($stResult) {
                        $siResult = $this->rawSignin(
                            $curToken['data']['user_id']
                        );
                        if ($siResult) {
                            $rtResult = [
                                'user_id'     => $siResult['user_id'],
                                'user_name'   => $siResult['name'],
                                'identity_id' => $curToken['data']['identity_id'],
                                'token'       => $siResult['token'],
                                'token_type'  => $curToken['data']['action'],
                            ];
                            if (@$curToken['data']['args']) {
                                $rtResult['args'] = $curToken['data']['args'];
                            }
                            // get other identities of current connecting user {
                            if ($current_user_id
                             && $current_user_id !== $curToken['data']['user_id']) {
                                $current_user = $this->getUserById(
                                    $current_user_id, false, 0
                                );
                                if ($current_user && $current_user->identities) {
                                    foreach ($current_user->identities as $iI => $iItem) {
                                        if ($iItem->status !== 'CONNECTED'
                                         && $iItem->status !== 'REVOKED') {
                                            unset($current_user->identities[$iI]);
                                        }
                                    }
                                    $current_user->identities = array_merge(
                                        $current_user->identities
                                    );
                                    if ($current_user->identities) {
                                        $rtResult['mergeable_user'] = $current_user;
                                        // updated token {
                                        $curToken['data']['merger_info'] = [
                                            'mergeable_user' => $current_user,
                                            'created_time'   => time(),
                                            'updated_time'   => time(),
                                        ];
                                        $hlpExfeAuth->updateToken($token, $curToken['data']);
                                        // }
                                    }
                                }
                            }
                            // }
                            if ($siResult['password']) {
                                $hlpExfeAuth->expireAllTokens($resource);
                                $rtResult['action'] = 'VERIFIED';
                            } else {
                                $hlpExfeAuth->refreshToken($token, 233);
                                $rtResult['action'] = 'INPUT_NEW_PASSWORD';
                            }
                            return $rtResult;
                        }
                    }
                    break;
                case 'SET_PASSWORD':
                    $stResult = $this->setUserIdentityStatus(
                        $curToken['data']['user_id'],
                        $curToken['data']['identity_id'], 3
                    );
                    if ($stResult) {
                        $hlpExfeAuth->refreshToken($token, 233);
                        $siResult = $this->rawSignin(
                            $curToken['data']['user_id']
                        );
                        if ($siResult) {
                            return [
                                'user_id'     => $siResult['user_id'],
                                'user_name'   => $siResult['name'],
                                'identity_id' => $curToken['data']['identity_id'],
                                'token'       => $siResult['token'],
                                'token_type'  => $curToken['data']['action'],
                                'action'      => 'INPUT_NEW_PASSWORD',
                            ];
                        }
                    }
            }
        }
        return null;
    }


    public function resetPasswordByToken($token, $password, $name = '') {
        $hlpExfeAuth   = $this->getHelperByName('ExfeAuth');
        // basic check
        if (!$token || !$password) {
            return null;
        }
        // change password
        if (($curToken = $hlpExfeAuth->getToken($token))
          && $curToken['data']['token_type'] === 'verification_token'
          && !$curToken['is_expire']) {
            $resource  = ['token_type'  => $curToken['data']['token_type'],
                          'action'      => $curToken['data']['action'],
                          'identity_id' => $curToken['data']['identity_id']];
            $cpResult  = $this->setUserPassword(
                $curToken['data']['user_id'], $password, $name
            );
            if ($cpResult) {
                $hlpExfeAuth->expireAllTokens($resource);
                $siResult = $this->rawSignin($curToken['data']['user_id']);
                if ($siResult) {
                    return [
                        'user_id'     => $siResult['user_id'],
                        'token'       => $siResult['token'],
                        'identity_id' => $curToken['data']['identity_id'],
                        'action'      => $curToken['data']['action'],
                    ];
                }
            }
        }
        return null;
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


    public function signinForAuthToken($provider, $external_username, $password) {
        $sql = "SELECT `user_identity`.`userid`, `user_identity`.`status`, `user_identity`.`identityid`
                FROM   `identities`, `user_identity`
                WHERE  `identities`.`provider`          = '{$provider}'
                AND    `identities`.`external_username` = '{$external_username}'
                AND    `identities`.`id` = `user_identity`.`identityid`";
        $rawUser = $this->getAll($sql) ?: [];
        foreach ($rawUser as $user) {
            if ($user_id = (int) $user['userid']) {
                $status  = (int) $user['status'];
                $passwdInDb  = $this->getUserPasswdByUserId($user_id);
                $ecPasswd    = $this->encryptPassword($password, $passwdInDb['password_salt']);
                $id_quantity = count($this->getAll(
                    "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$user_id}"
                ));
                if ((($status === 2 && $id_quantity === 1) || $status === 3)
                 && $ecPasswd === $passwdInDb['encrypted_password']) {
                    $rsResult = $this->rawSignin($user_id, $passwdInDb);
                    if ($rsResult) {
                        return $rsResult + ['identity_id' => $user['identityid']];
                    }
                }
            }
        }
        return null;
    }


    public function rawSignin($user_id, $passwdInDb = null) {
        if ($user_id) {
            $passwdInDb = $passwdInDb ?: $this->getUserPasswdByUserId($user_id);
            if ($passwdInDb) {
                $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
                $resource = ['token_type' => 'user_token',
                             'user_id'    => $user_id];
                $token = $hlpExfeAuth->generateToken(
                    $resource, $resource
                  + ['signin_time'        => time(),
                     'last_authenticate'  => time()], 31536000 // 1 year
                );
                if ($token) {
                    return [
                        'user_id'  => $user_id,
                        'name'     => $passwdInDb['name'],
                        'token'    => $token,
                        'password' => !!$passwdInDb['encrypted_password']
                    ];
                }
            }
        }
        return null;
    }


    public function getUserToken($token) {
        if ($token) {
            $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
            $result = $hlpExfeAuth->getToken($token);
            if ($result
             && $result['data']['token_type'] === 'user_token'
             && !$result['is_expire']) {
                return $result;
            }
        }
        return null;
    }


    public function setUserIdentityStatus($user_id, $identity_id, $status) {
        if (!$user_id || !$identity_id || !$status) {
            return null;
        }
        if ($status === 3 || $status === 4) {
            $this->query(
                "UPDATE `user_identity`
                 SET    `status`     = 1,
                        `updated_at` = NOW()
                 WHERE  `userid`    <> {$user_id}
                 AND    `identityid` = {$identity_id}
                 AND  ( `status`     = 2
                 OR     `status`     = 3
                 OR     `status`     = 4 )"
            );
        }
        $rawStatus = $this->getRow(
            "SELECT * FROM `user_identity`
             WHERE  `userid`     = {$user_id}
             AND    `identityid` = {$identity_id}"
        );
        $actResult = $this->query(
            $rawStatus
          ? "UPDATE `user_identity`
             SET    `status`     = {$status},
                    `updated_at` = NOW()
             WHERE  `userid`     = {$user_id}
             AND    `identityid` = {$identity_id}"
          : "INSERT INTO `user_identity`
             SET    `userid`     = {$user_id},
                    `identityid` = {$identity_id},
                    `status`     = {$status},
                    `created_at` = NOW(),
                    `updated_at` = NOW()"
        );
        return intval($actResult);
    }


    public function getIdentityIdByUserId($user_id){
        $sql="SELECT identityid FROM  `user_identity` where userid=$user_id;";
        $identity_ids=$this->getColumn($sql);
        return $identity_ids;
    }


    public function setUserPassword($user_id, $password, $name = '') {
        if (!$user_id || !$password) {
            return false;
        }
        $password = $this->encryptPassword(
            $password, $passwordSalt = md5(createToken())
        );
        $sqlName  = $name === '' ? '' : ", `name` = '{$name}'";
        return $this->query(
            "UPDATE `users`
             SET    `encrypted_password` = '{$password}',
                    `password_salt`      = '{$passwordSalt}'{$sqlName}
             WHERE  `id`                 =  {$user_id}"
        );
    }


    public function updateUserById($user_id, $user = array()) {
        if (!$user_id) {
            return false;
        }
        $update_sql = '';
        if (isset($user['name'])) {
            $update_sql .= " `name` = '{$user['name']}', ";
        }
        return $update_sql
             ? $this->query("UPDATE `users` SET {$update_sql} `updated_at` = NOW() WHERE `id` = {$user_id}")
             : true;
    }


    public function buildIdentitiesIndexes($user_id) {
        mb_internal_encoding('UTF-8');
        if (!$user_id) {
            return false;
        }
        $identities = $this->getAll(
            "SELECT * FROM `user_relations` WHERE `userid` = {$user_id}"
        );
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        foreach($identities as $identity) {
            $identity_array = explode(' ', mb_strtolower(str_replace('|', ' ', trim(
                "{$identity['name']} {$identity['external_identity']}"
            ))));
            if ($identity_array) {
                foreach($identity_array as $iaI) {
                    $identity_part = '';
                    for ($i = 0; $i < mb_strlen($iaI); $i++) {
                        $redis->zAdd(
                            "u:{$user_id}", 0,
                            $identity_part .= mb_substr($iaI, $i, 1)
                        );
                    }
                    $redis->zAdd(
                        "u:{$user_id}", 0,
                        "{$identity_part}|" . (
                            (int) $identity['r_identityid']
                          ? "rid:{$identity['r_identityid']}"
                          :  "id:{$identity['id']}"
                        ) . '*'
                    );
                }
            }
        }
        return true;
    }


    public function updateAvatarById($user_id, $avatar_filename = '') {
        return $user_id && $this->query(
            "UPDATE `users`
             SET    `avatar_file_name` = '{$avatar_filename}',
                    `updated_at`       =  NOW()
             WHERE  `id`               =  {$user_id}"
        );
    }


    public function makeDefaultAvatar($name, $asimage = false) {
        // image config
        $specification = [
            'width'      => 80,
            'height'     => 80,
            'font-width' => 62,
        ];
        $backgrounds = [
            'blue',
            'green',
            'magenta',
            'yellow',
            'khaki',
            'purple',
        ];
        $colors = [
            [135, 174, 198],
            [156, 189, 129],
            [178, 148, 173],
            [184, 129,  96],
            [189, 150, 128],
            [156, 155, 183],
        ];
        $ftSize = 36;
        $strHsh = md5($name);
        $intHsh = 0;
        for ($i = 0; $i < 3; $i++) {
            $intHsh += ord(substr($strHsh, $i, 1));
        }
        // init path
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/../default_avatar_portrait/";
        $fLatin = "{$resDir}Museo500-Regular.otf";
        $fCjk   = "{$resDir}wqy-microhei-lite.ttc";
        // get image
        $bgIdx  = fmod($intHsh, count($backgrounds));
        $image  = ImageCreateFromPNG("{$resDir}portrait_default_{$backgrounds[$bgIdx]}.png");
        // get color
        $fColor = imagecolorallocate($image, $colors[$bgIdx][0], $colors[$bgIdx][1], $colors[$bgIdx][2]);
        // get name & check CJK
        if (checkCjk($name = mb_substr($name, 0, 3, 'UTF-8'))
         && checkCjk($name = mb_substr($name, 0, 2, 'UTF-8'))) {
            $ftFile = $fCjk;
            $leftPd = 0;
        } else {
            $ftFile = $fLatin;
            $leftPd = 1;
        }
        $name   = mb_convert_encoding($name, 'html-entities', 'utf-8');
        // calcular font size
        do {
            $posArr = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 0, $specification['height'], $fColor, $ftFile, $name);
            $fWidth = $posArr[2] - $posArr[0];
            $ftSize--;
        } while ($fWidth > $specification['font-width']);
        $posArr = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 0, $specification['height'], $fColor, $ftFile, 'x');
        imagettftext($image, $ftSize, 0, ($specification['width'] - $fWidth) / 2 + $leftPd, ($specification['height'] + $posArr[1] - $posArr[7]) / 2, $fColor, $ftFile, $name);
        if ($asimage) {
            return $image;
        }
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


    public function verifyUserPassword($user_id, $password) {
        if (!$user_id) {
            return false;
        }
        $passwdInDb = $this->getUserPasswdByUserId($user_id);
        if (!$passwdInDb['encrypted_password']) {
            return null;
        }
        $password   = $this->encryptPassword($password, $passwdInDb['password_salt']);
        return $password === $passwdInDb['encrypted_password'];
    }

}
