<?php
//require_once("../lib/class.phpmailer.php");
include('Mail/mime.php');
include('sdk/sdk.class.php');

$connect_count=array("apn_connect"=>0,"email_connect"=>0);
$apn_connect="";
$email_connect="";
$redis_connect="";

function smtp_connect()
{
    global $email_connect;
    if($email_connect=="")
    {
        print "init amazon ses email\r\n";
        $email_connect = new AmazonSES();
    }
}

function cleanMailer()
{
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
    $payload = json_encode_nounicode($body);
    #$payload = json_encode($body);
#    echo "r\n======payload size:".strlen($payload)."\r\n";
    $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
    $err=fwrite($apn_connect, $msg);
    return $err;
}

function redis_connect()
{
    global $redis_connect;
    $redis_connect= new Redis();
    $redis_connect->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
}

