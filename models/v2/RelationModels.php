<?php

class RelationModels extends DataModel {

    public function saveRelations($userid, $r_identityid) {
        $hlpIdentity = $this->getHelperByName('Identity', 'v2');
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
                            $isResult = $this->query(
                                "INSERT INTO `user_relations` SET
                                 `userid`            =  {$userid},
                                 `r_identityid`      =  {$r_identityid},
                                 `name`              = '{$identity->name}',
                                 `external_identity` = '{$identity->external_id}',
                                 `provider`          = '{$identity->provider}'"
                            );
                            return intval($isResult);
                    }
                }
            }
        }
        return 0;
    }

}
