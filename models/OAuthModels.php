<?php

class OAuthModels extends DataModel {

    public function verifyOAuthUser($oAuthUserInfo) {
        $oAuthProvider = $oAuthUserInfo["provider"];
        $oAuthUserID = $oAuthProvider."_".$oAuthUserInfo["id"];
        $oAuthUserName = $oAuthUserInfo["name"];
        $oAuthScreenName = $oAuthUserInfo["sname"];
        $oAuthUserDesc = $oAuthUserInfo["desc"];
        $oAuthUserAvatar= $oAuthUserInfo["avatar"];
        $oAuthAccessToken = $oAuthUserInfo["oauth_token"];

        $currentTimeStamp = time();

        $sql = "SELECT id FROM identities WHERE external_identity='{$oAuthUserID}'";
        $rows = $this->getRow($sql);
        //如果当前OAuth用户不存在。
        if(!is_array($rows)){
            $sql = "INSERT INTO identities (`provider`, `external_identity`, `created_at`, `updated_at`, `name`, `bio`, `avatar_file_name`, `external_username`, `oauth_token`) VALUES ('{$oAuthProvider}', '{$oAuthUserID}', FROM_UNIXTIME({$currentTimeStamp}), FROM_UNIXTIME({$currentTimeStamp}), '{$oAuthUserName}', '{$oAuthUserDesc}', '{$oAuthUserAvatar}', '{$oAuthScreenName}', '{$oAuthAccessToken}')";
            $result = $this->query($sql);
            $identityID = intval($result["insert_id"]);

            $userID = intval($_SESSION['userid']);
            //如果没有登录。则将当前OAuth用户看成是一个新的用户。
            if($userID <= 0){
                //create user for current identity
                $sql = "INSERT INTO users (`created_at`, `updated_at` , `name`, `avatar_file_name`) VALUES (FROM_UNIXTIME({$currentTimeStamp}), FROM_UNIXTIME({$currentTimeStamp}), '{$oAuthUserName}', '{$oAuthUserAvatar}')";
                $result = $this->query($sql);
                $userID = intval($result["insert_id"]);
            }

            if($identityID && $userID){
                $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID}, {$userID}, FROM_UNIXTIME($currentTimeStamp), 3)";
                $this->query($sql);
            }
        }else{
            $identityID = intval($rows["id"]);
            $sql = "UPDATE identities SET updated_at=FROM_UNIXTIME({$currentTimeStamp}), name='{$oAuthUserName}', bio='{$oAuthUserDesc}', avatar_file_name='{$oAuthUserAvatar}', external_username='{$oAuthScreenName}', oauth_token='{$oAuthAccessToken}' WHERE id={$identityID}";
            $this->query($sql);

            $sql = "SELECT userid FROM user_identity WHERE identityid={$identityID}";
            $result = $this->getRow($sql);

            $userID = intval($result["userid"]);
            //这一块是多身份合并的代码，现在先不管，暂时先留着。
            /*
            //如果已经登录，则合并账户。
            $userID = intval($_SESSION['userid']);
            if($userID <= 0){
                $userID = intval($result["userid"]);
            }else{
                $oldUserID = intval($result["userid"]);
                $sql = "UPDATE user_identity set `userid`={$userID} WHERE `identityid`={$identityID} AND `userid`={$oldUserID}";
                $this->query($sql);
            }
            */

            /*
            if(is_array($result)){
                $userID = intval($result["userid"]);
                $sql = "UPDATE users SET updated_at=FROM_UNIXTIME({$currentTimeStamp}), name='{$oAuthUserName}', avatar_file_name='{$oAuthUserAvatar}' WHERE id={$userID}";
                $this->query($sql);
            }else{
                $sql = "SELECT name, avatar_file_name FROM identities WHERE id={$identityID}";
                $identityInfo = $this->getRow($sql);
                $sql = "INSERT INTO users (`created_at`, `name`, `avatar_file_name`) VALUES (FROM_UNIXTIME({$currentTimeStamp}), '".$identityInfo["name"]."', '".$identityInfo["avatar_file_name"]."')";
                $result = $this->query($sql);
                $userID = intval($result["insert_id"]);
                if($userID){
                    $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID}, {$userID}, FROM_UNIXTIME($currentTimeStamp), 3)";
                    $this->query($sql);
                }
            }
             */
        }
        return array("identityID" => $identityID, "userID" => $userID);
    }

    public function buildFriendsIndex($userID, $friendsList) {

        $redisHandler = new Redis();
        $redisHandler->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        mb_internal_encoding("UTF-8");
        foreach($friendsList as $value)
        {
            $identity = mb_strtolower($value["user_name"]);
            $identityPart = "";
            for ($i=0; $i<mb_strlen($identity); $i++)
            {
                $identityPart .= mb_substr($identity, $i, 1);
                $redisHandler->zAdd('u:'.$userID, 0, $identityPart);
            }
            $identityDetailID = $value["provider"].":".$value["customer_id"];
            $redisHandler->zAdd('u:'.$userID, 0, $identityPart."|".$identityDetailID."*");
            $identityDetail = $redisHandler->HGET("identities",$identityDetailID);
            if($identityDetail == false) {
                $identityDetail = array(
                    "external_identity" =>$value["provider"]."_".$value["customer_id"],
                    "name"              =>$value["display_name"],
                    "bio"               =>$value["bio"],
                    "avatar_file_name"  =>$value["avatar_img"],
                    "external_username" =>$value["user_name"],
                    "provider"          =>$value["provider"]
                );
                $identity = json_encode_nounicode($identityDetail);
                $redisHandler->HSET("identities", $identityDetailID, $identity);
            }

        }
    }

    public function updateTwitterIdentity($identityId, $userInfo) {
        // ready
        if (!intval($identityId)) {
            return false;
        }
        // make parameter
        $currentTimeStamp = time();
        $oAuthUserAvatar  = preg_replace('/normal(\.[a-z]{1,5})$/i', 'reasonably_small$1', $userInfo['profile_image_url']);
        // check old identity
        $row = $this->getRow(
            "SELECT id FROM identities WHERE provider='twitter' AND external_identity='twitter_{$userInfo['id']}'"
        );
        $wasIdentityId = intval($row["id"]);
        // update identity
        $chIdentityId  = $wasIdentityId > 0 ? $wasIdentityId : $identityId;
        $this->query(
            "UPDATE identities SET updated_at=FROM_UNIXTIME({$currentTimeStamp}), name='{$userInfo['name']}', bio='{$userInfo['description']}', avatar_file_name='{$oAuthUserAvatar}', external_username='{$userInfo['screen_name']}', external_identity='twitter_{$userInfo['id']}' WHERE id={$chIdentityId}"
        );
        // merge identity
        if ($wasIdentityId > 0) {
            $this->query(
                "UPDATE invitations SET identity_id={$wasIdentityId} WHERE identity_id={$identityId}"
            );
            $this->query("DELETE FROM identities WHERE id={$identityId}");
        }
        return $chIdentityId
    }

}
