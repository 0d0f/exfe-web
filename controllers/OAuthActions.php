<?php
require_once dirname(dirname(__FILE__))."/lib/OAuth.php";
require_once dirname(dirname(__FILE__))."/lib/TwitterOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/FacebookOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";
require_once dirname(dirname(__FILE__))."/lib/FoursquareAPI.class.php";

class OAuthActions extends ActionController {

	public function doLoginWithTwitter() {
        if (empty($_SESSION['access_token'])
            || empty($_SESSION['access_token']['oauth_token'])
            || empty($_SESSION['access_token']['oauth_token_secret'])
        ) {
            header('Location: /oAuth/clearTwitterSessions');
        }

        $accessToken = $_SESSION['access_token'];
        $accessTokenStr = packArray($accessToken);

        $twitterConn = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);


        $twitterUserInfo = $twitterConn->get('account/verify_credentials');

        if(gettype($twitterUserInfo) == "object"){
            $twitterUserInfo = (array)$twitterUserInfo;
        }
        $oAuthUserInfo = array(
            "provider"      =>"twitter",
            "id"            =>$twitterUserInfo["id"],
            "name"          =>$twitterUserInfo["name"],
            "sname"         =>$twitterUserInfo["screen_name"],
            "desc"          =>$twitterUserInfo["description"],
            "avatar"        =>str_replace('_normal', '_reasonably_small', $twitterUserInfo["profile_image_url"]),
            "oauth_token"   =>$accessTokenStr
        );
        $external_identity=$oAuthUserInfo["id"];

        $OAuthModel = $this->getModelByName("oAuth");
        $result = $OAuthModel->verifyOAuthUser($oAuthUserInfo);
        $identityID = $result["identityID"];
        $userID = $result["userID"];
        if(!$identityID || !$userID){
            die("OAuth error.");
        }

        //扔一个任务到队列里，去取用户的好友列表。
        $args = array(
            "screen_name"   =>$twitterUserInfo["screen_name"],
            "user_id"       =>$userID,
            "user_token"    =>$accessToken['oauth_token'],
            "user_secret"   =>$accessToken['oauth_token_secret']
        );
        $OAuthHelperHandler = $this->getHelperByName("oAuth");
        $jobToken = $OAuthHelperHandler->twitterGetFriendsList($args);

        $userData = $this->getModelByName("User","v2");

        if($_SESSION['oauth_device']=='iOS')
        {

            $signinResult=$userData->signinForAuthTokenByOAuth("twitter",$result["identityID"],$result["userID"]);
            if($signinResult["token"]!="" && intval($signinResult["user_id"]) == intval($result["userID"]))
            {

                header("location:".$_SESSION['oauth_device_callback']."?token=".$signinResult["token"]."&name=".$oAuthUserInfo["name"]."&userid=".$signinResult["user_id"]."&external_id=".$external_identity."&provider=twitter");
                exit(0);
            }
            header("location:".$_SESSION['oauth_device_callback']."?err=OAuth error.");
            exit(0);
        }


        $identityModels = $this->getModelByName("identity");
        //===========

        //先初始化一个对象。user_token和user_secret可以在数据库中找到，
        //即identities表中的oauth_token字段的内容，一个加密串，拿出来之后用unpackArray解包可得到一个数组。
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

        $identityModels->loginByIdentityId($identityID, $userID);
        if ($responseCode == 200) {
            if($twitterConn->response['response'] == 'false'){
                unset($oAuthUserInfo["oauth_token"]);
                $token = packArray($oAuthUserInfo);
                header("location:/oAuth/confirmTwitterFollowing?token=".$token);
                exit();
            }
        }
        header("location:/s/profile");
    }

}
