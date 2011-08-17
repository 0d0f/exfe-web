<?php
class XActions extends ActionController {

  public function doGather()
  {
   // print_r($_POST);die();
    $identity_id=$_SESSION["identity_id"];
    #$crossid=int_to_base62($crossid);
    #echo "redirect...to cross edit page: /$crossid/edit";
    if($_POST["title"]!="")
    {
	$crossdata=$this->getDataModel("x");
	$placedata=$this->getModelByName("place");

	//TODO: package as a translaction
	if(trim($_POST["place"])!="")
	    $placeid=$placedata->savePlace($_POST["place"]);
	else 
	    $placeid=0;

	$_POST["place_id"]=$placeid;
    	$cross_id=$crossdata->gatherCross($identity_id,$_POST);

	$helper=$this->getHelperByName("exfee");
	//$exfee_list=explode(",", $_POST["exfee_list"]); 
	//foreach ($exfee_list as $exfee)
	//{
	//    if($exfee!="")
	//    {
		$helper->addExfeeIdentify($cross_id,$_POST["exfee_list"]);
		$helper->sendInvitation($cross_id);
	//    }

	//}
	
	//TODO: redirect to this exfe page
	//cross_id
	$cross_id_base62=int_to_base62($cross_id);
	header( 'Location: /!'.$cross_id_base62 ) ;
	exit(0);

    }
    $this->displayView();
  }
  public function doIndex()
  {
    //$identity_id=$_SESSION["identity_id"];
    //if(intval($identity_id<=0))
	//if(checklogin()===FALSE)
    //	{
    $identity_id=0;
    $identityData=$this->getModelByName("identity");
    $cross_id=base62_to_int($_GET["id"]);
    $token=$_GET["token"];
    if(intval($cross_id)>0 && $token!="")
    {
	$identity_id=$identityData->loginWithXToken($cross_id, $token);
	if(intval($identity_id)>0)
	{
	    $status=$identityData->checkIdentityStatus($identity_id);
	    if($status!=STATUS_CONNECTED)
	    {
		$identityData->setRelation($identity_id,STATUS_CONNECTED);
	    }
	}
		//checkIdentityStatus
    }

    if($identity_id==0)
    {
	$identity_id=intval($_SESSION["identity_id"]);
    }
    //TODO: check if current identity in session can view this cross
    if(checkIdentityLogin($identity_id)===FALSE)
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }

    $showlogin="";
    if($identity_id!=$_SESSION["identity_id"])
    {
	$identityData=$this->getModelByName("user");
	$user=$identityData->getUserByIdentityId($identity_id);
	if(trim($user["encrypted_password"])=="")
	    $showlogin= "setpassword";
	//if user password="" then show set password box
	//else show login
	else
	    $showlogin= "login";
    }
    
    $this->setVar("showlogin", $showlogin);
	
    $Data=$this->getModelByName("x");
    $cross=$Data->getCross(base62_to_int($_GET["id"]));
    if($cross)
    {
	$place_id=$cross["place_id"];
	$cross_id=$cross["id"];
	if(intval($place_id)>0)
	{
	    $placeData=$this->getModelByName("place");
	    $place=$placeData->getPlace($place_id);
	    $cross["place"]=$place;
	}
    $invitationData=$this->getModelByName("invitation");
    $invitations=$invitationData->getInvitation_Identities($cross_id);
    
    if(intval($_SESSION["userid"])>0)
    {    
    	$userData = $this->getModelByName("user");
    	$user=$userData->getUser($_SESSION["userid"]);
	$this->setVar("user", $user);

	$identityData=$this->getModelByName("identity");
	$myidentities=$identityData->getIdentitiesIdsByUser($_SESSION["userid"]);
    }
    else
    {
	$myidentities=array($identity_id);
    }

    if($_SESSION["tokenIdentity"]["identity"]!="")
    {
	$this->setVar("myidentity", $_SESSION["tokenIdentity"]["identity"]);
    }
    else if($_SESSION["identity"]!="")
    {
	$this->setVar("myidentity", $_SESSION["identity"]);
    }
    $host_exfee=array();
    $normal_exfee=array();

    if($invitations)
	foreach ($invitations as $invitation)
    	{
	    if(in_array($invitation["identity_id"],$myidentities)==true)
	    {
		$this->setVar("interested","yes");	
	    }
	    //$invitation["identity_id"]
	   // $invitation["state"];
    	    if ($invitation["identity_id"]==$cross["host_id"])
    	        array_push($host_exfee,$invitation);
    	    else
    	        array_push($normal_exfee,$invitation);
    	}
    
    $cross["host_exfee"]=$host_exfee;
    $cross["normal_exfee"]=$normal_exfee;
    
    $ConversionData=$this->getModelByName("conversation");
    $conversationPosts=$ConversionData->getConversion(base62_to_int($_GET["id"]),'cross');
    $cross["conversation"]=$conversationPosts;

    $this->setVar("cross", $cross);
    $this->displayView();
    }
  }
}
