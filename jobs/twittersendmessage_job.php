<?php
require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class Twittersendmessage_Job
{
    public function perform()
    {
        $userName = $this->args["screen_name"];
        $userToken = $this->args["user_token"];
        $userSecret = $this->args["user_secret"];
        $toUser = $this->args["to_user"];
        $userMessage = $this->args["user_message"];

        if (!isset($this->args['with_url']) && mb_strlen($userMessage, "UTF-8") > 140){
            //@todo die 使用可能有问题，die会导致这个job被关闭，应该换成 return，我觉得。 by @leaskh
            die("{$userName}'s direct message is over 140 characters.\r\n");
        }

        //通过UserAccessToken进行登录处理。
        $twitterConn = new tmhOAuth(array(
            'consumer_key' => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
            'user_token' => $userToken,
            'user_secret' => $userSecret
        ));

        $responseCode = $twitterConn->request('POST', $twitterConn->url('1/direct_messages/new'), array(
            'screen_name'=>$toUser, 'text'=>$userMessage
        ));


        if ($responseCode == 200) {
            echo "{$userName}'s direct message has been sent!\r\n";
        } else {
            echo "{$userName}'s direct message sent error!\r\n";
        }

    }
}
?>
