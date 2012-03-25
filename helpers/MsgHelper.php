<?php

class MsgHelper extends ActionController {

    public function sentConversationEmail($mail) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            /////////////////////////////////////
            print_r($mail);
            $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $mail, true);
            return $jobId;
    }

    public function sentChangeEmail($mail) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            //$jobId = Resque::enqueue("changeemail","changeemail_job" , $mail, true);
            $jobId = Resque::enqueue("conversationemail","conversationemail_job" , $mail, true);
            return $jobId;
    }

    public function sentApnConversation($args) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
            return $jobId;
    }
    
    public function sentApnchangecross($args) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
            return $jobId;
    }

    public function sentApnRSVP($args) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("iOSAPN","apn_job" , $args, true);
            return $jobId;
    }

    public function sentWelcomeEmail($args) {
            require_once 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("email","welcomeemail_job" , $args, true);
            return $jobId;
    }

}
