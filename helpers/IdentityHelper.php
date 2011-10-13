<?php

class IdentityHelper extends ActionController
{
    public function sentActiveEmail($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("activecode","emailactivecode_job" , $args, true);
            return $jobId;
    }
}


