<?php
require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class Twitternewtweet_Job
{
    public function perform()
    {
        $userName = $this->args["screen_name"];
        $userToken = $this->args["user_token"];
        $userSecret = $this->args["user_secret"];
        $userTweet = $this->args["user_tweet"];

        if(mb_strlen($userTweet, "UTF-8") > 140){
            die("{$userName}'s Tweet is over 140 characters.\r\n");
        }

        //通过UserAccessToken进行登录处理。
        $twitterConn = new tmhOAuth(array(
            'consumer_key' => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
            'user_token' => $userToken,
            'user_secret' => $userSecret
        ));

        $responseCode = $twitterConn->request('POST', $twitterConn->url('1/statuses/update'), array(
            'status'=>$userTweet
        ));


        if ($responseCode == 200) {
            echo "{$userName}'s Tweet has been sent!\r\n";
        } else {
            $result = (array)json_decode($twitterConn->response['response']);
            echo "{$userName}'s Tweet sent error! [".$result["error"]."]\r\n";
        }

    }
}
?>
