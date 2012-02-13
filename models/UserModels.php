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

    //保存用户头像
    public function saveUserAvatar($avatar,$userid) {
        $sql="UPDATE users SET avatar_file_name='$avatar' WHERE id={$userid}";
        $this->query($sql);

        //如果Identity的头像为空，则更新Identity的头像。
        $sql = "SELECT identityid FROM user_identity WHERE userid={$userid}";
        $result = $this->getAll($sql);
        if(count($result) != 0){
            foreach($result as $v){
                $sql = "SELECT provider, external_identity, avatar_file_name FROM identities WHERE id=".$v["identityid"];
                $re = $this->getRow($sql);
                $avatar_file_name = $re["avatar_file_name"];
                $pattern = "/(http[s]?:\/\/www\.gravatar\.com)/is";
                if(preg_match($pattern, $avatar_file_name) && $re["provider"] == "email"){
                    $gravatar_file = 'http://www.gravatar.com/avatar/';
                    $gravatar_file .= md5(strtolower(trim($re["external_identity"])));
                    $gravatar_file .= "?d=".urlencode(getUserAvatar($avatar));
                    $sql = "UPDATE identities SET avatar_file_name='{$gravatar_file}' WHERE id=".$v["identityid"];
                    $this->query($sql);
                }
            }
        }
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
    public function regDeviceToken($devicetoken,$devicename="",$provider,$uid)
    {
        $sql="SELECT *,u.userid FROM `identities` i, user_identity u WHERE external_identity='$devicetoken' and provider='$provider' and i.id=u.identityid;";
        $rows=$this->getAll($sql);
        $identity_id=0;
        foreach($rows as $row)
        {
            $t_uid=$row["userid"];
            $t_identity_id=$row["id"];
            if(intval($row["userid"])!=$uid)// bind with other users, set status =1 to disconnect
            {
                    $sql="update user_identity set status=1 where userid=$t_uid and identityid=$t_identity_id";
                    $this->query($sql);
            }
            else if(intval($row["userid"])==intval($uid)) // this user
            {
                    $sql="update user_identity set status=3 where userid=$t_uid and identityid=$t_identity_id";
                    $this->query($sql);
                    $identity_id=$t_identity_id;
            }
        }

        if(intval($identity_id)==0)
        {
            $time=time();
            $sql="insert into identities (provider,external_identity,external_username,created_at) values('iOSAPN','$devicetoken','$devicename',FROM_UNIXTIME($time));";
            $result=$this->query($sql);
            if(intval($result["insert_id"])>0)
                $identity_id=$result["insert_id"];
            $sql="insert into user_identity  (identityid,userid,created_at,status) values ($identity_id,$uid,FROM_UNIXTIME($time),3);";
            $this->query($sql);
        }

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
        $sql="SELECT b.userid AS uid, a.name AS name FROM identities a,user_identity b WHERE a.external_identity='$external_identity' AND a.id=b.identityid";
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

    public function doSetOAuthAccountPassword($userPassword, $userDisplayName, $userID){
        $passwordSalt = md5(createToken());
        $passWord = md5($userPassword.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
        $userName = mysql_real_escape_string($userDisplayName);

        $sql = "UPDATE users SET encrypted_password='{$passWord}', password_salt='{$passwordSalt}', name='{$userName}', updated_at='FROM_UNIXTIME({$ts})',reset_password_token=NULL WHERE id={$userID}";
        $result = $this->query($sql);
        if($result){
            return true;
        }
        return false;
    }

    //check user password
    public function checkUserPassword($userid, $password){
        //$password = md5($password.$this->salt);
        $sql="SELECT encrypted_password, password_salt FROM users WHERE id={$userid} LIMIT 1";
        $row=$this->getRow($sql);
        $passwordSalt = $row["password_salt"];
        if($passwordSalt == $this->salt){
            $password=md5($password.$this->salt);
        }else{
            $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
        }


        if($row["encrypted_password"] == $password){
            return true;
        }
        return false;
    }
    //update user password
    public function updateUserPassword($userid, $password){
        //$password=md5($password.$this->salt);
        $passwordSalt = md5(createToken());
        $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
        $sql="UPDATE users SET encrypted_password='{$password}', password_salt='{$passwordSalt}' WHERE id={$userid}";
        $this->query($sql);
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

        if($result){
            $external_identity=mysql_real_escape_string($external_identity);
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
        }
        return array("result"=>$result,"newuser"=>$newUser);
    }
}
