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
        $invitationdata=$this->getModelByName("invitation");
        $invitations=$invitationdata->getInvitation_Identities(3);

        $crossData=$this->getModelByName("X");
        $cross=$crossData->getCross(3);
        $place_id=$cross["place_id"];
        if(intval($place_id)>0)
        {
            $placeData=$this->getModelByName("place");
            $place=$placeData->getPlace($place_id);
            $cross["place"]=$place;
        }
        print_r($cross);
        $invitation=$invitations[0];
        $args = array(
                 'title' => $cross["title"],
                 'description' => $cross["description"],
                 'begin_at' => $cross["begin_at"],
                 'place_line1' => $cross["place"]["line1"],
                 'place_line2' => $cross["place"]["line2"],
                 'cross_id_base62' => int_to_base62($cross_id),
                 'invitation_id' => $invitation["invitation_id"],
                 'token' => $invitation["token"],
                 'identity_id' => $invitation["identity_id"],
                 'provider' => $invitation["provider"],
                 'external_identity' => $invitation["external_identity"],
                 'name' => $invitation["name"],
                 'avatar_file_name' => $invitation["avatar_file_name"]
         );

        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend('127.0.0.1:6379');


        print $str;

    }

}
