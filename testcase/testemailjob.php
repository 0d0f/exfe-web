<?php
	require '../lib/Resque.php';
	date_default_timezone_set('GMT');
	Resque::setBackend('127.0.0.1:6379');
	    $args = array(
	    	'title' => "测试用标题",
	    	'description' => "测试用内容",
	    	'cross_id_base62' => 'bd1D',
	    	'invitation_id' => '4',
	    	'identity_id' => '1',
	    	'provider' => 'email',
	    	'external_identity' => 'virushuo@gmail.com',
	    	'name' => 'huoju',
	    	'avatar_file_name' =>'' 
	    );
	    
	    $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $args, true);

#$args['rsvp_status']="1";
#define("INVITATION_MAYBE", 3);
#define("INVITATION_YES", 1);
#define("INVITATION_NO", 2);
#if(intval($args['rsvp_status'])==intval(INVITATION_YES))
#    print "yes\r\n";
#else 
#    print "no\r\n";

#echo intval(INVITATION_YES);
