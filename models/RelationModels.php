<?php

class RelationModels extends DataModel {

    public function saveRelations($userid, $r_identityid) {
        $hlpIdentity = $this->getHelperByName('Identity');
        if ($userid && $r_identityid) {
            $curRelation = $this->getRow(
                "SELECT `userid`
                 FROM   `user_relations`
                 WHERE  `userid`       = {$userid}
                 AND    `r_identityid` = {$r_identityid}"
            );
            if (!$curRelation) {
                $identity = $hlpIdentity->getIdentityById($r_identityid);
                if ($identity) {
                    switch ($identity->provider) {
                        case 'email':
                        case 'phone':
                            $identity->external_id       = dbescape(strtolower($identity->external_id));
                            $identity->external_username = dbescape(strtolower($identity->external_username));
                            $avatar = $identity->avatar ? json_encode($identity->avatar) : '';
                            $isResult = $this->query(
                                "INSERT INTO `user_relations` SET
                                 `userid`            =  {$userid},
                                 `r_identityid`      =  {$r_identityid},
                                 `name`              = '{$identity->name}',
                                 `external_identity` = '{$identity->external_id}',
                                 `external_username` = '{$identity->external_username}',
                                 `provider`          = '{$identity->provider}',
                                 `avatar_filename`   = '{$avatar}'"
                            );
                            $this->buildIdentityIndex($userid, [
                                'name'              => $identity->name,
                                'external_username' => $identity->external_username,
                                'external_identity' => $identity->external_id,
                                'r_identityid'      => $r_identityid,
                            ]);
                            return intval($isResult);
                    }
                }
            }
        }
        return 0;
    }


    public function saveExternalRelations($userid, $identity) {
        $hlpIdentity = $this->getHelperByName('Identity');
        if ($userid && $identity
         && $identity->provider
         && $identity->external_id
         && $identity->external_username) {
            $identity->external_id       = dbescape(strtolower(trim($identity->external_id)));
            $identity->external_username = dbescape(strtolower(trim($identity->external_username)));
            $identity->name              = dbescape(trim($identity->name)) ?: $identity->external_username;
            $curRelation = $this->getRow(
                "SELECT `id`
                 FROM   `user_relations`
                 WHERE  `userid`            =  {$userid}
                 AND    `provider`          = '$identity->provider'
                 AND    `external_username` = '{$identity->external_username}'"
            );
            if ($curRelation) {
                return (int) $curRelation['id'];
            }
            $avatar = '';
            if (isset($identity->avatar) && is_array($identity->avatar)) {
                $avatar = json_encode($identity->avatar);
            } else if (isset($identity->avatar_filename) && $identity->avatar_filename) {
                $avatar = $identity->avatar_filename;
            }
            $strSQL   = "INSERT INTO `user_relations` SET
                         `userid`            =  {$userid},
                         `r_identityid`      =  0,
                         `name`              = '{$identity->name}',
                         `external_identity` = '{$identity->external_id}',
                         `external_username` = '{$identity->external_username}',
                         `provider`          = '{$identity->provider}',
                         `avatar_filename`   = '{$avatar}'";
            $isResult = $this->query($strSQL);
            if (!($isId = (int) $isResult) && DEBUG) {
                error_log(json_encode(['user_id' => $userid, 'identity' => $identity, 'sql' => $strSQL]));
            }
            return $isId;
        }
        if (DEBUG) {
            error_log(json_encode(['user_id' => $userid, 'identity' => $identity]));
        }
        return 0;
    }


    public function buildIdentitiesIndexes($user_id) {
        if (!$user_id) {
            return false;
        }
        $identities = $this->getAll(
            "SELECT * FROM `user_relations` WHERE `userid` = {$user_id}"
        );
        foreach($identities as $identity) {
            $this->buildIdentityIndex($user_id, $identity);
        }
        return true;
    }


    public function buildIdentityIndex($user_id, $identity) {
        mb_internal_encoding('UTF-8');
        if (!$user_id || !$identity) {
            return false;
        }
        global $redis;
        $identity_array = explode(' ', mb_strtolower(str_replace('|', ' ', trim(
            "{$identity['name']} " . (
                $identity['external_username'] ?: $identity['external_identity']
            )
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
        return true;
    }


    public function getRelationIdentityById($id) {
        if ((int) $id) {
            $rawRelation = $this->getRow(
                "SELECT * FROM `user_relations` WHERE `id` = {$id}"
            );
            if ($rawRelation) {
                $rawRelation['external_username'] = $rawRelation['external_username'] ?: $rawRelation['external_identity'];
                $rawRelation['avatar_filename']   = getAvatarUrl($rawRelation['avatar_filename']);
                if (!$rawRelation['avatar_filename']) {
                    $rawRelation['avatar_filename'] = getDefaultAvatarUrl($rawRelation['name']);
                    if ($rawRelation['provider'] === 'email') {
                        $hlpIdentity = $this->getModelByName('Identity');
                        $md5Identity = md5($rawRelation['external_identity']);
                        $rawRelation['avatar_filename'] = [
                            'original' => $hlpIdentity->getGravatarUrlByExternalUsername($md5Identity, 2048, '', $rawRelation['avatar_filename']['original']),
                            '320_320'  => $hlpIdentity->getGravatarUrlByExternalUsername($md5Identity, 320,  '', $rawRelation['avatar_filename']['320_320']),
                            '80_80'    => $hlpIdentity->getGravatarUrlByExternalUsername($md5Identity, 80,   '', $rawRelation['avatar_filename']['80_80']),
                        ];
                    }
                }
                return new Identity(
                    0, $rawRelation['name'], '', '', $rawRelation['provider'],
                    0, $rawRelation['external_identity'],
                    $rawRelation['external_username'],
                    $rawRelation['avatar_filename']
                );
            }
        }
        return null;
    }

}
