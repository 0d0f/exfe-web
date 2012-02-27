<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

class UpgradeCrossUTCTime extends DataModel{

    public function run(){
        $time_zone = 8*60*60;

        echo "start update crosses table...\r\n";
        $sql = "SELECT id, created_at, updated_at FROM crosses";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            $sql = "UPDATE crosses SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table crosses suscess...\r\n\r\n";

        echo "start update table identities...\r\n";
        $sql = "SELECT id, created_at, updated_at, avatar_updated_at FROM identities";
        $result = $this->getAll($sql);
        foreach($result as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            $au_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time && trim($v["created_at"]) != ""){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time && trim($v["updated_at"]) != ""){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            if($v["avatar_updated_at"] != $au_utc_time && trim($v["avatar_updated_at"]) != ""){
                $au_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["avatar_updated_at"])-$time_zone));
            }
            $sql = "UPDATE identities SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}', avatar_updated_at='{$au_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table identities suscess...\r\n\r\n";

        echo "start update table invitations...\r\n";
        $sql = "SELECT id, created_at, updated_at FROM invitations";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            $sql = "UPDATE invitations SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table invitations suscess...\r\n\r\n";

        echo "start update table logs...\r\n";
        $sql = "SELECT id, time FROM logs";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $t_utc_time = "0000-00-00 00:00:00";
            if($v["time"] != $t_utc_time){
                $t_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            $sql = "UPDATE logs SET time='{$t_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table logs suscess...\r\n\r\n";

        echo "start update table places...\r\n";
        $sql = "SELECT id, created_at, updated_at FROM places";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            $sql = "UPDATE places SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table places suscess...\r\n\r\n";

        echo "start update table posts...\r\n";
        $sql = "SELECT id, created_at, updated_at FROM posts";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            $sql = "UPDATE posts SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table posts suscess...\r\n\r\n";

        echo "start update table users...\r\n";
        $sql = "SELECT id, created_at, updated_at FROM users";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            $u_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time && trim($v["created_at"]) != ""){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            if($v["updated_at"] != $u_utc_time && trim($v["updated_at"]) != ""){
                $u_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["updated_at"])-$time_zone));
            }
            $sql = "UPDATE users SET created_at='{$c_utc_time}', updated_at='{$u_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table users suscess...\r\n\r\n";

        echo "start update table user_identity...\r\n";
        $sql = "SELECT id, created_at FROM user_identity";
        $cross_time_arr = $this->getAll($sql);
        foreach($cross_time_arr as $k=>$v){
            $c_utc_time = "0000-00-00 00:00:00";
            if($v["created_at"] != $c_utc_time && trim($v["created_at"]) != ""){
                $c_utc_time = date("Y-m-d H:i:s",intval(strtotime($v["created_at"])-$time_zone));
            }
            $sql = "UPDATE user_identity SET created_at='{$c_utc_time}' WHERE id=".$v["id"];
            $this->query($sql);
        }
        echo "update table user_identity suscess...\r\n\r\n";

        echo "upgrade success....\r\n";
    }
}

$upgradeObj = new UpgradeCrossUTCTime();
$upgradeObj->run();
?>
