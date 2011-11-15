<?php
class MsgHelper extends ActionController
{
    public function sentConversationEmail($mail)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $mail, true);
            return $jobId;
    }

    public function sentChangeEmail($mail)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("changeemail","changeemail_job" , $mail, true);
            return $jobId;
    }

    public function sentApnConversation($args)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("iOSAPN","apnconversation_job" , $args, true);
            return $jobId;
    }
    public function sentapnchangecross($args)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("iOSAPN","apntext_job" , $args, true);
            return $jobId;
    }
    public function sentWelcomeEmail($args)
    {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","welcomeemail_job" , $args, true);
            return $jobId;
    }
}



