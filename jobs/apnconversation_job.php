<?php
require_once("../config.php");
require_once("connect.php");

class Apnconversation_Job
{
    public function perform()
    {
        global $apn_connect;
        $name=$this->args['exfee_name'];
        $comment=$this->args['comment'];
        if(mb_strlen($comment)>100)
            $comment=mb_substr($comment,0,100);
        $message=$name.":".$comment;
        $sound ="default";
        $cross_id=$this->args["cross_id"];
        $args = array('cross_id' => $cross_id);
        $deviceToken = $this->args["external_identity"];
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
