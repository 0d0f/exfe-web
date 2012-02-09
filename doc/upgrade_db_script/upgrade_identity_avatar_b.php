<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeIdentityAvatar extends DataModel{

    public function run(){
        $sql = "SELECT id, provider, external_identity, avatar_file_name FROM identities";
        $row = $this->getAll($sql);
        if(is_array($row)){
            foreach($row as $v){
                if($v["provider"] == "email" && trim($v["avatar_file_name"]) != ""){
                    $sql = "SELECT userid FROM user_identity WHERE identityid=".$v["id"];
                    $userInfo = $this->getRow($sql);
                    $userId = $userInfo["userid"];
                    $sql = "select avatar_file_name from users where id={$userId} limit 1";
                    $userAvatarArr = $this->getRow($sql);
                    $userAvatar = getUserAvatar($userAvatarArr["avatar_file_name"]);
                    $new_avatar_file_name = "http://www.gravatar.com/avatar/";
                    $new_avatar_file_name .= md5(strtolower(trim($v["external_identity"])));
                    $new_avatar_file_name .= "?d=".urlencode($userAvatar);

                    //echo $new_avatar_file_name."\r\n";
                    $sql = "UPDATE identities SET avatar_file_name='{$new_avatar_file_name}' WHERE id=".$v['id'];
                    $this->query($sql);
                    echo "upgrade for ".$v["external_identity"]."\r\n";
                }
            }
        }
        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeIdentityAvatar();
$upgradeObj->run();
?>
