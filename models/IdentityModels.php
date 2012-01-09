<?php

class IdentityModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addIdentity($user_id,$provider,$external_identity,$identityDetail=array())
    {
        $activecode = createToken();

        $name=mysql_real_escape_string($identityDetail["name"]);
        $bio=mysql_real_escape_string($identityDetail["bio"]);
        $avatar_file_name=mysql_real_escape_string($identityDetail["avatar_file_name"]);

        $avatar_content_type=$identityDetail["avatar_content_type"];
        $avatar_file_size=$identityDetail["avatar_file_size"];
        $avatar_updated_at=$identityDetail["avatar_updated_at"];
        $external_username = trim(mysql_real_escape_string($identityDetail["external_username"]));
        
        $time=time();
        $sql="select id from identities where external_identity='$external_identity' limit 1";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0){
            return  intval($row["id"]);
        }

        $external_identity=mysql_real_escape_string($external_identity);

        if($external_username == ""){
            $external_username = $external_identity;
        }

        $sql="insert into identities (provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username) values ('$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username')";
        $result=$this->query($sql);
        $identityid=intval($result["insert_id"]);
        if($identityid>0)
        {
            $sql="select name,bio,avatar_file_name from users where id=$user_id;";
            $userrow=$this->getRow($sql);
            if($userrow["name"]=="")
                $userrow["name"]=$name;
            if($userrow["bio"]=="")
                $userrow["bio"]=$bio;
            if($userrow["avatar_file_name"]=="")
                $userrow["avatar_file_name"]=$avatar_file_name;

            $sql="update users set name='".$userrow["name"]."', bio='".$userrow["bio"]."', avatar_file_name='".$userrow["avatar_file_name"]."' where id=$user_id;";
            $this->query($sql);

            //TOdO: commit as a transaction
            $time=time();
            $sql="insert into user_identity (identityid,userid,created_at,activecode) values ($identityid,$user_id,FROM_UNIXTIME($time),'{$activecode}')";
            $this->query($sql);

            $verifyTokenArray = array(
                "identityid"    =>$identityid,
                "activecode"    =>$activecode
            );
            $verifyToken = packArray($verifyTokenArray);

            $args = array(
                     'identityid'           =>$identityid,
                     'external_identity'    =>$external_identity,
                     'name'                 =>$name,
                     'avatar_file_name'     =>$avatar_file_name,
                     'activecode'           =>$activecode,
                     'token'                =>$verifyToken
            );
            /*
            if($provider=="email")
            {
                $helper=$this->getHelperByName("identity");
                $helper->sentActiveEmail($args);
            }
             */
            //send welcome and active e-mail
            if($provider=="email")
            {
                $helper=$this->getHelperByName("identity");
                $helper->sentWelcomeAndActiveEmail($args);
            }
            return $identityid;
        }
    }

    public function addIdentityWithoutUser($provider,$external_identity,$identityDetail=array())
    {

        $name=mysql_real_escape_string($identityDetail["name"]);
        $bio=mysql_real_escape_string($identityDetail["bio"]);
        $avatar_file_name=mysql_real_escape_string($identityDetail["avatar_file_name"]);
        $avatar_content_type=$identityDetail["avatar_content_type"];
        $avatar_file_size=$identityDetail["avatar_file_size"];
        $avatar_updated_at=$identityDetail["avatar_updated_at"];
        $external_username=mysql_real_escape_string($identityDetail["external_username"]);
        $external_identity=mysql_real_escape_string($external_identity);
        $time=time();
        $sql="select id from identities where external_identity='$external_identity' limit 1";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0)
            return  intval($row["id"]);

        $sql="insert into identities (provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username) values ('$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username')";
        $result=$this->query($sql);
        $identityid=intval($result["insert_id"]);
        return $identityid;
    }

    public function ifIdentityExist($external_identity, $provider="")
    {
        $external_identity = mysql_real_escape_string($external_identity);
        $provider = mysql_real_escape_string($provider);
        $sql = "SELECT id FROM identities WHERE external_identity='{$external_identity}'";

        if($provider != ""){
            $sql = "SELECT id FROM identities WHERE provider='{$provider}' AND external_username='{$external_identity}'";
        }

        $row = $this->getRow($sql);
        $identity_id = intval($row["id"]);
        if ($identity_id > 0) {
            $sql = "SELECT userid, status FROM user_identity WHERE identityid={$identity_id}";
            $result = $this->getRow($sql);
            $uid = intval($result["userid"]);
            $user_status = intval($result["status"]);

            $returnData = array(
                "id"=>$identity_id,
                "status"=>$user_status,
                "user_avatar"=>""
            );

            if($user_status == 3 && $uid > 0) {
                $sql = "SELECT id, encrypted_password, avatar_file_name FROM users WHERE id={$uid}";
                $user_info = $this->getRow($sql);

                if(intval($user_info["id"])>0 && trim($user_info["encrypted_password"])==""){
                    $returnData["status"] = 2;
                }

                $returnData["user_avatar"] = $user_info["avatar_file_name"];
                return $returnData;
            }
            return $returnData;

        } else {
            return false;
        }
    }

    public function setLoginCookie($identity, $userid, $identity_id)
    {
            $time=time();

            $sql="select cookie_logintoken,cookie_loginsequ,encrypted_password,current_sign_in_ip from users where id=$userid";
            $userrow=$this->getRow($sql);
            $encrypted_password=$userrow["encrypted_password"];
            $current_ip=$userrow["current_sign_in_ip"];

            $cookie_logintoken=md5($encrypted_password."3firwkF");
            $cookie_loginsequ=md5($time."glkfFDks.F");

            $ipaddress=getRealIpAddr();

            if( $userrow["cookie_loginsequ"]=="" ||  $userrow["cookie_logintoken"]=="") //first time login, setup cookie
            {

                $sql="update users set current_sign_in_ip='$ipaddress',created_at=FROM_UNIXTIME($time),cookie_loginsequ='$cookie_loginsequ',cookie_logintoken='$cookie_logintoken' where id=$userid;";
                $this->query($sql);
            } else {
                $cookie_logintoken=$userrow["cookie_logintoken"];
                $cookie_loginsequ=$userrow["cookie_loginsequ"];
            }

            setcookie('uid', $userid, time()+31536000, "/", COOKIES_DOMAIN);
            setcookie('id', $identity_id, time()+31536000, "/", COOKIES_DOMAIN);
            setcookie('loginsequ', $cookie_loginsequ, time()+31536000, "/", COOKIES_DOMAIN);
            setcookie('logintoken', $cookie_logintoken, time()+31536000, "/", COOKIES_DOMAIN);
            setcookie('last_identity', $identity, time()+31536000, "/", COOKIES_DOMAIN);//one year.
    }

    public function loginByCookie($source='')
    {
        $uid=intval($_COOKIE['uid']);
        $identity_id=intval($_COOKIE['id']);
        $loginsequ=$_COOKIE['loginsequ'];
        $logintoken=$_COOKIE['logintoken'];
        $identity = $_COOKIE["last_identity"];
        if($uid>0)
        {
            $sql="select current_sign_in_ip,cookie_loginsequ,cookie_logintoken from users where id=$uid";
            $logindata=$this->getRow($sql);

            $ipaddress=getRealIpAddr();
            if($ipaddress!=$logindata["current_sign_in_ip"])
            {
                unset($_SESSION["userid"]);
                unset($_SESSION["identity_id"]);
                unset($_SESSION["identity"]);
                unset($_SESSION["tokenIdentity"]);
                session_destroy();

                unset($_COOKIE["uid"]);
                unset($_COOKIE["id"]);
                unset($_COOKIE["loginsequ"]);
                unset($_COOKIE["logintoken"]);
                setcookie('uid', NULL, -1,"/", COOKIES_DOMAIN);
                setcookie('id', NULL, -1,"/", COOKIES_DOMAIN);
                setcookie('loginsequ', NULL,-1,"/", COOKIES_DOMAIN);
                setcookie('logintoken',NULL,-1,"/", COOKIES_DOMAIN);
                if($source == ""){
                    header( 'Location: /s/login' ) ;
                }
                exit(0);
            }

            if($loginsequ==$logindata["cookie_loginsequ"] && $logintoken==$logindata["cookie_logintoken"])
            { //do login
               $user_id=$this->loginByIdentityId( $identity_id,$uid,$identity ,NULL,NULL,"cookie",false);
               return $user_id;
            } else {
                return 0;
            }

        }
    }
    public function loginByIdentityId($identity_id,$userid=0,$identity="", $userrow=NULL,$identityrow=NULL,$type="password",$setcookie=false)
    {
        if($userid==0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $trow=$this->getRow($sql);
            if(intval($trow["userid"])>0)
                $userid=intval($trow["userid"]);
        }
        if($userrow==NULL) {
            $sql="select name,bio,avatar_file_name from users where id=$userid";
            $userrow=$this->getRow($sql);
        }
        if($identityrow==NULL)
        {
            $sql="select * from identities where id='$identity_id' limit 1";
            $identityrow=$this->getRow($sql);
        }

        if($setcookie==true && $type=="password")
            $this->setLoginCookie($identity, $userid,$identity_id);


        $ipaddress=getRealIpAddr();
        $time=time();
        $sql="update users set current_sign_in_ip='$ipaddress',created_at=FROM_UNIXTIME($time) where id=$userid;";
        $this->query($sql);


        $_SESSION["userid"]=$userid;
        $_SESSION["identity_id"]=$identity_id;
        $identity=array();
        $identity["external_identity"]=$identityrow["external_identity"];
        $idnetity["provider"] = $identityrow["provider"];
        $identity["name"]=$identityrow["name"];
        if(trim($identity["name"]==""))
            $identity["name"]=$userrow["name"];

        if(trim($identity["name"]==""))
            $identity["name"]=$identityrow["external_identity"];

        $identity["bio"]=$identityrow["bio"];
        $identity["avatar_file_name"]=$identityrow["avatar_file_name"];
        if(trim($identity["avatar_file_name"])=="")
            $identity["avatar_file_name"]=$userrow["avatar_file_name"];
        $_SESSION["identity"]=$identity;

        unset($_SESSION["tokenIdentity"]);
        return $userid;
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

    public function login($identityInfo,$password,$setcookie=false, $password_hashed=false, $oauth_login=false)
    {
        //$password = md5($password.$this->salt);
        $sql="select * from identities where external_identity='$identityInfo' limit 1";
        if($oauth_login){
            $provider = $identityInfo["provider"];
            $ex_username = $identityInfo["ex_username"];
            $sql = "SELECT * FROM identities WHERE provider='{$provider}' AND external_username='{$ex_username}'";
        }

        $identityrow=$this->getRow($sql);
        if(intval($identityrow["id"])>0)
        {
            $identity_id=intval($identityrow["id"]);
            $identity = $identityrow["external_identity"];

            $userid=0;
            $sql="select userid from user_identity where identityid=$identity_id";
            $row=$this->getRow($sql);
            if(intval($row["userid"])>0)
            {
                $userid=intval($row["userid"]);

                $sql="select encrypted_password,password_salt,name,avatar_file_name from users where id=$userid";
                $row=$this->getRow($sql);
                if(!$password_hashed){
                    $passwordSalt = $row["password_salt"];
                    if($passwordSalt == $this->salt){
                        $password=md5($password.$this->salt);
                    }else{
                        $password=md5($password.substr($passwordSalt,3,23).EXFE_PASSWORD_SALT);
                    }
                }

                if($row["encrypted_password"]==$password)
                {
                    $this->loginByIdentityId( $identity_id,$userid,$identity,$row,$identityrow,"password",$setcookie);
                    return $userid;
                }

            }
        }
        return 0;
    }

    public function getUserNameByIdentityId($identity_id){
        $sql = "SELECT b.name FROM user_identity a LEFT JOIN users b ON (a.userid=b.id)
                WHERE a.identityid={$identity_id} LIMIT 1";
        $row = $this->getRow($sql);
        if($row){
            return $row["name"];
        }else{
            return "";
        }
    }

    public function getIdentityById($identity_id)
    {
        $sql="select id,external_identity,name,bio,avatar_file_name,external_username from identities where id='$identity_id'";
        $row=$this->getRow($sql);
        return $row;
    }

    public function getIdentity($identity)
    {
        $sql="SELECT a.*,b.* FROM identities a LEFT JOIN user_identity b ON (a.id=b.identityid) WHERE a.external_identity='$identity'";
        $row=$this->getRow($sql);
        return $row;
    }

    public function loginWithXToken($cross_id,$token)
    {
        $sql="select identity_id,tokenexpired from invitations where cross_id=$cross_id and token='$token';";
        $row=$this->getRow($sql);
        $identity_id=intval($row["identity_id"]);
        $tokenexpired=intval($row["tokenexpired"]);

        if($identity_id > 0)
        {

            if($tokenexpired<2)
            {
                $tokenexpired=$tokenexpired+1;
                $sql="update invitations set tokenexpired=$tokenexpired where cross_id=$cross_id and token='$token';";
                $this->query($sql);
            }

            $sql = "SELECT status FROM user_identity WHERE identityid={$identity_id}";
            $row = $this->getRow($sql);
            if($row["status"]!=STATUS_CONNECTED)
            {
                $sql="UPDATE user_identity SET status=3 WHERE identityid={$identity_id}";
                $this->query($sql);
            }

            $sql="select name,avatar_file_name,bio from identities where id=$identity_id limit 1";
            $identityrow=$this->getRow($sql);

            if($identityrow["name"]=="" || $identityrow["avatar_file_name"]=="" || $identityrow["bio"]=="")
            {
                $sql="select userid from user_identity where identityid=$identity_id";
                $result=$this->getRow($sql);
                if(intval($result["userid"])>0)
                {
                    $userid=$result["userid"];
                    $sql="select name,avatar_file_name,bio from users where id=$userid";
                    $user=$this->getRow($sql);
                    if($identityrow["name"]=="")
                        $identityrow["name"]=$user["name"];
                    if($identityrow["avatar_file_name"]=="")
                        $identityrow["avatar_file_name"]=$user["avatar_file_name"];
                    if($identityrow["bio"]=="")
                        $identityrow["bio"]=$user["bio"];
                }
            }


            $tokenSession=array();
            //$tokenSession["userid"]=$userid;
            $tokenSession["identity_id"]=$identity_id;
            $identity=array();
            $identity["external_identity"]=$identityrow["external_identity"];
            $identity["name"]=$identityrow["name"];
            if($identity["name"]=="")
                $identity["name"]=$identityrow["external_identity"];
            $identity["bio"]=$identityrow["bio"];
            $identity["avatar_file_name"]=$identityrow["avatar_file_name"];
            $tokenSession["identity"]=$identity;
            $tokenSession["auth_type"]="mailtoken";
            if($row["tokenexpired"]=="2")
                $tokenSession["token_expired"]="true";
            $_SESSION["tokenIdentity"]=$tokenSession;
            if($_SESSION["identity_id"]!=$_SESSION["tokenIdentity"]["identity_id"])
            {
                unset($_SESSION["userid"]);
                unset($_SESSION["identity_id"]);
                unset($_SESSION["identity"]);

                unset($_COOKIE["uid"]);
                unset($_COOKIE["id"]);
                unset($_COOKIE["loginsequ"]);
                unset($_COOKIE["logintoken"]);

                setcookie('uid', NULL, -1,"/", COOKIES_DOMAIN);
                setcookie('id', NULL, -1,"/", COOKIES_DOMAIN);
                setcookie('loginsequ', NULL,-1,"/", COOKIES_DOMAIN);
                setcookie('logintoken',NULL,-1,"/", COOKIES_DOMAIN);
            }
        }
        return $identity_id;
    }

    public function getIdentitiesIdsByUser($userid)
    {
        $sql="select identityid from user_identity where userid=$userid";
        $rows=$this->getAll($sql);
        $ids=array();
        if($rows)
            foreach ($rows as $row)
            {
                array_push($ids,$row["identityid"]);
            }
        return $ids;
    }
    public function getIdentitiesByUser($userid)
    {
        $sql="select identityid,status from user_identity where userid=$userid";
        $rows=$this->getAll($sql);
        $userIdentityInfo = array();
        foreach($rows as $row)
        {
            if(intval($row["identityid"])>0)
            {

                $identity_id=$row["identityid"];
                $sql="select * from identities where id=$identity_id";
                $identity=$this->getRow($sql);
                $identity["status"]=$row["status"];
                array_push($userIdentityInfo,$identity);
            }
        }
        return $userIdentityInfo;

    }


    public function checkIdentityStatus($identity_id)
    {
        $sql = "SELECT status FROM user_identity WHERE identityid={$identity_id}";
        $result=$this->getRow($sql);
        return intval($result["status"]);
    }

    public function setRelation($identity_id,$status=0)
    {
        if(intval($identity_id)>0 )
        {
            $token = createToken();
            $sql="select userid from user_identity where identityid=$identity_id;";
            $user=$this->getRow($sql);
            if(intval($user["userid"])==0)
            {
                $sql="select name,bio,avatar_file_name from identities where id=$identity_id;";
                $identity=$this->getRow($sql);
                $name=$identity["name"];
                $bio=$identity["bio"];
                $avatar_file_name=$identity["avatar_file_name"];
                $sql="insert into users (name,bio,avatar_file_name) values ('$name','$bio','$avatar_file_name');";
                $result=$this->query($sql);
                if(intval($result["insert_id"])>0)
                {
                    $time=time();
                    $userid=intval($result["insert_id"]);
                    $sql="insert into user_identity (identityid,userid,created_at) values ($identity_id,$userid,$FROM_UNIXTIME($time));";
                    $this->query($sql);

                    //set identity state to verifying, set identity activecode
                    if($status==STATUS_CONNECTED){
                        $sql="update user_identity set status=$status where identityid=$identity_id;";
                    }else{
                        $sql="update user_identity set status=2 where identityid=$identity_id;";
                    }


                    $this->query($sql);
                }
            }

            $sql = "SELECT status FROM user_identity WHERE identityid={$identity_id}";
            $row=$this->getRow($sql);

            if($status==STATUS_CONNECTED) {
                //$sql="update identities set status=$status where id=$identity_id;";
                $sql="update user_identity set status=$status where identityid=$identity_id;";
                $this->query($sql);
            }
            if (intval($row["status"]) == 2) { // if status is verifying, set identity activecode ,send active email ,
                //$sql="update identities set activecode='$token' where  id=$identity_id;";
                $sql="update user_identity set activecode='$token' where identityid=$identity_id;";
                $this->query($sql);
            } else if(intval($row["status"]) == 1) {	//if disconnect, change to verifying, set identity activecode ,send active email
                //$sql="update identities set status='2',activecode='$token' where  id=$identity_id;";

                $sql="update user_identity set status=2, activecode='$token' where identityid=$identity_id;";
                $this->query($sql);
            }

        }
    }

    public function getIdentitiesByIdentityIds($identity_ids)
    {
        if ($identity_ids) {
            $identity_ids = implode(' OR `id` = ', $identity_ids);
            $sql          = "SELECT * FROM `identities` WHERE `id` = {$identity_ids}";
            return $this->getAll($sql);
        } else {
            return array();
        }
    }

    public function delVerifyCode($identity_id, $active_code){
        $activecode = mysql_real_escape_string($activecode);
        $sql = "SELECT identityid FROM user_identity WHERE identityid={$identity_id} AND activecode='{$active_code}'"; 
        $row = $this->getRow($sql);
        if(is_array($row)){
            $sql = "UPDATE user_identity SET activecode='' WHERE identityid={$identity_id} AND activecode='{$active_code}'";
            $this->query($sql);
        }
    }


    //验证
    public function verifyIdentity($identity_id, $active_code){
        $returnData = array(
            "identity"          =>"",
            "display_name"      =>"",
            "avatar"            =>"",
            "password"          =>"",
            "reset_pwd_token"   =>"",
            "status"            =>"ok",
            "need_set_pwd"      =>"no"
        );
        $activecode = mysql_real_escape_string($activecode);
        $sql = "SELECT identityid,userid FROM user_identity WHERE identityid={$identity_id} AND activecode='{$active_code}'";
        $row = $this->getRow($sql);
        $sql = "SELECT external_identity,name,avatar_file_name FROM identities WHERE id={$identity_id}";
        $identityInfo = $this->getRow($sql);
        $returnData["identity"] = $identityInfo["external_identity"];
        $returnData["display_name"] = $identityInfo["name"];
        $returnData["avatar"] = $identityInfo["avatar_file_name"];

        if(is_array($row)){
            $userID = $row["userid"];
            $sql = "SELECT encrypted_password FROM users WHERE id={$userID}";
            $userInfo = $this->getRow($sql);

            //设置用户的Status为3，并且设置ActiveCode为空。
            $sql = "UPDATE user_identity SET status=3, activecode='' WHERE identityid={$identity_id}";
            $this->query($sql);
            
            //如果用户密码为空，则需要设置reset_password_token，同时告诉客户端需要设置密码。
            if(trim($userInfo["encrypted_password"]) == ""){
                $returnData["need_set_pwd"] = "yes";
                $resetPwdToken = createToken();
                $sql = "UPDATE users SET reset_password_token='{$resetPwdToken}' WHERE id={$userID}";
                $this->query($sql);
                $returnData["reset_pwd_token"] = $resetPwdToken;
            }else{
                $returnData["password"] = $userInfo["encrypted_password"];
                $returnData["need_set_pwd"] = "no";
            }
        }else{
            $returnData["status"] = "fail";
        }
        return $returnData;
    }

    public function ifIdentityBelongsUser($external_identity,$user_id)
    {
        $result=$this->ifIdentityExist($external_identity);
        if($result>0 && intval($user_id)>0)
        {
            $identity_id=$result;
            $sql="select identityid from user_identity where identityid=$identity_id and userid=$user_id;";
            $row=$this->getRow($sql);
            if(intval($row["identityid"])==$identity_id)
                return $identity_id;
        }
        return FALSE;
    }

    public function ifIdentityIdBelongsUser($identity_id,$user_id)
    {
        if(intval($identity_id)>0 && intval($user_id)>0)
        {
            $sql="select identityid from user_identity where identityid=$identity_id and userid=$user_id;";
            $row=$this->getRow($sql);
            if(intval($row["identityid"])==$identity_id)
                return $identity_id;
        }
        return FALSE;
    }

    public function getVerifyingCode($identity_id){
        if(intval($identity_id) > 0){
            $sql = "SELECT provider FROM identities WHERE id={$identity_id}";
            $row_p = $this->getRow($sql);
            $sql = "SELECT activecode FROM user_identity WHERE identityid={$identity_id}";
            $row_c = $this->getRow($sql);
            if($row_c["activecode"] != ""){//这个时候要判断一下ActiveCode是否过期
                $activecode = $row_c["activecode"];
                $activeCodeTS = substr($activecode, 32);
                $curTimeStamp = time();
                if(intval($activeCodeTS)+5*24*60*60 < $curTimeStamp){
                    $activecode = createToken();
                    $sql="UPDATE user_identity SET activecode='$activecode' WHERE identityid={$identity_id}";
                    $queryResult = $this->query($sql);
                }

                return array(
                    "provider"=>$row_p["provider"], 
                    "activecode"=>$activecode
                );
            }else{
                $activecode = createToken();
                $sql="UPDATE user_identity SET activecode='$activecode' WHERE identityid={$identity_id}";
                $queryResult = $this->query($sql);
                return array(
                    "provider"=>$result["provider"],
                    "activecode"=>$activecode
                );
            }
        }
    }

    public function buildIndex($userid,$identities)
    {
        //$identities=$this->getIdentitiesByUser($userid);
        //$userid=$_SESSION["userid"];

        if(intval($userid)>0)
        {
            $sql="select name,external_identity from user_relations where userid=$userid;";
            $identities =$this->getAll($sql);

            #global $redis;

            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            mb_internal_encoding("UTF-8");


            foreach($identities as $identitymeta)
            {
                $identity=mb_strtolower($identitymeta["name"]." ".$identitymeta["external_identity"]);
                $identity_array=explode(" ",trim($identity));
                if($identity_array>0)
                {
                    foreach($identity_array as $identity_a)
                    {
                        $identity_part="";
                        for ($i=0;$i<mb_strlen($identity_a);$i++)
                        {
                            $identity_part.=mb_substr($identity_a, $i, 1);
                            $redis->zAdd('u_'.$userid, 0, $identity_part);
                        }
                        $redis->zAdd('u_'.$userid, 0, $identity_part."|".$identitymeta["name"]." ".$identitymeta["external_identity"]."*");

                    }
                }
            }
        }

    }
    public function ifIdentitiesEqualWithIdentity($identities,$identity_id)
    {
        foreach($identities as $identity)
        {
            if(intval($identity["identity_id"])==intval($identity_id))
                return TRUE;
        }
        return FALSE;
    }
}

