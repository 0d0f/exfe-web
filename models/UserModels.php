<?php

class UserModels extends DataModel{

    private $salt="_4f9g18t9VEdi2if";

    public function addUser($email,$password)
    {
	$password=md5($password.$this->salt);
	$time=time();
	$sql="insert into users (email,encrypted_password,created_at) values('$email','$password',FROM_UNIXTIME($time));";
    	$this->query($sql);
    }
    public function login($email,$password)
    {
	$password=md5($password.$this->salt);
	$sql="select * from users where email='$email' and encrypted_password='$password'";
	#update last_sign_in_at,last_sign_in_ip...
    	return $this->getRow($sql);
    }
}
