<?php
class XActions extends ActionController {

  public function doGather()
  {
    $identity_id=$_SESSION["identity_id"];
    #$crossid=int_to_base62($crossid);
    #echo "redirect...to cross edit page: /$crossid/edit";
    if($_POST["title"]!="")
    {
	echo "create...";
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
	$helper->addExfeeIdentify($cross_id,$_POST["exfee"]);
	//TODO: redirect to this exfe page
    }
    $this->displayView();
  }
  public function doIndex()
  {
    if(checklogin()===FALSE)
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }
    $Data=$this->getModelByName("X");
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

	    
	    $invitationData=$this->getModelByName("invitation");
	    $invitations=$invitationData->getInvitation_Identities($cross_id);


	    $host_exfee=array();
	    $normal_exfee=array();
	    foreach ($invitations as $invitation)
	    {
		if ($invitation["identity_id"]==$cross["host_id"])
		    array_push($host_exfee,$invitation);
		else
		    array_push($normal_exfee,$invitation);
	    }

	    $cross["host_exfee"]=$host_exfee;
	    $cross["normal_exfee"]=$normal_exfee;
	}
    $this->setVar("cross", $cross);
    $this->displayView();
    }
  }
  //public function doGather()
  //{
  //  $crossdata=$this->getDataModel("x");
  //  $result=$crossdata->getCross(base62_to_int($_GET["id"]));
  //  $this->setVar("cross", $result);
  //  $this->displayView();
  // // echo "do edit:".base62_to_int($_GET["id"]);
  //  
  //}

}
