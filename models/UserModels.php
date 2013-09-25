<?php

class UserModels extends DataModel {

    private $salt = '_4f9g18t9VEdi2if';

    public  $arrUserIdentityStatus = ['', 'RELATED', 'VERIFYING', 'CONNECTED', 'REVOKED'];


    function randStr($len = 5, $type = 'normal') {
        switch($type){
            case 'num':
                $chars     = '0123456789';
                $chars_len = 10;
                break;
            case 'lowercase':
                $chars     = 'abcdefghijklmnopqrstuvwxyz';
                $chars_len = 26;
                break;
            case 'uppercase':
                $chars     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $chars_len = 26;
                break;
            default:
                $chars     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
                $chars_len = 62;
        }
        $string = '';
        for ($len; $len >= 1; $len--) {
            $position = rand() % $chars_len;
            $string  .= substr($chars, $position, 1);
        }
        return $string;
    }


    public function getMicrotime() {
        list($usec, $sec) = explode(" ", microtime());
        return (float) $usec + (float) $sec;
    }


    public function createToken() {
        $randString = $this->randStr(16);
        $hashString = md5(base64_encode(
            pack('N5', mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())
        ));
        return md5(
            $hashStr . $randString . $this->getMicrotime() . uniqid()
        ) . time();
    }


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


    public function getRawUserById($id) {
        $key = "users:{$id}";
        $rawUser = getCache($key);
        if (!$rawUser) {
            $rawUser = $this->getRow("SELECT * FROM `users` WHERE `id` = {$id}");
            setCache($key, $rawUser);
        }
        return $rawUser;
    }


    public function getUserById($id, $withCrossQuantity = false, $identityStatus = 3) {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        $hlpIdentity = $this->getHelperByName('Identity');
        $hlpDevice   = $this->getHelperByName('Device');
        $id = (int) $id;
        $rawUser = $this->getRawUserById($id);
        if ($rawUser) {
            // build user object
            $user = new User(
                $rawUser['id'],
                $rawUser['name'],
                $rawUser['bio'],
                getAvatarUrl($rawUser['avatar_file_name']),
                $rawUser['timezone'],
                [],
                [],
                $rawUser['created_at'],
                $rawUser['updated_at'],
                $rawUser['locale']
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
                    $intStatus  = (int) $item['status'];
                    $identity_infos[(int) $item['identityid']] = [
                        'status'     => $this->arrUserIdentityStatus[$intStatus],
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
                        if ($item['provider'] === 'facebook') {
                            if ($item['oauth_token']) {
                                $item['oauth_token'] = json_decode($item['oauth_token']);
                                if ($item['oauth_token']->oauth_expires < time()) {
                                    $hlpIdentity->revokeIdentity($item['id']);
                                }
                            } else {
                                $hlpIdentity->revokeIdentity($item['id']);
                            }
                        }
                        $identity = new Identity(
                            $item['id'],
                            $item['name'],
                            '', // $item['nickname'], // @todo by @leaskh;
                            $item['bio'],
                            $item['provider'],
                            $identity_infos[$item['id']]['status'] === 'CONNECTED' || $identity_infos[$item['id']]['status'] === 'REMOVED' ? $rawUser['id'] : - $item['id'],
                            $item['external_identity'],
                            $item['external_username'],
                            getAvatarUrl($item['avatar_file_name']),
                            $item['updated_at'],
                            $item['updated_at'],
                            0,
                            $item['unreachable'],
                            $item['locale'],
                            $item['timezone']
                        );
                        if (!$identity->avatar) {
                            $identity->avatar = $user->avatar ?: getDefaultAvatarUrl($identity->name);
                        }
                        $identity->avatar_filename = $identity->avatar['80_80'];
                        $identity->status  = $identity_infos[$identity->id]['status'];
                        if ($identity->status === 'VERIFYING') {
                            // remove timeout verifying {
                            $curTokens = $hlpExfeAuth->resourceGet([
                                'token_type'  => 'verification_token',
                                'action'      => 'VERIFY',
                                'identity_id' => $item['id'],
                            ]);
                            if ($curTokens && is_array($curTokens)) {
                                foreach ($curTokens as $cI => $cItem) {
                                    if ($cItem['data']['token_type'] === 'verification_token'
                                     && $cItem['data']['user_id']    === $id) {
                                        $timeout   = false;
                                        $identity->expired_time = date(
                                            'Y-m-d H:i:s',
                                            isset($cItem['data']['expired_time'])
                                          ? $cItem['data']['expired_time']
                                          : (time() + 60 * 60 * 12)
                                        ) . ' +0000';
                                        break;
                                    }
                                }
                            }
                            if ($timeout) {
                                $this->setUserIdentityStatus($id, $item['identityid'], 1);
                                contionue;
                            }
                            // }
                        }
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
                    $user->avatar          = $user->avatar          ?: $user->identities[0]->avatar;
                    $user->avatar_filename = $user->avatar_filename ?: $user->identities[0]->avatar_filename;
                    if ($withCrossQuantity) {
                        $cross_quantity = $this->getRow(
                            "SELECT COUNT(DISTINCT `exfee_id`) AS `cross_quantity` FROM `invitations` WHERE `identity_id` IN ({$identityIds})"
                        );
                        $user->cross_quantity = (int) $cross_quantity['cross_quantity'];
                    }
                }
            }
            // get all devices of user
            $user->devices = $hlpDevice->getDevicesByUserid($rawUser['id']);
            // return
            return $user;
        } else {
            return null;
        }
    }


