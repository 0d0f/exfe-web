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


    protected function getTokenInfo($token) {
        $time = Time();
        return $token ? $this->getRow(
            "SELECT * FROM `tokens`
             WHERE `token`           = '{$token}'
             AND   `expiration_date` >  FROM_UNIXTIME({$time})
             AND   `used_at`         =  0"
        ) : null;
    }


    protected function getSimilarTokenInfo($identity_id, $user_id, $action) {
        return $identity_id && $user_id && $action ? $this->getRow(
            "SELECT * FROM `tokens`
             WHERE `identity_id` =  {$identity_id}
             AND   `user_id`     =  {$user_id}
             AND   `action`      = '{$action}'"
        ) : null;
    }


    protected function usedToken($token) {
        $usResult = $token
                 && $token['id']
                 && $token['identity_id']
                 && $token['action']
                 && $this->query(
            "UPDATE `tokens`
             SET    `expiration_date` = NOW(),
                    `used_at`         = NOW()
             WHERE  `id`              = {$token['id']}"
        );
        if ($usResult) {
            return $this->destroySimilarTokens(
                $token['identity_id'], $token['action']
            );
        }
        return false;
    }


    protected function insertNewToken($token, $action, $identity_id, $user_id, $expiration_date = 2880) { // exp in minutes: 60 * 24 * 2 = 2 days
        if ($token && $action && $identity_id && $user_id && $expiration_date) {
            $expiration_date += Time();
            return $this->query(
                "INSERT INTO `tokens` SET
                 `token`           = '{$token}',
                 `action`          = '{$action}',
                 `identity_id`     =  {$identity_id},
                 `user_id`         =  {$user_id},
                 `created_at`      =  NOW(),
                 `expiration_date` =  FROM_UNIXTIME({$expiration_date}),
                 `used_at`         =  0"
            );
        }
        return false;
    }


    protected function extendTokenExpirationDate($id, $expiration_date = 3, $new_token = '') { // exp in minutes
        $expiration_date = Time() + ($expiration_date * 60);                                   // for 3 minutes by default
        $sql_token       = $new_token ? ", `token` = '{$new_token}'" : '';
        return $id ? $this->query(
            "UPDATE `tokens` SET
             `expiration_date` = FROM_UNIXTIME({$expiration_date}) {$sql_token},
             `used_at`         = 0
             WHERE  `id`       = {$id}"
        ) : false;
    }


    public function destroySimilarTokens($identity_id, $action) {
        return $identity_id && $action ? $this->query(
            "UPDATE `tokens`
             SET    `expiration_date` =  NOW()
             WHERE  `identity_id`     =  {$identity_id}
             AND    `action`          = '{$action}'"
        ) : false;
    }


    public function getUserPasswdByUserId($user_id) {
        return $user_id ? $this->getRow(
            "SELECT `name`, `auth_token`, `encrypted_password`, `password_salt`,
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
                null, // default_identity
                $rawUser['avatar_file_name'] ? getAvatarUrl('', '', $rawUser['avatar_file_name']) : '',
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
                            $item['external_username'],
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
                    $user->name             = $user->name             ?: $user->default_identity->name;
                    if ($withCrossQuantity) {
                        $cross_quantity = $this->getRow(
                            "SELECT COUNT(DISTINCT `cross_id`) AS `cross_quantity` FROM `invitations` WHERE `identity_id` IN ({$identityIds})"
                        );
                        $user->cross_quantity  = (int) $cross_quantity['cross_quantity'];
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
            "SELECT `userid` FROM `user_identity` WHERE `identityid` = {$identity_id} AND `status` = 3"
        );
        $user_id = intval($dbResult["userid"]);
        return $user_id ?: null;
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
            "INSERT INTO `users` SET {$passwordSql} {$nameSql} `created_at` = NOW()"
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


    public function verifyIdentity($identity, $action, $user_id = 0) {
        // basic check
        if (!$identity || !$action) {
            return null;
        }
        // check action
        switch ($action) {
            case 'VERIFY':
                if ($user_id) {
                    $setResult = $this->setUserIdentityStatus(
                        $user_id, $identity->id, 2
                    );
                    if (!$setResult) {
                        return null;
                    }
                } else {
                    // 创建新用户并设为验证状态
                    $user_id = $this->addVerifyingEmptyUserByIdentityId(
                        $identity->id
                    );
                    if (!$user_id) {
                        return null;
                    }
                }
                break;
            case 'SET_PASSWORD':
                if (!$user_id) {
                    return null;
                }
                break;
            default:
                return null;
        }
        // get ready
        $result = array('user_id' => $user_id);
        // case provider
        switch ($identity->provider) {
            case 'email':
                // get current token
                $curToken = $this->getSimilarTokenInfo(
                    $identity->id, $user_id, $action
                );
                // make new token
                $result['token'] = md5(json_encode(array(
                    'action'      => $action,
                    'identity_id' => $identity->id,
                    'user_id'     => $user_id,
                    'microtime'   => Microtime(),
                    'random'      => Rand(0, Time()),
                    'unique_id'   => Uniqid(),
                )));
                // update database
                if ($curToken) {
                    if (strtotime($curToken['expiration_date']) > Time()
                     && strtotime($curToken['used_at']) <= 0) {        // extension
                        $result['token'] = $curToken['token'];
                    }
                    $actResult = $this->extendTokenExpirationDate(
                        $curToken['id'], 60 * 24 * 2, $result['token'] // 2 days
                    );
                } else {
                    $actResult = $this->insertNewToken(
                        $result['token'], $action, $identity->id, $user_id
                    );
                }
                // return
                if ($actResult) {
                    return $result;
                }
                break;
            case 'twitter':
                // @todo by @leaskh
        }
        // return
        return null;
    }


    public function resolveToken($token) {
        if (($curToken = $this->getTokenInfo($token))) {
            $curToken['user_id']     = (int) $curToken['user_id'];
            $curToken['identity_id'] = (int) $curToken['identity_id'];
            switch ($curToken['action']) {
                case 'VERIFY':
                    $stResult = $this->setUserIdentityStatus(
                        $curToken['user_id'], $curToken['identity_id'], 3
                    );
                    if ($stResult) {
                        $siResult = $this->rawSignin($curToken['user_id']);
                        if ($siResult) {
                            if ($siResult['password']) {
                                $this->usedToken($curToken);
                                $nextAction = 'VERIFIED';
                            } else {
                                $this->extendTokenExpirationDate($curToken['id']);
                                $nextAction = 'INPUT_NEW_PASSWORD';
                            }
                            return [
                                'user_id'     => $siResult['user_id'],
                                'user_name'   => $siResult['name'],
                                'identity_id' => $curToken['identity_id'],
                                'token'       => $siResult['token'],
                                'token_type'  => $curToken['action'],
                                'action'      => $nextAction,
                            ];
                        }
                    }
                    break;
                case 'SET_PASSWORD':
                    $stResult = $this->setUserIdentityStatus(
                        $curToken['user_id'], $curToken['identity_id'], 3
                    );
                    if ($stResult) {
                        $this->extendTokenExpirationDate($curToken['id']);
                        $siResult = $this->rawSignin($curToken['user_id']);
                        if ($siResult) {
                            return [
                                'user_id'     => $siResult['user_id'],
                                'user_name'   => $siResult['name'],
                                'identity_id' => $curToken['identity_id'],
                                'token'       => $siResult['token'],
                                'token_type'  => $curToken['action'],
                                'action'      => 'INPUT_NEW_PASSWORD',
                            ];
                        }
                    }
            }
        }
        return null;
    }


    public function resetPasswordByToken($token, $password, $name = '') {
        // basic check
        if (!$token || !$password) {
            return null;
        }
        // change password
        if (($curToken = $this->getTokenInfo($token))) {
            $cpResult  = $this->setUserPassword($curToken['user_id'], $password, $name);
            if ($cpResult) {
                $siResult = $this->rawSignin($curToken['user_id']);
                $this->usedToken($curToken);
                return array(
                    'user_id'     => $siResult['user_id'],
                    'token'       => $siResult['token'],
                    'identity_id' => $curToken['identity_id'],
                    'action'      => $curToken['action'],
                );
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
                $siResult = true;
                if (!$passwdInDb['auth_token']) {
                    $passwdInDb['auth_token'] = md5($time.uniqid());
                    $siResult = $this->query(
                        "UPDATE `users`
                         SET    `auth_token` = '{$passwdInDb['auth_token']}'
                         WHERE  `id`         =  {$user_id}"
                    );
                }
                if ($siResult) {
                    return [
                        'user_id'  => $user_id,
                        'name'     => $passwdInDb['name'],
                        'token'    => $passwdInDb['auth_token'],
                        'password' => !!$passwdInDb['encrypted_password']
                    ];
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


    public function addVerifyingEmptyUserByIdentityId($identity_id) {
        // basic check
        if (!$identity_id) {
            return null;
        }
        // add new user
        $isuResult = $this->query(
            "INSERT INTO `users` SET `created_at` = NOW()"
        );
        // add verifying relation
        if ($isuResult && isset($isuResult['insert_id'])) {
            $user_id   = (int) $isuResult['insert_id'];
            $isrResult = $this->query(
                "INSERT INTO `user_identity` SET
                 `identityid` = {$identity_id},
                 `userid`     = {$user_id},
                 `status`     = 2,
                 `created_at` = NOW(),
                 `updated_at` = NOW()"
            );
            return intval($isrResult) ? $user_id : null;
        }
        return null;
    }


    public function setUserIdentityStatus($user_id, $identity_id, $status) {
        if (!$user_id || !$identity_id || !$status) {
            return null;
        }
        if ($status === 3) {
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


    public function getUserAvatarByProviderAndExternalUsername($provider, $external_username) {
        $hlpIdentity = $this->getHelperByName('identity');
        $rawIdentity = $this->getRow(
            "SELECT `id`, `name` FROM `identities`
             WHERE  `provider`          = '{$provider}'
             AND    `external_username` = '{$external_username}'"
        );
        $name = '';
        if ($rawIdentity && $rawIdentity['id']) {
            $rawUser = $this->getRow(
                "SELECT `users`.`avatar_file_name` FROM `users`, `user_identity`
                 WHERE  `user_identity`.`userid`     = `users`.`id`
                 AND    `user_identity`.`identityid` = {$rawIdentity['id']}
                 AND    `user_identity`.`status`     = 3"
            );
            if ($rawUser && $rawUser['avatar_file_name']) {
                return ['url' => $rawUser['avatar_file_name'], 'type' => 'url'];
            }
            $name = $rawIdentity['name'];
        }
        if (!$name) {
            switch ($provider) {
                case 'email':
                    $objEmail = $hlpIdentity->parseEmail($external_username);
                    if ($objEmail) {
                        $name = $objEmail['name'];
                    }
            }
        }
        return ['name' => $name ?: $external_username, 'type' => 'name'];
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
            'font-width' => 64,
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
            [175, 161, 120],
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
        $ftFile = checkCjk($name = mb_substr($name, 0, 3, 'UTF-8'))
               && checkCjk($name = mb_substr($name, 0, 2, 'UTF-8')) ? $fCjk : $fLatin;
        $name   = mb_convert_encoding($name, 'html-entities', 'utf-8');
        // calcular font size
        do {
            $posArr  = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 3, 50, $fColor, $ftFile, $name);
            $fWidth  = $posArr[2] - $posArr[0];
            $fHeight = $posArr[1] - $posArr[7];
            $ftSize--;
        } while ($fWidth > $specification['font-width']);
        imagettftext($image, $ftSize, 0, ($specification['width'] - $fWidth) / 2, 50, $fColor, $ftFile, $name);
        // return
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

}
