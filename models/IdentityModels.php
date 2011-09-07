<?php

class IdentityModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addIdentity($user_id,$provider,$external_identity,$identityDetail=array())
    {

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
        $sql="insert into identities (provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username) values ('$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username')";
        $result=$this->query($sql);
        $identityid=intval($result["insert_id"]);
        if($identityid>0)
        {
            //TOdO: commit as a transaction
            $time=time();
            $sql="insert into user_identity (identityid,userid,created_at) values ($identityid,$user_id,FROM_UNIXTIME($time))";
            $this->query($sql);
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
        $sql="select id from  identities where external_identity='$external_identity'";
        $row=$this->getRow($sql);
        if (intval($row["id"])>0)
            return  intval($row["id"]);
        else 
            return FALSE;
    }

    public function loginByIdentityId($identity_id,$userid=0,$userrow=NULL,$identityrow=NULL,$type="password")
    {
        if($userid==0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $trow=$this->getRow($sql);
            if(intval($trow["userid"])>0)
                $userid=intval($trow["userid"]);
        }
        if($userrow==NULL)
        {
            $sql="select name,bio,avatar_file_name,current_sign_in_ip,cookie_logintoken,cookie_loginsequ,encrypted_password from users where id=$userid"; 
            $userrow=$this->getRow($sql);
        }
        if($identityrow==NULL)
        {
            $sql="select * from identities where external_identity='$identity' limit 1";
            $identityrow=$this->getRow($sql);
        }

        //write some login info
        //if new ip 
            echo $type;
            die();
        if($type=="password")
        {
            $time=time();
            $ipaddress=getRealIpAddr();
            $cookie_logintoken=$userrow["cookie_logintoken"];
            $cookie_loginsequ=$userrow["cookie_loginsequ"];
            $encrypted_password=$userrow["encrypted_password"];
            $encrypted_password_salt=md5($encrypted_password."3firwkF");
            if($cookie_logintoken!=$encrypted_password_salt)
            {
                $cookie_logintoken=$encrypted_password_salt;
                //update logintoken and sqeu
            }
                
            $sql="update users set current_sign_in_ip='$ipaddress',created_at=FROM_UNIXTIME($time)  where id=$userid;";
            $this->query($sql);
        }
        else if($type="cookie")
        {

        }
        //set cookie

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
    public function login($identity,$password)
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
                    $this->loginByIdentityId($identity_id,$userid,$row,$identityrow);
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
        if($identity_id > 0)
        {

            $sql="update invitations set tokenexpired=1 where cross_id=$cross_id and token='$token';";	
            $this->query($sql);

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
            if($row["tokenexpired"]=="1")
                $tokenSession["token_expired"]="true";
            $_SESSION["tokenIdentity"]=$tokenSession;
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
            if(intval($row["status"])==2)// if status is verifying, set identity activecode ,send active email ,
                {
                    $sql="update identities set activecode='$token' where  id=$identity_id;";
                    $this->query($sql);
                }
            else if(intval($row["status"])==1)	//if disconnect, change to verifying, set identity activecode ,send active email
            {
                $sql="update identities set status='2',activecode='$token' where  id=$identity_id;";
                $this->query($sql);
            }
        }


    }

}

