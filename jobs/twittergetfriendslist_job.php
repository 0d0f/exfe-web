<?php
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(dirname(__FILE__)) . '/common.php';
require_once dirname(dirname(__FILE__)) . '/DataModel.php';
require_once dirname(dirname(__FILE__)) . '/models/OAuthModels.php';
require_once dirname(dirname(__FILE__)) . '/lib/tmhOAuth.php';
define('TWITTER_FETCH_PAGESIZE', 100);

class Twittergetfriendslist_Job
{
    public function perform()
    {
        $userID = $this->args["user_id"];
        $userName = $this->args["screen_name"];
        $userToken = $this->args["user_token"];
        $userSecret = $this->args["user_secret"];

        //通过UserAccessToken进行登录处理。
        $twitterConn = new tmhOAuth(array(
            'consumer_key' => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
            'user_token' => $userToken,
            'user_secret' => $userSecret
        ));

        $dataCursor = -1;
        $userFriendsIDS = array();
        //先去取得所有好友的ID列表。
        while(true){
            if($dataCursor == 0){ break; }

            $twitterConn->request('GET', $twitterConn->url('1/friends/ids'), array('cursor'=>$dataCursor));
            //array('cursor'=>$dataCursor, 'screen_name'=>'xiaolai')

            $this->checkRateLimit($twitterConn->response);

            if ($twitterConn->response['code'] == 200) {
                $responseData = (array)json_decode($twitterConn->response['response'], true);
                $userFriendsIDS = array_merge($userFriendsIDS, $responseData["ids"]);
                $dataCursor = $responseData["next_cursor"];
            }else{
                echo $twitterConn->response['response'];
                break;
            }
        }

        $userListPage = ceil(count($userFriendsIDS)/TWITTER_FETCH_PAGESIZE);
        //根据好友的列表里的ID分批去取详细的数据。这里每次取一百条数据。
        for($i=0; $i < $userListPage; $i++){
            $getIDSListArr = array_slice($userFriendsIDS, $i*TWITTER_FETCH_PAGESIZE, TWITTER_FETCH_PAGESIZE);
            $getIDSList = implode(',', $getIDSListArr);

            $twitterConn->request('GET', $twitterConn->url('1/users/lookup'), array('user_id'=>$getIDSList));

            $this->checkRateLimit($twitterConn->response);

            if ($twitterConn->response['code'] == 200) {
                $responseData = json_decode($twitterConn->response['response'], true);

                //每取100条数据，进行一次进行分词入库处理。
                if(is_array($responseData) && count($responseData) > 0){
                    $friendsListArr = array();
                    foreach($responseData as $value){
                        $user = (array)$value;
                        $tmpArr = array(
                            "customer_id"   =>$user["id"],
                            "bio"           =>$user["description"],
                            "provider"      =>"twitter",
                            "display_name"  =>$user["name"],
                            "user_name"     =>$user["screen_name"],
                            "avatar_img"    =>$user["profile_image_url"]
                        );
                        array_push($friendsListArr, $tmpArr);
                    }
                    echo "Page:".$i." ".$userName." Num:".count($friendsListArr);
                    echo "\r\n";
                    $OAuthModel = new OAuthModels();
                    $OAuthModel->buildFriendsIndex($userID, $friendsListArr);
                }
            } else {
                echo $twitterConn->response['response'];
                break;
            }

        }
    }

    public function checkRateLimit($responseData){
        $headers = $responseData['headers'];
        if ($headers['x_ratelimit_remaining'] == 0) {
            $reset = $headers['x_ratelimit_reset'];
            $sleep = time() - $reset;
            echo 'rate limited. reset time is ' . $reset . PHP_EOL;
            echo 'sleeping for ' . $sleep . ' seconds';
            sleep($sleep);
        }
    }
}
?>