    public function getUserIdByIdentityId($identity_id) {
        $dbResult = $this->getRawUserIdentityStatusByIdentityId($identity_id);
        return $dbResult && ($user_id = (int) $dbResult['userid']) ? $user_id : null;
    }


    public function getRawUserIdentityStatusByIdentityId($identity_id) {
        $key = "user_identity:identity_{$identity_id}";
        $rawStatus = getCache($key);
        if (!$rawStatus) {
            $rawStatus = $this->getRow(
                "SELECT * FROM `user_identity`
                 WHERE `identityid` = {$identity_id} AND `status` = 3"
            );
            setCache($key, $rawStatus);
        }
        return $rawStatus;
    }


    public function addUser($password = '', $name = '', $bio = '') {
        $passwordSql = '';
        if (strlen($password)) {
            $passwordSalt = md5($this->createToken());
            $passwordInDb = $this->encryptPassword($password, $passwordSalt);
            $passwordSql  = "`encrypted_password` = '{$passwordInDb}',
                             `password_salt`      = '{$passwordSalt}',";
        }
        $nameSql = '';
        $name = formatName($name);
        if (strlen($name)) {
            $nameSql      = "`name` = '{$name}',";
        }
        $bioSql  = '';
        $bio  = formatDescription($bio);
        if (strlen($bio)) {
            $bioSql       = "`bio`  = '{$bio}',";
        }
        $dbResult = $this->query(
            "INSERT INTO `users` SET {$passwordSql} {$nameSql} {$bioSql}
             `created_at` = NOW(), `updated_at` = NOW()"
        );
        $id = intval($dbResult['insert_id']);
        delCache("users:{$id}");
        return $id;
    }


