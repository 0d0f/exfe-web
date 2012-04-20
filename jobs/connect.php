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
    if($apn_connect)
        fclose($apn_connect);

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
    $identifiers = array();
    for ($i = 0; $i < 4; $i++) {
        $identifiers[$i] = rand(1, 100);
    }
    $payload = json_encode_nounicode($body);
    $msg = chr(1) . chr($identifiers[0]) . chr($identifiers[1]) . chr($identifiers[2]) . chr($identifiers[3]) . pack('N', time() + 3600). chr(0) . chr(32) . pack('H*', $deviceToken) . chr(0) . chr(strlen($payload)) . $payload;

    $err=fwrite($apn_connect, $msg);
    if($err)
    {
        $read = array($apn_connect);
        $null=null;
        $changedStreams = stream_select($read, $null, $null, 0, 1000000);
        if ($changedStreams === false) {    
            echo ("Error: Unabled to wait for a stream availability");
        } elseif ($changedStreams > 0) {
            $responseBinary = fread($apn_connect, 6);
            if ($responseBinary !== false || strlen($responseBinary) == 6) {
                $response = unpack('Ccommand/Cstatus_code/Nidentifier', $responseBinary);
                if($response["identifier"]!="")
                {
                    fclose($apn_connect);
                    $apn_connect=null;
                }
                return $response;
            }
        }
    }
    
    return $err;
}

function redis_connect()
{
    global $redis_connect;
    $redis_connect= new Redis();
    $redis_connect->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
}

