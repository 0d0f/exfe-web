<?php

require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class Twitter_Job {

    public function perform() {
        global $site_url;
        $twitterConn = new tmhOAuth(array(
            'consumer_key'    => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
            'user_token'      => TWITTER_ACCESS_TOKEN,
            'user_secret'     => TWITTER_ACCESS_TOKEN_SECRET
        ));
        //print_r($this->args);
        $external_username = 'syxnx';//$this->args['to_identity']['external_username'];
        $responseCode = $twitterConn->request(
            'GET',
            $twitterConn->url('1/friendships/exists'),
            array('screen_name_a' => $external_username,
                  'screen_name_b' => TWITTER_OFFICE_ACCOUNT)
        );
        if ($responseCode != 200) {
            echo "Invalid response\r\n";
            return;
        }
        // build twt
        // link
        $crossLink = " {$site_url}/!" . $this->args['cross_id'];
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
        } else if ($twitterConn->response['response'] === 'false') {
            $strTwt = "@{$external_username} {$strTwt}";
        } else {
            return;
        }
        $lenLink = strlen($crossLink);
        if (mb_strlen($strTwt, 'UTF-8') + $lenLink > 140) {
            while (mb_strlen($strTwt, 'UTF-8') + $lenLink > 137) {
                $strTwt = mb_substr($strTwt, 0, mb_strlen($strTwt, 'UTF-8') - 1, 'UTF-8');
            }
            $strTwt = "{$strTwt}...{$crossLink}";
        }
        // send
        $OAuthHelperHandler = $this->getHelperByName("oAuth");
        if ($twitterConn->response['response'] === 'true') {
            $twt = array(
                "screen_name"  => $external_username,
                "to_user"      => $external_username,
                "user_token"   => $accessToken['oauth_token'],
                "user_secret"  => $accessToken['oauth_token_secret'],
                "user_message" => $strTwt
            );
            $jobToken = $OAuthHelperHandler->twitterSendDirectMessage($twt);
        } else if ($twitterConn->response['response'] === 'false') {
            $twt = array(
                "screen_name"  => $external_username,
                "user_tweet"   => $strTwt,
                "user_token"   => $accessToken['oauth_token'],
                "user_secret"  => $accessToken['oauth_token_secret']
            );
            $jobToken = $OAuthHelperHandler->composeNewTweet($twt);
        }
    }

}

$aa=new Twitter_Job;
$aa->perform();

?>
