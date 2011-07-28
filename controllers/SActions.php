<?php

class SActions extends ActionController {
  public function doAdd()
  {
    $identity= $_GET["identity"];
    $provider= $_GET["provider"];
    $password = $_GET["password"];


    #package as a  transaction
    if(intval($_SESSION["userid"])>0)
    {
	$userid=$_SESSION["userid"];
    }
    else
    {
	$Data = $this->getModelByName("user");
	$userid = $Data->addUser($password);
    }
    $identityData = $this->getModelByName("identity");
    $identityData->addIdentity($userid,$provider,$identity);
  }

  public function doIfIdentityExist()
  {

//TODO: private API ,must check session
    $identity=$_GET["identity"];
    $identityData = $this->getModelByName("identity");
    $exist=$identityData->ifIdentityExist($identity);

    $responobj["meta"]["code"]=200;
    //$responobj["meta"]["errType"]="Bad Request";
    //$responobj["meta"]["errorDetail"]="invalid_auth";

    if($exist!==FALSE)
	$responobj["response"]["identity_exist"]="true";
    else
	$responobj["response"]["identity_exist"]="false";
	echo json_encode($responobj);
	exit();
  }
  public function doLogin()
  {
    $identity=$_POST["identity"];
    $password=$_POST["password"];
    $repassword=$_POST["retypepassword"];
    $displayname=$_POST["displayname"];
    $autosignin=$_POST["auto_signin"];
    


    $isNewIdentity=FALSE;

    if(isset($identity) && isset($password)  && isset($repassword) && isset($displayname) )
    {
	$Data = $this->getModelByName("user");
	$userid = $Data->AddUser($password);
	$identityData = $this->getModelByName("identity");
	$provider= $_POST["provider"];
	if($provider=="")
	    $provider="email";
	$identityData->addIdentity($userid,$provider,$identity);
	//TODO: check return value
	$isNewIdentity=TRUE;
    }


    if(isset($identity) && isset($password))
    {
	$Data=$this->getModelByName("identity");
    	$userid=$Data->login($identity,$password);
	if(intval($userid)>0)
	{
	    //$_SESSION["userid"]=$userid;
	    if($isNewIdentity===TRUE)
		$this->setVar("isNewIdentity", TRUE);

	    if(intval($autosignin)>0)
	    {
		//TODO: set cookie
		//set cookie
	    }
	    $this->displayView();
	}
	else
	{
		$this->displayView();
	}
    }
    else
    {
	$this->displayView();
    }
  }
}

