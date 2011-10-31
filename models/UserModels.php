<?php
class UserModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addUser($password)
    {
        $password=md5($password.$this->salt);
        $time=time();
        $sql="insert into users (encrypted_password,created_at) values('$password',FROM_UNIXTIME($time));";
        $result=$this->query($sql);
        if(intval($result["insert_id"])>0)
            return intval($result["insert_id"]);
    }

    public function getUser($userid)
    {
        $sql="select name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username from users where id=$userid";
        $row=$this->getRow($sql);
        return $row;
    }

    public function saveUser($name,$userid)
    {
        $sql="update users set name='$name' where id=$userid";
        $this->query($sql);
        return $this->getUser($userid);
    }

    public function saveUserAvatar($avatar,$userid)
    {
        $sql="update users set avatar_file_name='$avatar' where id=$userid";
        $this->query($sql);
        return $this->getUser($userid);
    }

    public function getUserIdByToken($token)
    {
        $sql="select id from users where auth_token='$token';";
        $row=$this->getRow($sql);
        return intval($row["id"]);
    }

    public function loginForAuthToken($user,$password)
    {
        $password=md5($password.$this->salt);
        $sql="select b.userid as uid from identities a,user_identity b where a.external_identity='$user' and a.id=b.identityid;";
        $row=$this->getRow($sql);
        $result=array();
        if(intval($row["uid"])>0)
        {
            $uid=intval($row["uid"]);
            $sql="select id,auth_token from users where id=$uid and encrypted_password='$password'";
            $row=$this->getRow($sql);
            if(intval($row["id"])==$uid )
            {
                $result["userid"]=$uid;
                if($row["auth_token"]=="")
                {
                    $auth_token=md5($time.uniqid());
                    $sql="update users set auth_token='$auth_token'  where id=$uid";
                    $this->query($sql);
                    $result["auth_token"]=$auth_token;
                }
                else
                    $result["auth_token"]=$row["auth_token"];
            }
        }
        return $result;
    }

    public function login($email,$password)
    {
        $password=md5($password.$this->salt);
        $sql="select * from users where email='$email' and encrypted_password='$password'";
#update last_sign_in_at,last_sign_in_ip...
        return $this->getRow($sql);
    }

    public function getUserProfileByIdentityId($identity_id)
    {
        $sql="select userid from user_identity where identityid=$identity_id";
        $result=$this->getRow($sql);
        if(intval($result["userid"])>0)
        {
            $userid=$result["userid"];
            $sql="select name,bio,avatar_file_name from users where id=$userid";
            $user=$this->getRow($sql);
            return $user;
        }
    }
    public function getUserIdByIdentityId($identity_id)
    {
        $sql="select userid from user_identity where identityid=$identity_id";
        $result=$this->getRow($sql);
        if(intval($result["userid"])>0)
        {
            return intval($result["userid"]);
        }
    }

    public function getUserByIdentityId($identity_id)
    {
        $sql="select userid from user_identity where identityid=$identity_id";
        $result=$this->getRow($sql);
        if(intval($result["userid"])>0)
        {
            $userid=$result["userid"];
            $sql="select * from users where id=$userid";
            $user=$this->getRow($sql);
            return $user;
        }
    }

    public function setPassword($identity_id,$password,$displayname)
    {
        $sql="select userid from user_identity where identityid=$identity_id";
        $result=$this->getRow($sql);
        if(intval($result["userid"])>0)
        {
            $userid=intval($result["userid"]);
            $password=md5($password.$this->salt);
            $sql="update users set encrypted_password ='$password',name='$displayname' where id=$userid;";
            $result=$this->query($sql);
            if($result==1)
            {
                $sql="update identities set name='$displayname' where id=$identity_id";
                $result=$this->query($sql);
                return true;
            }
        }
        return false;
        //$sql="update ";
    }

    public function setPasswordByToken($cross_id,$token,$password,$displayname)
    {
        $sql="select identity_id,tokenexpired from invitations where cross_id=$cross_id and token='$token';";
        $row=$this->getRow($sql);
        $identity_id=intval($row["identity_id"]);
        if($identity_id > 0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $result=$this->getRow($sql);
            if(intval($result["userid"])>0)
            {
                $userid=intval($result["userid"]);
                $password=md5($password.$this->salt);
                $sql="update users set encrypted_password ='$password',name='$displayname' where id=$userid;";
                $result=$this->query($sql);
                if($result==1)
                {
                    $sql="update identities set name='$displayname' where id=$identity_id";
                    $result=$this->query($sql);
                    return true;
                }
            }
        }
        return false;
    }

    public function regDeviceToken($devicetoken,$provider,$uid)
    {

        $sql="select id,status from identities where external_identity='$devicetoken' and  provider='$provider';";
        $row=$this->getRow($sql);
        $time=time();
        $identity_id=0;
        if(intval($row["id"])==0)
        {
            //insert new identity, set status connect
            $sql="insert into identities (provider,external_identity,created_at,status) values('iOSAPN','$devicetoken',FROM_UNIXTIME($time),3);";
            $result=$this->query($sql);
            if(intval($result["insert_id"])>0)
                $identity_id=$result["insert_id"];
        }
        else if(intval($row["id"])>0)
        {
            $identity_id=$row["id"];

        }
        $sql="select identityid from user_identity where identityid=$identity_id and userid=$uid;";
        $row=$this->getRow($sql);

        if(intval($row["identityid"])==0) // if dose not bind with any user?
        {
            $sql="insert into user_identity  (identityid,userid,created_at) values ($identity_id,$uid,FROM_UNIXTIME($time));";
            $this->query($sql);
        }
        else if(intval($row["identityid"])>0 && intval($row["identityid"])!=$identity_id) // bind with other user?
        {
            $sql="update user_identity set userid=$uid where identityid=$identity_id";
            $this->query($sql);
        }
        //check identity_user relationship
        //update connect status
        $sql="update identities set status=3 where id=$identity_id";
        $this->query($sql);
        return $identity_id;
    }

    public function ifIdentityBelongsUser($external_identity,$user_id)
    {
        $sql="select id from identities where external_identity='$external_identity';";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0)
        {
            $identity_id=intval($row["id"]);
            $sql="select identityid from user_identity where identityid =$identity_id and userid=$user_id;";
            $row=$this->getRow($sql);
            if(intval($row["identityid"])>0)
            {
                return $row["identityid"];
            }
        }
        return FALSE;
    }
    public function setPasswordToken($external_identity)
    {
        $sql="select b.userid as uid from identities a,user_identity b where a.external_identity='$external_identity' and a.id=b.identityid;";
        $row=$this->getRow($sql);
        $uid=intval($row["uid"]);
        if($uid>0)
        {
            $activecode=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
            $sql="update users set reset_password_token='$activecode' where id=$uid";
            $this->query($sql);
            $sql="select reset_password_token from users where id=$uid";
            $row=$this->getRow($sql);
            $token=$row["reset_password_token"];
            if($token==$activecode)
                return array("uid"=>$uid,"token"=>$activecode);
        }
        return "";
    }

    public function verifyResetPassword($userID, $userToken){
        $sql = "SELECT id,name FROM users WHERE `id`={$userID} AND `reset_password_token`='{$userToken}'";
        $row = $this->getRow($sql);
        return $row;
    }

    public function doResetUserPassword($userPwd, $userName, $userID, $userToken){
        $passWord=md5($userPwd.$this->salt);
        $ts = time();
        $sql = "UPDATE users SET encrypted_password='{$passWord}', name='{$userName}', updated_at='FROM_UNIXTIME({$ts})',reset_password_token=NULL WHERE id={$userID} AND reset_password_token='{$userToken}'";
        $result = $this->query($sql);
    }

}
