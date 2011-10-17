<?php
//require_once("../lib/class.phpmailer.php");
include('Mail/mime.php');
include('sdk/sdk.class.php');

$connect_count=array("apn_connect"=>0,"email_connect"=>0);
$apn_connect="";
$email_connect="";

function smtp_connect()
{
    global $email_connect;
    if($email_connect=="")
    {
        print "init amazon ses email\r\n";
        $email_connect = new AmazonSES();
        //$email_connect= new PHPMailer();
        //$email_connect->CharSet = 'UTF-8';
        //$email_connect->IsSMTP();
        //$email_connect->IsHTML(true);
        //$email_connect->Host = 'ssl://smtp.gmail.com:465';
        //$email_connect->SMTPAuth = TRUE;
        //$email_connect->Username = '0d0fnofity@gmail.com';  // Change this to your gmail adress
        //$email_connect->Password = 'alter8!chill';  // Change this to your gmail password
        //$email_connect->From = '0d0fnofity@gmail.com';  // This HAVE TO be your gmail adress
        //$email_connect->FromName = '0d0fnofity.com'; // This is the from name in the email, you can put anything you like here
        //$email_connect->SMTPKeepAlive = true;
    }
}

function cleanMailer()
{
        //global $email_connect;
        //$email_connect->ClearAddresses();
        //$email_connect->ClearCCs();
        //$email_connect->ClearBCCs();
        //$email_connect->ClearReplyTos();
        //$email_connect->ClearAllRecipients();
        //$email_connect->ClearAttachments();
        //$email_connect->ClearCustomHeaders();
}

function apn_connect()
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
function sendapn($deviceToken,$body)
{
    global $apn_connect;
    var_dump($apn_connect);
    $payload = json_encode($body);
    $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
    $err=fwrite($apn_connect, $msg);
    return $err;
}


