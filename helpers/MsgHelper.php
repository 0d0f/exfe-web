<?php
class MsgHelper extends ActionController
{
    public function sentTemplateEmail($mail)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend('127.0.0.1:6379');
            $jobId = Resque::enqueue("templatemail","templatemail_job" , $mail, true);
            return $jobId;
    }


    public function sentApnConversation($args)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend('127.0.0.1:6379');
            $jobId = Resque::enqueue("iOSAPN","apnconversation_job" , $args, true);
            return $jobId;
    }
}



