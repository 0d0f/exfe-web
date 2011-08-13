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

    public function login($identity,$password)
    {
	$password=md5($password.$this->salt);
	$sql="select * from identities where external_identity='$identity' limit 1";
	#update last_sign_in_at,last_sign_in_ip...
    	$identityrow=$this->getRow($sql);
	if(intval($identityrow["id"])>0)
	{
	   $identityid=intval($identityrow["id"]);
	   $sql="select userid from user_identity where identityid=$identityid";
	   $row=$this->getRow($sql);
    
	   if(intval($row["userid"])>0)
	   {	
		$userid=intval($row["userid"]);
		$sql="select encrypted_password from users where id=$userid"; 
	    	$row=$this->getRow($sql);
	    	if($row["encrypted_password"]==$password)
	    	{
	    	     $_SESSION["userid"]=$userid;
	    	     $_SESSION["identity_id"]=$identityid;
		     $identity=array();
		     $identity["external_identity"]=$identityrow["external_identity"];
		     $identity["name"]=$identityrow["name"];
		     if($identity["name"]=="")
			$identity["name"]=$identityrow["external_identity"];
		     $identity["bio"]=$identityrow["bio"];
		     $identity["avatar_file_name"]=$identityrow["avatar_file_name"];
		     $_SESSION["identity"]=$identity;
		     unset($_SESSION["tokenIdentity"]);
	    	     return $userid;
	    	}
	   }
	}
	return 0;
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
	$sql="select identity_id from invitations where cross_id=$cross_id and token='$token';";	
	$row=$this->getRow($sql);
	$identity_id=intval($row["identity_id"]);
	if($identity_id > 0)
	{

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
		     $_SESSION["tokenIdentity"]=$tokenSession;
	}
	return $identity_id;
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


}

