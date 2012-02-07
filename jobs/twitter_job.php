<?php
require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/models/OAuthModels.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/Resque.php";

class Twitter_Job {

    public function perform() {
        global $site_url;
        $external_username = $this->args['to_identity']['external_username'];
        $twitterConn = new tmhOAuth(array(
            'consumer_key'    => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
            'user_token'      => TWITTER_ACCESS_TOKEN,
            'user_secret'     => TWITTER_ACCESS_TOKEN_SECRET
        ));
        // update twitter account information
        if (!$this->args['external_identity']) {
            $responseCode = $twitterConn->request(
                'GET',
                $twitterConn->url('1/users/show'),
                array('screen_name' => $external_username)
            );
            if ($responseCode !== 200) {
                echo "Invalid response on getting twitter user informations.\r\n";
            } else {
                $OAuthModel = new OAuthModels();
                $OAuthModel->updateTwitterIdentity(
                    $this->args['identity_id'],
                    $twitterConn->response['response']
                );
            }
        }
        // send twt
        $responseCode = $twitterConn->request(
            'GET',
            $twitterConn->url('1/friendships/exists'),
            array('screen_name_a' => $external_username,
                  'screen_name_b' => TWITTER_OFFICE_ACCOUNT)
        );
        if ($responseCode !== 200) {
            echo "Invalid response on sending twt.\r\n";
            return;
        }
        // build twt
        // link
        $crossLink = " {$site_url}/!" . $this->args['cross_id_base62'];
        // time
        $datetime = explode(' ', $this->args["begin_at"]);
        if ($datetime[0] === '0000-00-00' && $datetime[1] === '00:00:00') {
            $datetime = '';
        } else if ($datetime[1] === '00:00:00') {
            $datetime = " Anytime, {$datetime[0]}";
        } else {
            $datetime = " {$datetime[1]}, {$datetime[0]}";
        }
        // place
        if ($this->args["place_line1"] == '') {
            $place = '';
        } else {
            $place = ' at ' . $this->args["place_line1"];
            if ($this->args["place_line2"] != '') {
                $place .= ', ' . $this->args["place_line2"];
            }
        }        
        $strTwt = 'EXFE invitation: ' . $this->args['title'] . ".{$datetime}{$place}";
        // connect string
        if ($twitterConn->response['response'] === 'true') {
            $crossLink .= '?token=' . $this->args['token'];
        } else if ($twitterConn->response['response'] === 'false') {
            $strTwt     = "@{$external_username} {$strTwt}";
        } else {
            return;
        }
        // @waiting for our own url shorter
        // $lenLink = strlen($crossLink);
        $lenLink = 25;
        if (mb_strlen($strTwt, 'UTF-8') + $lenLink > 140) {
            while (mb_strlen($strTwt, 'UTF-8') + $lenLink > 137) {
                $strTwt = mb_substr($strTwt, 0, mb_strlen($strTwt, 'UTF-8') - 1, 'UTF-8');
            }
            $strTwt .= '...';
        }
        $strTwt .= $crossLink;
        // send
        if ($twitterConn->response['response'] === 'true') {
            $twt = array(
                'screen_name'  => $external_username,
                'to_user'      => $external_username,
                'user_token'   => TWITTER_ACCESS_TOKEN,
                'user_secret'  => TWITTER_ACCESS_TOKEN_SECRET,
                'user_message' => $strTwt,
                'with_url'     => true,
            );
            $jobToken = $this->twitterSendDirectMessage($twt);
        } else if ($twitterConn->response['response'] === 'false') {
            $twt = array(
                'screen_name'  => $external_username,
                'user_tweet'   => $strTwt,
                'user_token'   => TWITTER_ACCESS_TOKEN,
                'user_secret'  => TWITTER_ACCESS_TOKEN_SECRET,
                'with_url'     => true,
            );
            $jobToken = $this->composeNewTweet($twt);
        }
    }

    public function composeNewTweet($args) {
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        $jobId = Resque::enqueue('oauth', 'twitternewtweet_job', $args, true);
        return $jobId;
    }

    public function twitterSendDirectMessage($args) {
        date_default_timezone_set('GMT');
        Resque::setBackend(RESQUE_SERVER);
        $jobId = Resque::enqueue('oauth', 'twittersendmessage_job', $args, true);
        return $jobId;
    }

}
