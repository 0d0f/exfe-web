<?php
class OAuthHelper extends ActionController
{
    public function twitterGetFriendsList($args) {
        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        $jobId = Resque::enqueue("oauth","twittergetfriendslist_job" , $args, true);
        return $jobId;
    }

    public function composeNewTweet($args) {
        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        $jobId = Resque::enqueue("oauth","twitternewtweet_job" , $args, true);
        return $jobId;
    }

    public function twitterSendDirectMessage($args) {
        require 'lib/Resque.php';
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        $jobId = Resque::enqueue("oauth","twittersendmessage_job" , $args, true);
        return $jobId;
    }
}
