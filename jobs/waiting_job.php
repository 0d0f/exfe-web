<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Waiting_Job
{
    public function multi_perform($args)
    {
       
       foreach($args as $arg)
       {
               if($arg["queue_name"]!="" && $arg["jobclass_name"]!="")
               {
                date_default_timezone_set('GMT');
                Resque::setBackend(RESQUE_SERVER);
                $changed_data["queue_name"]="changeemail";
                $changed_data["jobclass_name"]="changeemail_job";
                $jobId = Resque::enqueue($arg["queue_name"],$arg["jobclass_name"] , $arg, true);
                echo "throw back jobid: $jobId\r\n";
               }
       }



    }
    public function perform()
    {
    }

}
?>

