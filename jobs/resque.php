<?php
date_default_timezone_set('GMT');
#require 'bad_job.php';
require_once 'email_job.php';
require_once 'emailactivecode_job.php';
require_once 'templatemail_job.php';
require_once 'conversationemail_job.php';
require_once 'apn_job.php';
require_once 'apnconversation_job.php';
require_once 'apntext_job.php';
require_once 'connect.php';
#require 'php_error_job.php';

require_once '../resque.php';

?>
