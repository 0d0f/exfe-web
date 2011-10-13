<?php
require_once("../config.php");
require_once("connect.php");

class Apn_Job
{
    public function perform()
    {
        global $apn_connect;
        
	    $title=$this->args['title'];
	    $name=$this->args['name'];
	    if($this->args['name']=="")
		    $name=$this->args['external_identity'];
	    $message=$name." 邀请你参加活动 " .$title;
        //$sound = $_GET['sound'] or $sound = $argv[3];
        $sound ="default";
        $args = array('t' => 'i','eid'=>'85');
        $deviceToken = $this->args["identity"]["external_identity"];
        $badge=1;
        if($apn_connect=="")
            apn_connect();
           // $this->connect();
        $this->send($deviceToken,$message,$sound,$badge,$args);
    }

    public function send($deviceToken,$message,$sound,$badge,$args)
    {
        global $apn_connect;
        global $connect_count;
        //["$apn_connect"]
        $body = array();
        $body['aps'] = array('alert' => $message);
        if ($badge)
          $body['aps']['badge'] = $badge;
        if ($sound)
          $body['aps']['sound'] = $sound;
        $body['args']=$args;

        $err=sendapn($deviceToken,$body);
        if($err==0)
        {
            apn_connect();
            $err=sendapn($deviceToken,$body);
            if($err>0)
                $connect_count["apn_connect"]=time();
        }
    }
}

?>
