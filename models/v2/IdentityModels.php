<?php

class IdentityModels extends DataModel {

    public function getIdentityById($id)
    {
        $rawIdentity = $this->getRow("SELECT * FROM `identities` WHERE `id` = {$id}");
        if ($rawIdentity) {
            $rawUserIdentity = $this->getRow("SELECT * FROM `user_identity` WHERE `identityid` = {$id} AND `status` = 3");
            $objIdentity = new Identity($rawIdentity['id'],
                                        $rawIdentity['name'],
                                        '', // $rawIdentity['nickname'], // @todo;
                                        $rawIdentity['bio'],
                                        $rawIdentity['provider'],
                                        $rawUserIdentity ? $rawUserIdentity['userid'] : 0,
                                        $rawIdentity['external_identity'],
                                        $rawIdentity['external_username'],
                                        $rawIdentity['avatar_file_name'],
                                        $rawIdentity['avatar_updated_at'],
                                        $rawIdentity['created_at'],
                                        $rawIdentity['updated_at']);
            return $objIdentity;
        } else {
            return null;
        }
    }

}
