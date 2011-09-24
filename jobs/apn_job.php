<?php
require("../config.php");

class Apn_Job
{
    public function perform()
    {
        //$message = $_GET['message'] or $message = $argv[1] or $message = '赞';
        //$badge =5; //(int)$_GET['badge'] or $badge = (int)$argv[2];


	    $title=$this->args['title'];
	    $name=$this->args['name'];
	    if($this->args['name']=="")
		    $name=$this->args['external_identity'];
	    $message=$name." 邀请你参加活动 " .$title;
        //$sound = $_GET['sound'] or $sound = $argv[3];
        $sound ="default";
        $args = array('t' => 'i','eid'=>'85');
        $deviceToken = $this->$args["identity"]["external_identity"];
        $badge=1;

        $this->send($deviceToken,$message,$sound,$badge,$args);
}

    public function send($deviceToken,$message,$sound,$badge,$args)
    {
        $body = array();
        $body['aps'] = array('alert' => $message);
        if ($badge)
          $body['aps']['badge'] = $badge;
        if ($sound)
          $body['aps']['sound'] = $sound;
        $body['args']=$args;
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'apns-dev-exfe.pem');  
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            print "Failed to connect $err $errstr\n";
            return;
        }
        else {
           print "Connection OK\n<br/>";
        }
        
        // send message
        $payload = json_encode($body);
        $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
        print "Sending message :" . $payload . "\n";  
        fwrite($fp, $msg);
        fclose($fp);
    }
}

?>
