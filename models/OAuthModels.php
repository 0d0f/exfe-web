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

            //如果已经登录，则合并账户。
            $userID = intval($_SESSION['userid']);
            if($userID > 0){
                if((int)$userID != intval($result["userid"])){
                    $oldUserID = intval($result["userid"]);
                    $sql = "UPDATE user_identity set `status`=1 WHERE `identityid`={$identityID} AND `userid`={$oldUserID}";
                    $this->query($sql);
                    $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID},{$userID}, FROM_UNIXTIME({$currentTimeStamp}), 3)";
                    $this->query($sql);
                }
            }else{

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
            }
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

}
