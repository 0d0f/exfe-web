<?php

class TestActions extends ActionController {
  public function doAdd()
  {
    $email=$_GET["email"];
    $password=$_GET["password"];
    $Data=$this->getModelByName("user");
    $Data->AddUser($email,$password);
  }

  public function doLogin()
  {
    $email=$_GET["email"];
    $password=$_GET["password"];
    $Data=$this->getModelByName("user");
    $user=$Data->login($email,$password);

    print_r($user);

  }
  public function doIndex()
  {
	$Data=$this->getModel();
	$events=$Data->getEvents();
//	print_r($events);

	$this->setVar("notice", $events[0]["title"]);
	$this->displayView();
	echo "index";
  }
  public function doTest()
  {
    $Data=$this->getModelByName("X");
    $cross=$Data->getCross(2);
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
	    $cross["invitation"]=$invitations;

	}
    }
    print_r($cross);
#    $Data=$this->getModelByName("place");
#    $place="line1\r\nline2\r\nline3\rline4\nline5";
#    $places=$Data->savePlace($place);
#    print_r($places);
#
#    $Data=$this->getModelByName("Invitation");
#    $places=$Data->addInvitation(1,2,4);
#
#    print_r($places);
#     $exfee="virushuo@gmail.com,gokeeper@gmail.con,hengdm@gmail.com";
#     $helper=$this->getHelperByName("exfee");
#     $helper->addExfeeIdentify(1,$exfee);
  }

}
