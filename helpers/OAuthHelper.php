<?php
class OAuthHelper extends ActionController
{
    public function getTwitterFriendsList($args) {
            require 'lib/Resque.php';
            date_default_timezone_set('GMT');
            Resque::setBackend(RESQUE_SERVER);
            $jobId = Resque::enqueue("oauth","gettwitterfriendslist_job" , $args, true);
            return $jobId;
    }
}
