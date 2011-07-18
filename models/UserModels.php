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

    #public function addUser($email,$password)
    #{
    #    $password=md5($password.$this->salt);
    #    $time=time();
    #    $sql="insert into users (email,encrypted_password,created_at) values('$email','$password',FROM_UNIXTIME($time));";
    #	$result=$this->query($sql);
    #    if(intval($result["insert_id"])>0)
    #        return intval($result["insert_id"]);
    #}
    public function login($email,$password)
    {
	$password=md5($password.$this->salt);
	$sql="select * from users where email='$email' and encrypted_password='$password'";
	#update last_sign_in_at,last_sign_in_ip...
    	return $this->getRow($sql);
    }
}
