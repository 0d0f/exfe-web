<?php

class ConversationActions extends ActionController {

  public function doAdd()
  {
    if(checklogin()===FALSE)
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }
    $postData=$this->getModelByName("conversation");
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    $postData->addConversion($cross_id,"cross",$identity_id,"",$_POST["comment"]);
    
    $cross_id=intval($_GET["id"]);
    $cross_id_base62=int_to_base62($cross_id);
    header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
  public function doSave() //for ajax api
  {
    
    $responobj["meta"]["code"]=200;
    $comment=$_POST["comment"];
    $cross_id=$_POST["cross_id"];
    $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
    if(intval($identity_id)==0)
	$identity_id=$_SESSION["identity_id"];
    if(trim($comment)!="" && intval($identity_id)>0 )
    {
	$postData=$this->getModelByName("conversation");
	$r=$postData->addConversion($cross_id,"cross",$identity_id,"",$_POST["comment"]);
	if($r===false)
	{
	   $responobj["response"]["success"]="false";
	}
	else
	{
	    $identityData=$this->getModelByName("identity");
	    $identity=$identityData->getIdentityById($identity_id);

	    $userData=$this->getModelByName("user");
	    $user=$userData->getUserProfileByIdentityId($identity_id);

	   $responobj["response"]["comment"]=$comment;
	   $responobj["response"]["created_at"]=RelativeTime(time());
	   $responobj["response"]["cross_id"]=$cross_id;
	   if($identity["name"]=="")
	    $identity["name"]=$user["name"];
	   if($identity["bio"]=="")
	    $identity["bio"]=$user["bio"];
	   if($identity["avatar_file_name"]=="")
	    $identity["avatar_file_name"]=$user["avatar_file_name"];

	   $responobj["response"]["identity"]=$identity;
	   $responobj["response"]["success"]="true";
	}
    }
    else
    {
	   $responobj["response"]["success"]="false";
    }
    echo json_encode($responobj);
    exit();
  }
   
}
