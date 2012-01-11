<?php
require_once("../config.php");
require_once("connect.php");

class Iospush_Job
{
    public function perform()
    {
        global $apn_connect;
        #$name=$this->args['exfee_name'];
        #$content=$this->args['content'];
        #if(mb_strlen($content)>100)
        #    $content=mb_substr($content,0,100);

        $sound ="default";
        $cross_id=$this->args["cid"];
        $type=$this->args["t"];
        $content=$this->args["msg"];
        $args = array('cid' => $cross_id,'t' => $type);
        $deviceToken = $this->args["external_identity"];
        $badge=$this->args["badge"];
        if($apn_connect=="")
            apn_connect();
           // $this->connect();
        $this->send($deviceToken,$content,$sound,$badge,$args);
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
        print_r($body);

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

