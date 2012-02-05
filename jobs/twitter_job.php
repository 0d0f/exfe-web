<?php

require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class Twitter_Job {

    public function perform() {
        
    
        
        //print_r($this->args);
        
        
        
        
        
        
        
    

　　1、发新Twitter：

        $args = array(
            "screen_name"   =>$twitterUserInfo["screen_name"],
            "user_tweet"       =>"Compose new tweet",
            "user_token"       =>$accessToken['oauth_token'],
            "user_secret"      =>$accessToken['oauth_token_secret']
        );
        $OAuthHelperHandler = $this->getHelperByName("oAuth");
        $jobToken = $OAuthHelperHandler->composeNewTweet($args);


　　2、发新的DirectMessage：

        $args = array(
            "screen_name"   =>$twitterUserInfo["screen_name"],
            "to_user"             =>"handaoliang",
            "user_token"       =>$accessToken['oauth_token'],
            "user_secret"      =>$accessToken['oauth_token_secret'],
            "user_message" =>"Direct message."
        );
        $OAuthHelperHandler = $this->getHelperByName("oAuth");
        $jobToken = $OAuthHelperHandler->twitterSendDirectMessage($args);

　　放到后台之前最好判断是否超过140个字符，否则会发送不成功，我后台进程也会拦截掉。
　　代码：
        if(mb_strlen($userMessage, "UTF-8") > 140){
            die("{$userName}'s direct message is over 140 characters.\r\n");
        } 

        这些参数中，user_token和user_secret可以在数据库中找到，即identities表中的oauth_token字段的内容，一个加密串，拿出来之后用unpackArray解包可得到一个数组。


　　二、判断是否Fo某人：

        $twitterConn = new tmhOAuth(array(
          'consumer_key'    => TWITTER_CONSUMER_KEY,
          'consumer_secret' => TWITTER_CONSUMER_SECRET,
          'user_token'      => $accessToken['oauth_token'],
          'user_secret'     => $accessToken['oauth_token_secret']
        ));

        //通过friendships/exists去判断当前用户screen_name_a是否Follow screen_name_b。
        //如果已经Follow，会返回true，否则False。(String)

        $responseCode = $twitterConn->request('GET', $twitterConn->url('1/friendships/exists'), array(
            'screen_name_a'=>$twitterUserInfo["screen_name"], 'screen_name_b'=>TWITTER_OFFICE_ACCOUNT
        ));
        
        if ($responseCode == 200) {
            if($twitterConn->response['response'] == 'false'){
                unset($oAuthUserInfo["oauth_token"]);
                $token = packArray($oAuthUserInfo);
                header("location:/oAuth/confirmTwitterFollowing?token=".$token);
                exit();
            }
        }



        
        
        
        
        
        
        
        
        
    }

}

?>
