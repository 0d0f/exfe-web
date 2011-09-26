<?php
require("../config.php");
require("connect.php");

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
            $this->connect();
        $this->send($deviceToken,$message,$sound,$badge,$args);
    }
    public function connect()
    {
        global $apn_connect;
        print "init apn\r\n";

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'apns-dev-exfe.pem');  
        $apn_connect = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

        if (!$apn_connect) {
            print "Failed to connect $err $errstr\n";
            return;
        }
        else {
            $err=stream_set_blocking($apn_connect, 0); 
            $err=stream_set_write_buffer($apn_connect, 0); 
        }
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

        
        // send message
        $payload = json_encode($body);
        $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
        $err=fwrite($apn_connect, $msg);
        var_dump($err);
        if($err==0)
        {
            $this->connect();
            $err=fwrite($apn_connect, $msg);
        }
        $connect_count["apn_connect"]=time();
#        fclose($apn_connect);
    }
}

?>
