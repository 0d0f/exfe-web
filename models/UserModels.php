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
            $result["userid"]=$uid;
            $sql="select id,auth_token from users where id=$uid and encrypted_password='$password'";
            $row=$this->getRow($sql);
            if($uid>0 && $row["auth_token"]=="")
            {
                $auth_token=md5($time.uniqid());
                $sql="update users set auth_token='$auth_token'  where id=$uid";
                $this->query($sql);
                $result["auto_token"]=$auth_token;
            }
            else if($row["auth_token"]!="")
                $result["auth_token"]=$row["auth_token"];
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

}
