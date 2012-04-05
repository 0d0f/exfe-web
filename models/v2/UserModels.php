<?php

class UserModels extends DataModel {
    
    public function getUserById($id)  {
        $rawUser = $this->getRow("SELECT * FROM `users` WHERE `id` = {$id}");
        if ($rawUser) {
            // build user object
            $user = new User($rawUser['id'],
                             $rawUser['name'],
                             $rawUser['bio'],
                             null, // $rawUser[''] default_identity
                             $rawUser['avatar_file_name'],
                             $rawUser['avatar_updated_at'],
                             $rawUser['timezone']);
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
                            new Identity($item['id'],
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
                                         $item['updated_at'])
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

}
