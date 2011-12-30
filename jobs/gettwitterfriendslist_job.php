<?php
require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/models/OAuthModels.php";

class Gettwitterfriendslist_Job
{
    public function perform()
    {
        $twitterScreenName = $this->args["screen_name"];
        $userID = $this->args["user_id"];

        $friendsDataURL = TWITTER_API_URL."/statuses/friends/".$twitterScreenName.".json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $friendsDataURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $friendsListData = curl_exec($ch);
        curl_close($ch);
        $friendsListArray = (array)json_decode($friendsListData);
        if(count($friendsListArray) != 0){
            $friendsInfoArr = array();
            foreach($friendsListArray as $value){
                $value = (array)$value;
                $tmpArr = array(
                    "provider"      =>"twitter",
                    "display_name"  =>$value["name"],
                    "user_name"     =>$value["screen_name"],
                    "avatar_img"    =>$value["profile_image_url"]
                );
                array_push($friendsInfoArr, $tmpArr);
            }
            $OAuthModel = new OAuthModels();
            $OAuthModel->buildFriendsIndex($userID, $friendsInfoArr);
        }
    }
}
?>
