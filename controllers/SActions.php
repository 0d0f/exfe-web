<?php

class SActions extends ActionController {
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
}

