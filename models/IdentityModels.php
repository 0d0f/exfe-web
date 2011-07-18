<?php

class IdentityModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addIdentity($user_id,$provider,$external_identity,$identityDetail=array())
    {

	$name=$identityDetail["name"];
    	$bio=$identityDetail["bio"];
    	$avatar_file_name=$identityDetail["avatar_file_name"];
    	$avatar_content_type=$identityDetail["avatar_content_type"];
    	$avatar_file_size=$identityDetail["avatar_file_size"];
    	$avatar_updated_at=$identityDetail["avatar_updated_at"];
    	$external_username=$identityDetail["external_username"];
	$time=time();
	$sql="select id from identities where external_identity='$external_identity' limit 1";
    	$row=$this->getRow($sql);
	if(intval($row["id"])>0)
	    return  intval($row["id"]);

	$sql="insert into identities (user_id,provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username) values ($user_id,'$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username')";
    	$result=$this->query($sql);
	if(intval($result["insert_id"])>0)
	    return intval($result["insert_id"]);
    }
    public function ifIdentityExist($external_identity)
    {
	$sql="select user_id from  identities where external_identity='$external_identity'";
	$row=$this->getRow($sql);
	if (intval($row["user_id"])>0)
	    return  TRUE;
	else 
	    return FALSE;
    }

    public function login($identity,$password)
    {
	$password=md5($password.$this->salt);
	$sql="select * from identities where external_identity='$identity' limit 1";
	#update last_sign_in_at,last_sign_in_ip...
    	$row=$this->getRow($sql);
	if($row["user_id"]>0)
	{
	   $userid=$row["user_id"];
	   $sql="select encrypted_password from users where id=$userid"; 
	   $row=$this->getRow($sql);
	   if($row["encrypted_password"]==$password)
	   {
		$_SESSION["userid"]=$userid;
		return $userid;
	   }
	}
	return 0;
    }
}

