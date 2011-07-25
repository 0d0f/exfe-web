<?php
class XActions extends ActionController {

  public function doGather()
  {
    $identity_id=2;
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
    $crossdata=$this->getDataModel("x");
    $result=$crossdata->getCross(base62_to_int($_GET["id"]));
    //print_r($result);
    $this->setVar("cross", $result);
    $this->displayView();
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