    public function getUserIdsByIdentityIds($identity_ids, $notConnected = false) {
        $identity_ids = implode(', ', $identity_ids);
        $sql = "SELECT `userid` FROM `user_identity`
                WHERE  `identityid` IN ({$identity_ids}) AND (`status` = 3"
             . ($notConnected ? ' OR `status` = 2 OR `status` = 4' : '') . ')';
        $dbResult = $this->getAll($sql);
        $user_ids = [];
        if ($dbResult) {
            foreach ($dbResult as $uI => $uItem) {
                $user_ids[] = (int) $uItem['userid'];
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
            delCache("user_identity:identity_{$identity_id}");
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
                delCache("user_identity:identity_{$identity['identityid']}");
                $order++;
            }
        }
        return true;
    }


    public function getRegistrationFlag($identity) {
        // init models
        $hlpIdentity = $this->getHelperByName('Identity');
        // get user info
        $user_infos = $this->getUserIdentityInfoByIdentityId($identity->id);
        // no user
        if (!$user_infos) {
            $rtResult = ['reason' => 'NO_USER'];
            if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
                $rtResult['flag'] = 'VERIFY';
            } else if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['authenticate'])) {
                $rtResult['flag'] = 'AUTHENTICATE';
            } else {
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
            if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
                $rtResult['flag'] = 'VERIFY';
            } else if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['authenticate'])) {
                $rtResult['flag'] = 'AUTHENTICATE';
            } else {
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
            if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
                $rtResult['flag'] = 'VERIFY';
            } else if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['authenticate'])) {
                $rtResult['flag'] = 'AUTHENTICATE';
            } else {
                return null;
            }
            return $rtResult;
        }
        // try verifying or related user
        if (isset($user_infos['VERIFYING']) || isset($user_infos['RELATED'])) {
            $rtResult = ['reason' => 'RELATED'];
            $rtResult = array('reason'  => 'REVOKED');
            if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
                $rtResult['flag'] = 'VERIFY';
            } else if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['authenticate'])) {
                $rtResult['flag'] = 'AUTHENTICATE';
            } else {
                return null;
            }
            return $rtResult;
        }
        // }
        // failed
        return null;
    }


    public function verifyIdentity($identity, $action, $user_id = 0, $args = null, $device = '', $device_callback = '', $workflow = []) {
        // init models
        $hlpIdentity = $this->getHelperByName('Identity');
        // basic check
        if (!$identity || !$action) {
            return null;
        }
        $identity->id = (int) $identity->id;
        $user_id      = (int) $user_id;
        $raw_action   = $action;
        // check action
        switch ($action) {
            case 'VERIFY':
                $user_id = $user_id ?: $this->addUser();
                if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
                    if (!$this->setUserIdentityStatus($user_id, $identity->id, 2)) {
                        return null;
                    }
                }
                break;
            case 'VERIFY_SET_PASSWORD':
                $action = 'SET_PASSWORD';
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
                      'updated_time' => time(),
                      'raw_action'   => $raw_action];
        if ($args) {
            $data['args'] = $args;
        }
        // get current token
        $expireSec = 60 * 60 * 24 * 2; // 2 days
        $result['token'] = '';
        $curTokens = $hlpExfeAuth->resourceGet($resource);
        if ($curTokens && is_array($curTokens)) {
            foreach ($curTokens as $cI => $cItem) {
                if ($cItem['data']['token_type'] === 'verification_token'
                 && $cItem['data']['user_id']    === $user_id) {
                    $result['token'] = $cItem['token'];
                    $data            = $cItem['data'];
                    break;
                }
            }
        }
        $data['updated_time'] = time();
        $data['expired_time'] = time() + $expireSec;
        // case provider
        $short = $identity->provider === 'phone';
        if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['verification'])) {
            // call token service
            if ($result['token']) {
                $hlpExfeAuth->keyUpdate($result['token'], $data, $expireSec); // update && extension
                $actResult = true;
            } else {
                // make new token
                $actResult = $result['token'] = $hlpExfeAuth->create(
                    $resource, $data, $expireSec, $short
                );
            }
            // return
            if ($actResult) {
                return $result;
            }
        } else if (in_array($identity->provider, $hlpIdentity->modIdentity->providers['authenticate'])) {
            $hlpOAuth = $this->getHelperByName('OAuth');
            $workflow['user_id'] = $user_id;
            if ($device && $device_callback) {
                if (!isset($workflow['callback'])) {
                    $workflow['callback'] = [];
                }
                $workflow['callback']['oauth_device']          = $device;
                $workflow['callback']['oauth_device_callback'] = $device_callback;
            }
            switch ($action) {
                case 'SET_PASSWORD':
                    // update database
                    if ($result['token']) {
                        $hlpExfeAuth->keyUpdate($result['token'], $data, $expireSec); // update && extension
                        $actResult = true;
                    } else {
                        $actResult = $result['token'] = $hlpExfeAuth->create( // make new token
                            $resource, $data, $expireSec
                        );
                    }
                    if ($actResult) {
                        $workflow['verification_token'] = $result['token'];
                    }
            }
            if ($args) {
                if (!isset($workflow['callback'])) {
                    $workflow['callback'] = [];
                }
                $workflow['callback']['args'] = $args;
            }
            switch ($identity->provider) {
                case 'twitter':
                    $urlOauth = $hlpOAuth->getTwitterRequestToken($workflow);
                    break;
                case 'facebook':
                    $urlOauth = $hlpOAuth->facebookRedirect($workflow);
                    break;
                case 'dropbox':
                    $urlOauth = $hlpOAuth->dropboxRedirect($workflow);
                    break;
                case 'flickr':
                    $urlOauth = $hlpOAuth->flickrRedirect($workflow);
                    break;
                case 'instagram':
                    $urlOauth = $hlpOAuth->instagramRedirect($workflow);
                    break;
                case 'google':
                    $urlOauth = $hlpOAuth->googleRedirect($workflow);
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
        if (($curToken = $hlpExfeAuth->keyGet($token))
          && $curToken['data']['token_type'] === 'verification_token') {
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
                                        $hlpExfeAuth->keyUpdate(
                                            $token, $curToken['data']
                                        );
                                        // }
                                    }
                                }
                            }
                            // }
                            if ($siResult['password']) {
                                $hlpExfeAuth->resourceUpdate($resource, 0);
                                $rtResult['action'] = 'VERIFIED';
                            } else {
                                $hlpExfeAuth->keyUpdate($token, null, 233);
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
                        $hlpExfeAuth->keyUpdate($token, null, 233);
                        $siResult = $this->rawSignin(
                            $curToken['data']['user_id']
                        );
                        if ($siResult) {
                            return [
                                'user_id'     => $siResult['user_id'],
                                'user_name'   => $siResult['name'],
                                'identity_id' => $curToken['data']['identity_id'],
                                'token'       => $siResult['token'],
                                'token_type'  => @$curToken['raw_action'] === 'VERIFY_SET_PASSWORD' ? 'VERIFY' : $curToken['data']['action'],
                                'action'      => 'INPUT_NEW_PASSWORD',
                            ];
                        }
                    }
            }
        }
        return null;
    }


    public function resetPasswordByToken($token, $password, $name = '') {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        // basic check
        if (!$token || !$password) {
            return null;
        }
        // change password
        if (($curToken = $hlpExfeAuth->keyGet($token))
          && $curToken['data']['token_type'] === 'verification_token') {
            $cpResult  = $this->setUserPasswordAndSignin(
                $curToken['data']['user_id'], $password, $name,
                $curToken['data']['identity_id'],
                $curToken['data']['action']
            );
            if ($cpResult) {
                $hlpExfeAuth->resourceUpdate([
                    'token_type'  => $curToken['data']['token_type'],
                    'action'      => $curToken['data']['action'],
                    'identity_id' => $curToken['data']['identity_id'],
                ], 0);
                return $cpResult;
            }
        }
        return null;
    }


    public function setUserPasswordAndSignin($user_id, $password, $name, $identity_id = 0, $action = '') {
        $cpResult  =  $this->setUserPassword($user_id, $password, $name);
        if ($cpResult) {
            if (($siResult = $this->rawSignin($user_id))) {
                return [
                    'user_id'     => $siResult['user_id'],
                    'name'        => $siResult['name'],
                    'token'       => $siResult['token'],
                    'identity_id' => $identity_id,
                    'action'      => $action,
                ];
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
                $token = $hlpExfeAuth->create(
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
            $result = $hlpExfeAuth->keyGet($token);
            if ($result
             && $result['data']['token_type'] === 'user_token') {
                return $result;
            }
        }
        return null;
    }


    public function getUserCalendarToken($token) {
        if ($token) {
            $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
            $result = $hlpExfeAuth->keyGet($token);
            if ($result
             && $result['data']['token_type'] === 'calendar_token') {
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
        $this->query(
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
        delCache("user_identity:identity_{$identity_id}");
        $sqlAppend     = '';
        $tutorial_x_id = 0;
        if ($status > 1) {
             $rawIdentity = $this->getRow(
                "SELECT * FROM `identities` WHERE `id` = {$identity_id}"
            );
            $rawUsers    = $this->getRow(
                "SELECT * FROM `users`      WHERE `id` = {$user_id}"
            );
            if ($rawIdentity && $rawUsers) {
                if (!$rawUsers['locale']   && $rawIdentity['locale']) {
                    $sqlAppend .= ", `locale`   = '{$rawIdentity['locale']}'";
                }
                if (!$rawUsers['timezone'] && $rawIdentity['timezone']) {
                    $sqlAppend .= ", `timezone` = '{$rawIdentity['timezone']}'";
                }
                if ($status === 3) {
                    if (!$rawUsers['tutorial_x_id'] && $rawIdentity['tutorial_x_id']) {
                        $sqlAppend .= ", `tutorial_x_id` = '{$rawIdentity['tutorial_x_id']}'";
                    }
                }
                $tutorial_x_id = (int) ($rawIdentity['tutorial_x_id'] ?: $rawUsers['tutorial_x_id']);
            }
        }
        $this->query(
            "UPDATE `users`
             SET    `updated_at` = NOW() {$sqlAppend}
             WHERE  `id`         = {$user_id}"
        );
        delCache("users:{$user_id}");
        // tutorials {
        if (in_array($status, [2, 3])) {
            $aftStatus = $this->getConnectedIdentityCount($user_id);
            if ($aftStatus === 1 && $tutorial_x_id === 0
             && $rawIdentity['provider'] !== 'wechat') {
                require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';
                httpKit::request(
                    EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
                  . base64_url_encode(
                        SITE_URL . "/v3/bus/tutorials/1?identity_id={$identity_id}"
                    ),
                    ['update' => 'once', 'ontime' => time()], [],
                    false, false, 3, 3, 'txt'
                );
            }
        }
        // }
        return true;
    }


    public function getConnectedIdentityCount($user_id) {
        $rawResult = $this->getAll(
            "SELECT * FROM `user_identity`
             WHERE `userid` = {$user_id}
             AND   `status` = 3"
        );
        return $rawResult ? sizeof($rawResult) : null;
    }


    public function getTutorialXId($user_id, $identity_id) {
        if ($identity_id) {
            $rawResult = $this->getRow(
                "SELECT `tutorial_x_id` FROM `identities` WHERE `id` = " . (int) $identity_id . ';'
            );
            if ($rawResult) {
                return (int) $rawResult['tutorial_x_id'];
            }
        }
        if ($user_id) {
            $rawResult = $this->getRow(
                "SELECT `tutorial_x_id` FROM `users`      WHERE `id` = " . (int) $user_id     . ';'
            );
            if ($rawResult) {
                return (int) $rawResult['tutorial_x_id'];
            }
        }
        return null;
    }


    public function getIdentityIdsByUserId($user_id) {
        $user_id     = (int) $user_id;
        $identityIds = $this->getColumn(
            "SELECT `identityid`         FROM `user_identity`
             WHERE  `userid` = {$user_id} AND `status` in (3, 4)"
        );
        return $identityIds ?: [];
    }


    public function setUserPassword($user_id, $password, $name = '') {
        if (!$user_id || !$password) {
            return false;
        }
        $password = $this->encryptPassword(
            $password, $passwordSalt = md5($this->createToken())
        );
        $sqlName  = $name === '' ? '' : ", `name` = '{$name}'";
        delCache("users:{$user_id}");
        return $this->query(
            "UPDATE `users`
             SET    `encrypted_password` = '{$password}',
                    `password_salt`      = '{$passwordSalt}'{$sqlName},
                    `updated_at`         =  NOW()
             WHERE  `id`                 =  {$user_id}"
        );
    }


    public function updateUserById($user_id, $user = []) {
        if (!$user_id) {
            return false;
        }
        $update_sql = '';
        if (isset($user['name'])) {
            $update_sql .= " `name` = '{$user['name']}', ";
        }
        if (isset($user['bio'])) {
            $update_sql .= " `bio`  = '{$user['bio']}', ";
        }
        delCache("users:{$user_id}");
        return $update_sql
             ? $this->query("UPDATE `users` SET {$update_sql} `updated_at` = NOW() WHERE `id` = {$user_id}")
             : true;
    }


    public function updateAvatarById($user_id, $avatar = null) {
        if ($user_id) {
            delCache("users:{$user_id}");
            $avatar = $avatar ? json_encode($avatar) : '';
            return $this->query(
                "UPDATE `users`
                 SET    `avatar_file_name` = '{$avatar}',
                        `updated_at`       =  NOW()
                 WHERE  `id`               =  {$user_id}"
            );
        }
    }


    public function makeDefaultAvatar($name, $asimage = false) {
        require_once dirname(__FILE__) . "/../xbgutilitie/libimage.php";
        // image config
        $specification = [
            'width'      => 320,
            'height'     => 320,
            'font-width' => 220,
        ];
        $backgrounds = [
            'blue',
            'cyan',
            'green',
            'khaki',
            'magenta',
            'purple',
            'red',
        ];
        $colors = [
            [255, 255, 255],
            [255, 255, 255],
            [255, 255, 255],
            [255, 255, 255],
            [255, 255, 255],
            [255, 255, 255],
            [255, 255, 255],
        ];
        $ftSize = 128;
        // header
        if (!$asimage) {
            imageHeader();
        }
        // try cache
        $objLibImage  = new libImage;
        $cache_url    = "/v2/avatar/default?name={$name}";
        $cache_period = 60 * 60 * 24 * 3;
        $cache  = $objLibImage->getImageCache(
            IMG_CACHE_PATH, $cache_url, $cache_period, $asimage
        );
        if ($cache) {
            if ($asimage) {
                return $cache;
            }
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cache['time']) . ' GMT');
            $imfSince = @strtotime($this->params['if_modified_since']);
            if ($imfSince && $imfSince >= $cache['time']) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
            fpassthru($cache['resource']);
            fclose($cache['resource']);
            return;
        }
        // get rendom color index
        $strHsh = md5($name);
        $intHsh = 0;
        for ($i = 0; $i < 3; $i++) {
            $intHsh += ord(mb_substr($strHsh, $i, 1, 'UTF-8'));
        }
        // init path
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/../default_avatar_portrait/";
        $fLatin = "{$resDir}OpenSans-Bold.ttf";
        $fCjk   = "{$resDir}wqy-microhei.ttc";
        // get image
        $bgIdx  = fmod($intHsh, count($backgrounds));
        $image  = ImageCreateFromPNG("{$resDir}portrait_default_{$backgrounds[$bgIdx]}.png");
        // get color
        $fColor = imagecolorallocate($image, $colors[$bgIdx][0], $colors[$bgIdx][1], $colors[$bgIdx][2]);
        // get name & check CJK
        $name   = trim($name);
        $arName = explode(' ', $name);
        switch (sizeof($arName)) {
            case 0:
                $name = '';
                break;
            case 1:
                $name = ucfirst(mb_substr($arName[0], 0, 3, 'UTF-8'));
                break;
            case 2:
                $name = ucfirst(mb_substr($arName[0], 0, 2, 'UTF-8'))
                      . ucfirst(mb_substr($arName[1], 0, 1, 'UTF-8'));
                break;
            default:
                $name = ucfirst(mb_substr($arName[0], 0, 1, 'UTF-8')
                              . mb_substr($arName[1], 0, 1, 'UTF-8')
                              . mb_substr($arName[2], 0, 1, 'UTF-8'));
        }
        if (checkCjk($name = mb_substr($name, 0, 3, 'UTF-8'))
         && checkCjk($name = mb_substr($name, 0, 2, 'UTF-8'))) {
            $ftFile = $fCjk;
            $leftPd = 0;
        } else {
            $ftFile = $fLatin;
            $leftPd = 0;
        }
        $name    = mb_convert_encoding($name, 'html-entities', 'utf-8');
        // calcular font size
        $ftSize += 2;
        do {
            $ftSize -= 2;
            $posArr = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 0, $specification['height'], $fColor, $ftFile, $name);
            $fWidth = $posArr[2] - $posArr[0];
        } while ($fWidth > $specification['font-width']);
        $posArr = imagettftext(imagecreatetruecolor($specification['width'], $specification['height']), $ftSize, 0, 0, $specification['height'], $fColor, $ftFile, 'x');
        imagettftext($image, $ftSize, 0, ($specification['width'] - $fWidth) / 2 + $leftPd, ($specification['height'] + $posArr[1] - $posArr[7]) / 2, $fColor, $ftFile, $name);
        $objLibImage->setImageCache(IMG_CACHE_PATH, $cache_url, $image);
        if ($asimage) {
            return $image;
        }
        // show image
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
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
