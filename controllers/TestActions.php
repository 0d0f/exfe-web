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

}
