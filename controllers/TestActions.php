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
        $r=$external_identity=mysql_real_escape_string("123");;
        var_dump($r);
    }
    public function doTest()
    {
        $helper=$this->getHelperByName("exfee");
        $result=$helper->sendInvitation(8);
    }

}
