<?php
class OAuthModels extends DataModel{
    public function verifyOAuthUser($oAuthUserInfo){

        $oAuthUserID = $oAuthUserInfo["id"];
        $oAuthUserName = $oAuthUserInfo["name"];
        $oAuthScreenName = $oAuthUserInfo["sname"];
        $oAuthUserDesc = $oAuthUserInfo["desc"];
        $oAuthUserAvatar= $oAuthUserInfo["avatar"];

        $currentTimeStamp = time();

        $sql = "SELECT id FROM identities WHERE external_identity={$oAuthUserID}";
        $rows = $this->getRow($sql);
        if(!is_array($rows)){
            $sql = "INSERT INTO identities (`provider`, `external_identity`, `created_at`, `name`, `bio`, `avatar_file_name`, `external_username`) VALUES ('twitter', '{$oAuthUserID}', FROM_UNIXTIME({$currentTimeStamp}), '{$oAuthUserName}', '{$oAuthUserDesc}', '{$oAuthUserAvatar}', '{$oAuthScreenName}')";
            $result = $this->query($sql);
            $identityID = intval($result["insert_id"]);

            //create user for current identity
            $sql = "INSERT INTO users (`created_at`, `name`, `avatar_file_name`) VALUES (FROM_UNIXTIME({$currentTimeStamp}), '{$oAuthUserName}', '{$oAuthUserAvatar}')";
            $result = $this->query($sql);
            $userID = intval($result["insert_id"]);

            if($identityID && $userID){
                $sql = "INSERT INTO user_identity (`identityid`, `userid`, `created_at`, `status`) VALUES ({$identityID}, {$userID}, FROM_UNIXTIME($currentTimeStamp), 3)";
                $this->query($sql);
            }
        }else{
            $identityID = intval($rows["id"]);
            $sql = "SELECT userid FROM user_identity WHERE identityid={$identityID}";
            $result = $this->getRow($sql);
            if(is_array($result)){
                $userID = intval($result["userid"]);
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
        return array("identityID" => $identityID, "userID" => $userID);
    }
}
