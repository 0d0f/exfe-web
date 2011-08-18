<?php
class RSVPActions extends ActionController {
  public function checkallow($cross_id,$token)
  {
    $checkhelper=$this->getHelperByName("check");
    $check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
    if($check["allow"]=="false")
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }
    if($check["type"]=="token")
    {
    		$identityData=$this->getModelByName("identity");
	    $identity_id=$identityData->loginWithXToken($cross_id, $token);
            $status=$identityData->checkIdentityStatus($identity_id);
            if($status!=STATUS_CONNECTED)
            {
        	$identityData->setRelation($identity_id,STATUS_CONNECTED);
            }
    }
    else if($check["type"]=="session")
	$identity_id=$_SESSION["identity_id"];
    return $identity_id;
  }
  public function doYES()
  {
    $cross_id=intval($_GET["id"]);
    $token=$_GET["token"];

    $identity_id=$this->checkallow($cross_id,$token);


    if(intval($identity_id)>0)
    {
    $state=INVITATION_YES;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    }
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doNO()
  {

    $cross_id=intval($_GET["id"]);
    $token=$_GET["token"];

    $identity_id=$this->checkallow($cross_id,$token);

    if(intval($identity_id)>0)
    {
    $state=INVITATION_NO;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
   }
    exit(0);
  }
  public function doMaybe()
  {

    $cross_id=intval($_GET["id"]);
    $token=$_GET["token"];

    $identity_id=$this->checkallow($cross_id,$token);

    if(intval($identity_id)>0)
    {
    $state=INVITATION_MAYBE;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    if($token!="")
	header( "Location: /!$cross_id_base62?token=$token" ) ;
    else
	header( "Location: /!$cross_id_base62" ) ;
    }
    exit(0);
  }
}

