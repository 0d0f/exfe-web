<?php
class RSVPActions extends ActionController {
  public function doYES()
  {
    if(checklogin()===FALSE)
    {
        header( 'Location: /s/login' ) ;
        exit(0);
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    $state=INVITATION_YES;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doNO()
  {
    if(checklogin()===FALSE)
    {
        header( 'Location: /s/login' ) ;
        exit(0);
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    $state=INVITATION_NO;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doMaybe()
  {
    if(checklogin()===FALSE)
    {
        header( 'Location: /s/login' ) ;
        exit(0);
    }
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    $state=INVITATION_MAYBE;
    $invitationData=$this->getModelByName("Invitation");
    $invitationData->rsvp($cross_id,$identity_id,$state);
    $cross_id_base62=int_to_base62($cross_id);
    header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
}

