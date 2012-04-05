<?php

class UserModels extends DataModel {
    
    public function getUserById($id)  {
        /*---------------------+---------------------+------+-----+---------+----------------+
        | Field                | Type                | Null | Key | Default | Extra          |
        +----------------------+---------------------+------+-----+---------+----------------+
        | id                   | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
        | encrypted_password   | varchar(128)        | NO   |     |         |                |
        | password_salt        | varchar(128)        | NO   |     |         |                |
        | reset_password_token | varchar(255)        | YES  | UNI | NULL    |                |
        | remember_created_at  | datetime            | YES  |     | NULL    |                |
        | sign_in_count        | int(11)             | YES  |     | 0       |                |
        | current_sign_in_at   | datetime            | YES  |     | NULL    |                |
        | last_sign_in_at      | datetime            | YES  |     | NULL    |                |
        | current_sign_in_ip   | varchar(255)        | YES  |     | NULL    |                |
        | last_sign_in_ip      | varchar(255)        | YES  |     | NULL    |                |
        | created_at           | datetime            | YES  |     | NULL    |                |
        | updated_at           | datetime            | YES  |     | NULL    |                |
        | name                 | varchar(255)        | NO   |     | NULL    |                |
        | bio                  | text                | NO   |     | NULL    |                |
        | avatar_file_name     | varchar(255)        | NO   |     | NULL    |                |
        | avatar_content_type  | varchar(255)        | NO   |     | NULL    |                |
        | avatar_file_size     | int(11)             | YES  |     | NULL    |                |
        | avatar_updated_at    | datetime            | YES  |     | NULL    |                |
        | external_username    | varchar(255)        | NO   |     | NULL    |                |
        | cookie_logintoken    | varchar(255)        | NO   |     | NULL    |                |
        | cookie_loginsequ     | varchar(255)        | NO   |     | NULL    |                |
        | auth_token           | varchar(32)         | NO   | MUL | NULL    |                |
        | timezone             | varchar(10)         | YES  |     | NULL    |                |
        | default_identity     | int(11)             | NO   |     | NULL    |                |
        +----------------------+---------------------+------+-----+---------+---------------*/
        
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
            if (!$rawIdentityIds) {
                return $user;
            }
            $identityIds    = array();
            foreach ($rawIdentityIds as $i => $item) {
                $identityIds[] = $item['identityid'];
            }
            $identityIds = implode($identityIds, ', OR `id` = ');
            $identities  = $this->getAll("SELECT * FROM `identities` WHERE `id` = {$identityIds}");
            if (!$identities) {
                return $user;
            }
            foreach ($identities as $i => $item) {
                $user->identities[] = new Identity($item['id'],
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
                                                   $item['updated_at']);
               if (intval($item['id']) === intval($rawUser['id'])) {
                   $user()
               }
            }
            return $user;
        } else {
            return null;
        }
        
        return $result;
    }

}
