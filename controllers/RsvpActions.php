<?php
class RSVPActions extends ActionController {
  public function doYES()
  {
    if(checklogin()===FALSE)
    {
	$identityData=$this->getModelByName("identity");
	$cross_id=$_GET["id"];
	$token=$_GET["token"];
	if(intval($cross_id)>0 && $token!="")
	{
	    $identity_id=$identityData->loginWithXToken($cross_id, $token);
	}

	if(checkIdentityLogin($identity_id)===FALSE)
	{
	    header( 'Location: /s/login' ) ;
	    exit(0);
	}
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    if(intval($identity_id)==0)
	$identity_id=$_SESSION["tokenIdentity"]["identity_id"];

    $state=INVITATION_YES;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doNO()
  {
    if(checklogin()===FALSE)
    {
	$identityData=$this->getModelByName("identity");
	$cross_id=$_GET["id"];
	$token=$_GET["token"];
	if(intval($cross_id)>0 && $token!="")
	{
	    $identity_id=$identityData->loginWithXToken($cross_id, $token);
	}

	if(checkIdentityLogin($identity_id)===FALSE)
	{
	    header( 'Location: /s/login' ) ;
	    exit(0);
	}
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    if(intval($identity_id)==0)
	$identity_id=$_SESSION["tokenIdentity"]["identity_id"];
    $state=INVITATION_NO;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doMaybe()
  {
    if(checklogin()===FALSE)
    {
	$identityData=$this->getModelByName("identity");
	$cross_id=$_GET["id"];
	$token=$_GET["token"];
	if(intval($cross_id)>0 && $token!="")
	{
	    $identity_id=$identityData->loginWithXToken($cross_id, $token);
	}

	if(checkIdentityLogin($identity_id)===FALSE)
	{
	    header( 'Location: /s/login' ) ;
	    exit(0);
	}
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    if(intval($identity_id)==0)
	$identity_id=$_SESSION["tokenIdentity"]["identity_id"];
    $state=INVITATION_MAYBE;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
}

