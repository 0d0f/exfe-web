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
                            $identity->external_id       = mysql_real_escape_string(strtolower($identity->external_id));
                            $identity->external_username = mysql_real_escape_string(strtolower($identity->external_username));
                            $isResult = $this->query(
                                "INSERT INTO `user_relations` SET
                                 `userid`            =  {$userid},
                                 `r_identityid`      =  {$r_identityid},
                                 `name`              = '{$identity->name}',
                                 `external_identity` = '{$identity->external_id}',
                                 `external_username` = '{$identity->external_username}',
                                 `provider`          = '{$identity->provider}',
                                 `avatar_filename`   = '{$identity->avatar_filename}'"
                            );
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
            $identity->external_id       = mysql_real_escape_string(strtolower(trim($identity->external_id)));
            $identity->external_username = mysql_real_escape_string(strtolower(trim($identity->external_username)));
            $identity->name              = mysql_real_escape_string(trim($identity->name)) ?: $identity->external_username;
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
            $strSQL   = "INSERT INTO `user_relations` SET
                         `userid`            =  {$userid},
                         `r_identityid`      =  0,
                         `name`              = '{$identity->name}',
                         `external_identity` = '{$identity->external_id}',
                         `external_username` = '{$identity->external_username}',
                         `provider`          = '{$identity->provider}',
                         `avatar_filename`   = '{$identity->avatar_filename}'";
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


    public function getRelationIdentityById($id) {
        if ((int) $id) {
            $rawRelation = $this->getRow(
                "SELECT * FROM `user_relations` WHERE `id` = {$id}"
            );
            if ($rawRelation) {
                return new Identity(
                    0,
                    $rawRelation['name'],
                    '',
                    '',
                    $rawRelation['provider'],
                    0,
                    $rawRelation['external_identity'],
                    $rawRelation['external_username'],
                    $rawRelation['avatar_filename']
                );
            }
        }
        return null;
    }

}
