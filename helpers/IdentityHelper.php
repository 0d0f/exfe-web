<?php

class IdentityHelper extends ActionController
{
    public function sentActiveEmail($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","emailactivecode_job" , $args, true);
            return $jobId;
    }
    public function sendResetPassword($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","emailresetpassword_job" , $args, true);
            return $jobId;
    }

    public function sentWelcomeAndActiveEmail($args)
    {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","welcomeandactivecode_job" , $args, true);
            return $jobId;
    }

}


