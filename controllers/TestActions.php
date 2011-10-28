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

    public function getMail($mail)
    {
        $title=$mail["title"];
        $from_name=$mail["from_name"];
        $action=$mail["action"];
        $content=$mail["content"];
        $link=$mail["link"];
        $command=$mail["command"];
        $templatename="conversation";
        $template=file_get_contents("./".$templatename."_template");

        print $template;


        print_r($mail);

    }

        function escape($str)
        {
                $search=array("\\","\0","\n","\r","\x1a","'",'"');
                $replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
                return str_replace($search,$replace,$str);
        }
    function reverse_escape($str)
    {
      $search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
      $replace=array("\\","\0","\n","\r","\x1a","'",'"');
      return str_replace($search,$replace,$str);
    }
    public function doTest()
    {
        $content=cleanText("dadfasdf http://www.dianping.com/shop/2588782 www.google.com ");
        print_r($content);
        #$a="abcd\r\ne'rf";
        #$b=nl2br($a);
        #$b=$this->escape($a) ;
        #$a=str_replace('\r\n', "\n", $a);
        #$b=mysql_real_escape_string($a);
        #print $this->reverse_escape($b);//stripslashes($b);

    #    $invitationdata=$this->getModelByName("invitation");
    #    //$invitations=$invitationdata->getInvitation_Identities(3);

    #    $identities=$invitationdata->getInvitation_Identities_ByIdentities(83, array(1,42,13));
    #    print_r($identities);
#
#        $crossData=$this->getModelByName("X");
#        $cross=$crossData->getCross(3);
#        $place_id=$cross["place_id"];
#        if(intval($place_id)>0)
#        {
#            $placeData=$this->getModelByName("place");
#            $place=$placeData->getPlace($place_id);
#            $cross["place"]=$place;
#        }
#        print_r($cross);
#        $invitation=$invitations[0];
#        $args = array(
#                 'title' => $cross["title"],
#                 'description' => $cross["description"],
#                 'begin_at' => $cross["begin_at"],
#                 'place_line1' => $cross["place"]["line1"],
#                 'place_line2' => $cross["place"]["line2"],
#                 'cross_id_base62' => int_to_base62($cross_id),
#                 'invitation_id' => $invitation["invitation_id"],
#                 'token' => $invitation["token"],
#                 'identity_id' => $invitation["identity_id"],
#                 'provider' => $invitation["provider"],
#                 'external_identity' => $invitation["external_identity"],
#                 'name' => $invitation["name"],
#                 'avatar_file_name' => $invitation["avatar_file_name"]
#         );
#
#        require 'lib/Resque.php';
#        date_default_timezone_set('GMT');
#        Resque::setBackend(RESQUE_SERVER);
#
#
#        print $str;

    }

}
