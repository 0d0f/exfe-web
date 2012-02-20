<?php
	require '../lib/Resque.php';
	date_default_timezone_set('GMT');
	Resque::setBackend('127.0.0.1:6379');



	    $args = array(
            'link' => 'http://local.exfe.com/!1a',
            'mutelink' => 'http://local.exfe.com/mute/x?id=1a',
	        'cross_id' => '102',
	        'cross_id_base62' => 'bd1D',
            'action' => 'post',
            'content' => '测试102_1',
            'title' => 'meet virushuo',
            'exfee_name' => 'virushuo',
            'external_identity' => 'virushuo@gmail.com',
            'provider' => 'email',
            'identity' => array(
                'id' => 1,
                'external_identity' => "virushuo@gmail.com",
                'name' => "virushuo",
                'bio' => "",
                'avatar_file_name' => "default.png"
            )
	    );

	    $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $args, true);

	    $args = array(
            'link' => 'http://local.exfe.com/!1a',
            'mutelink' => 'http://local.exfe.com/mute/x?id=1a',
	        'cross_id' => '102',
	        'cross_id_base62' => 'bd1D',
            'action' => 'post',
            'content' => '测试102_2',
            'title' => 'meet virushuo',
            'exfee_name' => 'huoju me',
            'external_identity' => 'huoju@me.com',
            'provider' => 'email',
            'identity' => array(
                'id' => 2,
                'external_identity' => "huoju@me.com",
                'name' => "huoju me",
                'bio' => "",
                'avatar_file_name' => "default.png"
            )
	    );

	    $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $args, true);

	    $args = array(
            'link' => 'http://local.exfe.com/!1a',
            'mutelink' => 'http://local.exfe.com/mute/x?id=1a',
	        'cross_id' => '104',
	        'cross_id_base62' => 'bd1D',
            'action' => 'post',
            'content' => '测试104_1',
            'title' => 'meet virushuo',
            'exfee_name' => 'huoju exfe',
            'external_identity' => 'hj@exfe.com',
            'provider' => 'email',
            'identity' => array(
                'id' => 3,
                'external_identity' => "hj@exfe.com",
                'name' => "hj exfe",
                'bio' => "",
                'avatar_file_name' => "default.png"
            )
	    );

	    $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $args, true);

	    $args = array(
            'link' => 'http://local.exfe.com/!1a',
            'mutelink' => 'http://local.exfe.com/mute/x?id=1a',
	        'cross_id' => '104',
	        'cross_id_base62' => 'bd1D',
            'action' => 'post',
            'content' => '测试104_2',
            'title' => 'meet virushuo',
            'exfee_name' => 'virushuo',
            'external_identity' => 'virushuo@gmail.com',
            'provider' => 'email',
            'identity' => array(
                'id' => 3,
                'external_identity' => "virushuo@gmail.com",
                'name' => "virushuo",
                'bio' => "",
                'avatar_file_name' => "default.png"
            )
	    );
	    $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $args, true);

	    	#'external_identity' => 'huojuhuoju@gmail.com',
	    	#'external_identity' => 'huoju@me.com',
	    	#'external_identity' => 'hj@exfe.com',

#$args['rsvp_status']="1";
#define("INVITATION_MAYBE", 3);
#define("INVITATION_YES", 1);
#define("INVITATION_NO", 2);
#if(intval($args['rsvp_status'])==intval(INVITATION_YES))
#    print "yes\r\n";
#else
#    print "no\r\n";

#echo intval(INVITATION_YES);
