<?php
date_default_timezone_set('GMT');
#require 'bad_job.php';
require_once 'email_job.php';
#require_once 'emailactivecode_job.php';
require_once 'emailverifying_job.php';
require_once 'welcomeandactivecode_job.php';
require_once 'welcomeemail_job.php';
require_once 'templatemail_job.php';
require_once 'conversationemail_job.php';
require_once 'changeemail_job.php';
require_once 'apn_job.php';
require_once 'apnconversation_job.php';
require_once 'apntext_job.php';
require_once 'waiting_job.php';
require_once 'iospush_job.php';
require_once 'connect.php';
require_once 'twittergetfriendslist_job.php';
require_once 'twitternewtweet_job.php';
require_once 'twittersendmessage_job.php';

require_once '../resque.php';
?>
