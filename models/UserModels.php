<?php
class UserModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addUser($password)
    {
        $passwordSalt = md5(createToken());
        $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
        $time=time();
        $sql="INSERT INTO users (encrypted_password, password_salt, created_at) VALUES ('{$password}','{$passwordSalt}',FROM_UNIXTIME($time));";
        $result=$this->query($sql);
        if(intval($result["insert_id"])>0)
            return intval($result["insert_id"]);
    }

    public function addUserAndSetRelation($password,$displayname,$identity_id=0,$external_identity="")//$external_identity,
    {

        $external_identity = mysql_real_escape_string($external_identity);
        $displayname = mysql_real_escape_string($displayname);


        if($identity_id == 0 && $external_identity != "")
        {
            $sql="select id from identities where external_identity='$external_identity';";
            $result=$this->getRow($sql);
            $identity_id=$result["id"];
        }

        $sql="select userid from user_identity where identityid=$identity_id";
        
        $result=$this->getRow($sql);
        if(intval($result["userid"]) > 0)
        {
            $uid=intval($result["userid"]);
            return array("uid"=>$uid,"identity_id"=>$identity_id);
        } else {
            $time=time();
            $sql="insert into users (encrypted_password,name,created_at) values('$password','$displayname',FROM_UNIXTIME($time));";
            $result=$this->query($sql);
            if(intval($result["insert_id"])>0)
            {
                $uid=intval($result["insert_id"]);
                $sql="insert into user_identity  (identityid,userid,created_at) values ($identity_id,$uid,FROM_UNIXTIME($time));";
                $this->query($sql);
                $sql="select userid from user_identity where identityid=$identity_id";
                $result=$this->getRow($sql);
                if(intval($result["userid"])>0)
                {
                    if($displayname!="")
                    {
                        //$sql="update status,identities set status=3,name='$displayname' where id=$identity_id";
                        $sql="UPDATE identities SET name='$displayname' WHERE id=$identity_id";
                        $this->query($sql);
                        $sql="UPDATE user_identity SET status=3 WHERE identityid=$identity_id";
                        $this->query($sql);
                    }
                    if($uid==intval($result["userid"]))
                        return array("uid"=>$uid,"identity_id"=>$identity_id);
                    return false;
                }
            }
        }

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
        $sql="select b.userid as uid from identities a,user_identity b where a.external_identity='$user' and a.id=b.identityid;";
        $row=$this->getRow($sql);
        $result=array();
        if(intval($row["uid"])>0)
        {
            $uid=intval($row["uid"]);
            $sql = "SELECT password_salt FROM users WHERE id={$uid}";
            $result = $this->getRow($sql);
            $passwordSalt = $result["password_salt"];
            if($passwordSalt == $this->salt){
                $password=md5($password.$this->salt);
            }else{
                $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
            }

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

            $passwordSalt = md5(createToken());
            $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);

            //$password=md5($password.$this->salt);
            $sql="UPDATE users SET encrypted_password='{$password}', password_salt='{$passwordSalt}',name='{$displayname}' WHERE id={$userid}";
            $result=$this->query($sql);
            if($result==1)
            {
                $sql="UPDATE identities SET name='$displayname' WHERE id=$identity_id";
                $result=$this->query($sql);
                return true;
            }
        }
        return false;
        //$sql="update ";
    }
    public function addUserByToken($cross_id,$password,$displayname,$token)
    {
        $sql = "select identity_id,tokenexpired from invitations where cross_id=$cross_id and token='$token';";
        $row=$this->getRow($sql);
        $identity_id=intval($row["identity_id"]);
        if($identity_id > 0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $result=$this->getRow($sql);
            if(intval($result["userid"])>0)
            {
                //user exist, set password
                return array("uid"=>intval($result["userid"]),"identity_id"=>$identity_id);
            } else {
                $passwordSalt = md5(createToken());
                $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);

                $time=time();
                $sql="INSERT INTO users (encrypted_password,password_salt,name,created_at) VALUES ('{$password}','{$passwordSalt}','{$displayname}',FROM_UNIXTIME($time));";
                $result=$this->query($sql);
                if(intval($result["insert_id"])>0)
                {
                    $uid=intval($result["insert_id"]);
                    $sql="insert into user_identity  (identityid,userid,created_at) values ($identity_id,$uid,FROM_UNIXTIME($time));";
                    $this->query($sql);
                    $sql="select userid from user_identity where identityid=$identity_id";
                    $result=$this->getRow($sql);
                    if(intval($result["userid"])>0)
                    {
                        if($displayname!="")
                        {
                            //$sql="update status,identities set status=3,name='$displayname' where id=$identity_id";
                            $sql="UPDATE identities SET name='$displayname' WHERE id=$identity_id";
                            $this->query($sql);
                            $sql="UPDATE user_identity SET status=3 WHERE identityid=$identity_id";
                            $this->query($sql);
                        }
                        if($uid==intval($result["userid"]))
                            return array("uid"=>$uid,"identity_id"=>$identity_id);
                        return false;
                    }
                }
    //add user
            }
        }
        return false;

    }

    public function setPasswordByToken($cross_id,$token,$password,$displayname)
    {
        $sql="select identity_id,tokenexpired from invitations where cross_id=$cross_id and token='$token';";
        $row=$this->getRow($sql);
        $identity_id=intval($row["identity_id"]);
        $tokenexpired=intval($row["tokenexpired"]);
        if(tokenexpired==2)
            return false;
        if($identity_id > 0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $result=$this->getRow($sql);
            if(intval($result["userid"])>0)
            {
                $userid=intval($result["userid"]);

                $passwordSalt = md5(createToken());
                $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
                //$password=md5($password.$this->salt);
                $sql="UPDATE users SET encrypted_password='{$password}', password_salt='{$passwordSalt}', name='{$displayname}' WHERE id={$userid}";
                $result=$this->query($sql);
                if($result==1)
                {
                    $sql="update identities set name='$displayname' where id=$identity_id";
                    $result=$this->query($sql);
                    return array("uid"=>$userid,"identity_id"=>$identity_id);
                }
            }
        }
        return false;
    }

    //todo for huoju
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
    public function getResetPasswordToken($external_identity)
    {
        $sql="select b.userid as uid ,a.name as name from identities a,user_identity b where a.external_identity='$external_identity' and a.id=b.identityid;";
        $row=$this->getRow($sql);
        $uid=intval($row["uid"]);
        $name=$row["name"];
        if($uid==0)
        {
            $result=$this->addUserAndSetRelation($password,$displayname,0,$external_identity);
            if($result!=false)
                $uid=intval($result["uid"]);
        }

        if($uid > 0)
        {
            $sql = "SELECT reset_password_token FROM users WHERE id={$uid}";
            $result = $this->getRow($sql);
            $resetPasswordToken = $result["reset_password_token"];
            if(trim($resetPasswordToken) == "" || $resetPasswordToken == null){
                $resetPasswordToken = createToken();
                $sql="update users set reset_password_token='$resetPasswordToken' where id=$uid";
                $this->query($sql);
            }else{
                $tokenTimeStamp = substr($resetPasswordToken, 32);
                $curTimeStamp = time();
                //如果Token已经过期。
                if(intval($tokenTimeStamp)+5*24*60*60 < $curTimeStamp){
                    $resetPasswordToken = createToken();
                    $sql="update users set reset_password_token='$resetPasswordToken' where id=$uid";
                    $this->query($sql);
                }
            }
            $returnData = array(
                "uid"   =>$uid,
                "name"  =>$name,
                "token" =>$resetPasswordToken
            );
            
            return $returnData;
        }
        return "";
    }

    public function verifyResetPassword($userID, $resetPasswordToken){
        $sql = "SELECT id,name FROM users WHERE `id`={$userID} AND `reset_password_token`='{$resetPasswordToken}'";
        $row = $this->getRow($sql);
        return $row;
    }

    public function delResetPasswordToken($userID, $resetPasswordToken){
        $sql = "SELECT id FROM users WHERE `id`={$userID} AND `reset_password_token`='{$resetPasswordToken}'";
        $row = $this->getRow($sql);
        if(is_array($row)){
            $sql = "UPDATE users SET `reset_password_token`=NULL WHERE `id`={$userID}";
            $this->query($sql);
        }
    }

    public function doResetUserPassword($userPwd, $userName, $userID, $external_identity,$userToken){
        $ts = time();
        $sql = "select id,encrypted_password from users WHERE id={$userID} AND reset_password_token='{$userToken}';";
        $userrow = $this->getRow($sql);
        $newUser = false;


        if(intval($userrow["id"])>0 && $userrow["encrypted_password"]==""){
            $newUser = true;
        }

        $passwordSalt = md5(createToken());
        $passWord=md5($userPwd.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);

        $sql = "UPDATE users SET encrypted_password='{$passWord}', password_salt='{$passwordSalt}', name='{$userName}', updated_at='FROM_UNIXTIME({$ts})',reset_password_token=NULL WHERE id={$userID} AND reset_password_token='{$userToken}';";
        $result = $this->query($sql);

        $external_identity=mysql_real_escape_string($external_identity);
        //$sql="select id,status from identities where external_identity='$external_identity' limit 1";
        $sql="select id from identities where external_identity='$external_identity' limit 1";
        $identityrow=$this->getRow($sql);
        $identity_id=intval($identityrow["id"]);

        $sql = "SELECT status FROM user_identity WHERE identityid={$identity_id}";
        $rows = $this->getRow($sql);

        if($rows["status"]!=STATUS_CONNECTED && $identity_id>0)
        {
            //$sql="update identities set status=3 where id=$identity_id;";
            $sql="UPDATE user_identity SET status=3 WHERE identityid={$identity_id}";
            $this->query($sql);
        }
        return array("result"=>$result,"newuser"=>$newUser);
    }
}
