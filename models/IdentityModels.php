<?php

class IdentityModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addIdentity($user_id,$provider,$external_identity,$identityDetail=array())
    {
        $activecode=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));

        $name=mysql_real_escape_string($identityDetail["name"]);
        $bio=mysql_real_escape_string($identityDetail["bio"]);
        $avatar_file_name=mysql_real_escape_string($identityDetail["avatar_file_name"]);

        $avatar_content_type=$identityDetail["avatar_content_type"];
        $avatar_file_size=$identityDetail["avatar_file_size"];
        $avatar_updated_at=$identityDetail["avatar_updated_at"];
        $external_username=mysql_real_escape_string($identityDetail["external_username"]);
        $time=time();
        $sql="select id from identities where external_identity='$external_identity' limit 1";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0)
            return  intval($row["id"]);

        $external_identity=mysql_real_escape_string($external_identity);
        $sql="insert into identities (provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username,activecode) values ('$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username','$activecode')";
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
            $sql="insert into user_identity (identityid,userid,created_at) values ($identityid,$user_id,FROM_UNIXTIME($time))";
            $this->query($sql);

            $args = array(
                     'identityid' => $identityid,
                     'external_identity' => $external_identity,
                     'name' => $name,
                     'avatar_file_name' => $avatar_file_name,
                     'activecode' => $activecode
             );
            if($provider=="email")
            {
                $helper=$this->getHelperByName("identity");
                $helper->sentActiveEmail($args);
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


    public function ifIdentityExist($external_identity)
    {
        $external_identity=mysql_real_escape_string($external_identity);
        $sql="select id,status from  identities where external_identity='$external_identity'";
        $row=$this->getRow($sql);
        if (intval($row["id"])>0)
        {
            $identity_id=intval($row["id"]);
            if(intval($row["status"])==3)
            {
                $sql="select userid from user_identity where identityid=$identity_id";
                $userIdRow=$this->getRow($sql);
                if(intval($userIdRow["userid"])>0)
                {
                    $uid=intval($userIdRow["userid"]);
                    $sql = "select id,encrypted_password from users WHERE id=$uid;";
                    $userrow = $this->getRow($sql);

                    $newUser = false;
                    if(intval($userrow["id"])>0 && trim($userrow["encrypted_password"])=="")
                        return  array("id"=>$identity_id,"status"=>2);
                }
            }

            return  array("id"=>intval($row["id"]),"status"=>intval($row["status"]));
        }
        else
            return FALSE;
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

            setcookie('uid', $userid, time()+86400, "/",".exfe.com");
            setcookie('id', $identity_id, time()+86400, "/",".exfe.com");
            setcookie('loginsequ', $cookie_loginsequ, time()+86400, "/",".exfe.com");
            setcookie('logintoken', $cookie_logintoken, time()+86400, "/",".exfe.com");
            setcookie('last_identity', $identity, time()+31536000, "/",".exfe.com");//one year.
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
                setcookie('uid', NULL, -1,"/",".exfe.com");
                setcookie('id', NULL, -1,"/",".exfe.com");
                setcookie('loginsequ', NULL,-1,"/",".exfe.com");
                setcookie('logintoken',NULL,-1,"/",".exfe.com");
                if($source == ""){
                    header( 'Location: /s/login' ) ;
                }
                exit(0);
            }

            if($loginsequ==$logindata["cookie_loginsequ"] && $logintoken==$logindata["cookie_logintoken"])
            {
               $user_id=$this->loginByIdentityId( $identity_id,$uid,$identity ,NULL,NULL,"cookie",false);
    //do login
               return $user_id;
            }
            else
            {
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
    public function login($identity,$password,$setcookie=false)
    {
        $password=md5($password.$this->salt);
        $sql="select * from identities where external_identity='$identity' limit 1";
#update last_sign_in_at,last_sign_in_ip...
        $identityrow=$this->getRow($sql);
        if(intval($identityrow["id"])>0)
        {
            $identity_id=intval($identityrow["id"]);
            $userid=0;
            $sql="select userid from user_identity where identityid=$identity_id";
            $row=$this->getRow($sql);
            if(intval($row["userid"])>0)
            {
                $userid=intval($row["userid"]);
                $sql="select encrypted_password,name,avatar_file_name from users where id=$userid";
                $row=$this->getRow($sql);
                if($row["encrypted_password"]==$password)
                {
                    $this->loginByIdentityId( $identity_id,$userid,$identity,$row,$identityrow,"password",$setcookie);

                    //$_SESSION["userid"]=$userid;
                    //$_SESSION["identity_id"]=$identity_id;
                    //$identity=array();
                    //$identity["external_identity"]=$identityrow["external_identity"];
                    //$identity["name"]=$identityrow["name"];
                    //if(trim($identity["name"]==""))
                    //   $identity["name"]=$row["name"];
                    //if(trim($identity["name"]==""))
                    //   $identity["name"]=$identityrow["external_identity"];

                    //$identity["bio"]=$identityrow["bio"];
                    //$identity["avatar_file_name"]=$identityrow["avatar_file_name"];
                    //if(trim($identity["avatar_file_name"])=="")
                    //   $identity["avatar_file_name"]=$row["avatar_file_name"];
                    //$_SESSION["identity"]=$identity;
                    //unset($_SESSION["tokenIdentity"]);
                    return $userid;
                }

            }
        }
        return 0;
    }
    public function getIdentityById($identity_id)
    {
        $sql="select id,external_identity,name,bio,avatar_file_name from identities where id='$identity_id'";
        $row=$this->getRow($sql);
        if($row)
            return $row;
    }
    public function getIdentity($identity)
    {
        $sql="select id,external_identity,name,bio,avatar_file_name from identities where external_identity='$identity'";
        $row=$this->getRow($sql);
        if($row)
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

            $sql="select name,status,avatar_file_name,bio from identities where id=$identity_id limit 1";
            $identityrow=$this->getRow($sql);
            if($identityrow["status"]!=STATUS_CONNECTED)
            {
                $sql="update identities set status=3 where id=$identity_id;";
                $this->query($sql);
            }

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

                setcookie('uid', NULL, -1,"/",".exfe.com");
                setcookie('id', NULL, -1,"/",".exfe.com");
                setcookie('loginsequ', NULL,-1,"/",".exfe.com");
                setcookie('logintoken',NULL,-1,"/",".exfe.com");
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
        $sql="select identityid from user_identity where userid=$userid";
        $rows=$this->getAll($sql);
        $identities=array();
        foreach($rows as $row)
        {
            if(intval($row["identityid"])>0)
            {

                $identity_id=$row["identityid"];
                $sql="select * from identities where id=$identity_id";
                $identity=$this->getRow($sql);
                array_push($identities,$identity);
                //$identity=;
            }
        }
        return $identities;

    }

    public function checkIdentityStatus($identity_id)
    {
        $sql="select status from identities where id=$identity_id;";
        $result=$this->getRow($sql);
        return intval($result["status"]);
    }

    public function setRelation($identity_id,$status=0)
    {
        if(intval($identity_id)>0 )
        {
            $token=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
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


                    if($status==STATUS_CONNECTED)
                        $sql="update identities set status=$status where id=$identity_id;";
                    else
                        $sql="update identities set status=2 where id=$identity_id;";

                    $this->query($sql);
                }
            }

            $sql="select status from identities where id=$identity_id;";
            $row=$this->getRow($sql);

            if($status==STATUS_CONNECTED) {
                $sql="update identities set status=$status where id=$identity_id;";
                $this->query($sql);
            }
            if (intval($row["status"]) == 2) { // if status is verifying, set identity activecode ,send active email ,
                $sql="update identities set activecode='$token' where  id=$identity_id;";
                $this->query($sql);
            } else if(intval($row["status"]) == 1) {	//if disconnect, change to verifying, set identity activecode ,send active email
                $sql="update identities set status='2',activecode='$token' where  id=$identity_id;";
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
    public function activeIdentity($identity_id,$activecode)
    {
        $activecode=mysql_real_escape_string($activecode);
        $sql="select id,status,external_identity from identities where id=$identity_id and activecode='$activecode';"; 
        $row=$this->getRow($sql);
        $external_identity=$row["external_identity"];
        if(intval($row["id"])==$identity_id && intval($row["id"])>0)
        {
            $sql="update identities set status=3,activecode='' where id=$identity_id;";
            $this->query($sql);
            return array("result"=>"verified","external_identity"=>$external_identity);
        }
        $sql="select external_identity from identities where id=$identity_id;"; 
        $row=$this->getRow($sql);
        return array("result"=>"","external_identity"=>$row["external_identity"]);
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
    public function reActiveIdentity($identity_id)
    {
        if(intval($identity_id)>0)
        {
            $activecode=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
            $sql="update identities set activecode='$activecode' where id=$identity_id";
            $r=$this->query($sql);
            if(intval($r)>0)
            {
                $sql="select id,provider,external_identity,name,avatar_file_name,activecode from identities where id=$identity_id;";
                $r=$this->getRow($sql);
                return $r;
                //success
            }
        }
        return FALSE;
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
}

