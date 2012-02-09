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
                if($v["provider"] == "email" && trim($v["avatar_file_name"]) == ""){
                    $avatar_file_name = "http://www.gravatar.com/avatar/";
                    $avatar_file_name .= md5(strtolower(trim($v["external_identity"])));
                    $avatar_file_name .= "?d=".urlencode(DEFAULT_AVATAR_URL);
                    $sql = "UPDATE identities SET avatar_file_name='{$avatar_file_name}' WHERE id=".$v['id'];
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
