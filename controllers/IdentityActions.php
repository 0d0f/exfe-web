<?php

session_write_close();

require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class IdentityActions extends ActionController {

    public function doGet() {
        $IdentityData  = $this->getModelByName('identity');

        $arrIdentities = json_decode($_GET['identities'], true);

        $responobj['response']['identities'] = array();

        if ($arrIdentities) {
            foreach ($arrIdentities as $identityI => $identityItem) {
                if (!$identityItem['provider']) {
                    continue;
                }
                $identity = $IdentityData->getIdentity($identityItem['external_identity'], $identityItem['provider']);
                if (intval($identity['id']) > 0) {
                    if (!$identity['avatar_file_name'] || !$identity['name']) {
                        $userData = $this->getModelByName('user');
                        $user     = $userData->getUserProfileByIdentityId($identity['id']);
                        $identity = humanIdentity($identity, $user);
                    }
                }

                if ($identity) {
                    $responobj['response']['identities'][] = $identity;
                } else {
                    switch ($identityItem['provider']) {
                        case 'twitter':
                            if (isset($identityItem['external_username'])) {
                                $twitterConn = new tmhOAuth(array(
                                    'consumer_key'    => TWITTER_CONSUMER_KEY,
                                    'consumer_secret' => TWITTER_CONSUMER_SECRET,
                                    'user_token'      => TWITTER_OFFICE_ACCOUNT_ACCESS_TOKEN,
                                    'user_secret'     => TWITTER_OFFICE_ACCOUNT_ACCESS_TOKEN_SECRET
                                ));
                                $responseCode = $twitterConn->request(
                                    'GET',
                                    $twitterConn->url('1/users/show'),
                                    array('screen_name' => $identityItem['external_username'])
                                );
                                if ($responseCode === 200) {
                                    $twitterUser = (array)json_decode($twitterConn->response['response'], true);
                                    $twitterUser['profile_image_url'] = preg_replace(
                                        '/normal(\.[a-z]{1,5})$/i',
                                        'reasonably_small$1',
                                        $twitterUser['profile_image_url']
                                    );
                                    $responobj['response']['identities'][] = array(
                                        'provider'          => 'twitter',
                                        'name'              => $twitterUser['name'],
                                        'bio'               => $twitterUser['description'],
                                        'avatar_file_name'  => $twitterUser['profile_image_url'],
                                        'external_username' => $twitterUser['screen_name'],
                                        'external_identity' => "@{$twitterUser['screen_name']}@twitter",
                                    );
                                }
                            }
                    }
                }
            }
        }

        $responobj['meta']['code'] = 200;
        //$responobj['meta']['errType'] = 'Bad Request';
        //$responobj['meta']['errorDetail'] = 'invalid_auth';

        echo json_encode($responobj);

        exit();
    }


    public function doComplete() {
        $rangelen=50;
        $key=mb_strtolower($_GET["key"]);
        $userid=$_SESSION["userid"];
        $resultarray=array();
        $identityData = $this->getModelByName('identity');
        if(trim($key)!="" && intval($userid)>0)
        {
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            $count=$redis->zCard('u:'.$userid);
            if($count==0)
            {
                $identities=$identityData->getIdentitiesByUser($userid);
                if(sizeof($identities)==0){
                    return;
                } else {
                    $identityData->buildIndex($userid);
                }
            }

            $start=$redis->zRank('u:'.$userid, $key);
            if(is_numeric($start))
            {
                $endflag=FALSE;
                $result=$redis->zRange('u:'.$userid, $start+1, $start+$rangelen);
                while(sizeof($result)>0)
                {
                    foreach($result as $r)
                    {
                        if($r[strlen($r)-1]=="*")
                        {
                            //根据返回的数据拆解Key和匹配的数据。
                            $arr_explode=explode("|",$r);
                            if(sizeof($arr_explode)==2) {
                                $str=rtrim($arr_explode[1], "*");
                                $resultarray[$str]=$arr_explode[0];
                            }
                        }

                        if(strlen($r)==strlen($key))
                        {
                            $endflag=TRUE;
                            break;
                        }
                    }
                    if($result<$rangelen || $endflag===TRUE)
                    {
                        break;
                    }
                    $start=$start+$rangelen;
                    $result=$redis->zRange('u:'.$userid, $start+1, $start+$rangelen);
                }
            }
        }
        $keys=array_keys($resultarray);
        #$resultidentities=array();
        $resultstr="[";
        if(sizeof($keys)>0)
        {
            $identity_id_list=array();
            foreach($keys as $k)
            {
                //为了保证取到正确的Key，必须再拆解一次。
                $key_explode=explode("|",$k);
                //if(intval($key_explode[sizeof($key_explode)-1])>0)
                //默认Key是在最后一位的。这里需要约定一下。By：handaoliang
                //由于Key会包括字符，所以不能以intval该值是否大于0来判断是否存在。By：handaoliang
                if($key_explode[sizeof($key_explode)-1] != NULL)
                {
                    $identity_id=$key_explode[sizeof($key_explode)-1];
                    array_push($identity_id_list,$identity_id);
                }

            }
            if(sizeof($identity_id_list) > 0);
            {
                $identities=$identityData->getIdentitiesByIdsFromCache($identity_id_list);
                foreach($identities as $identity_json)
                {
                    $resultstr.=$identity_json.",";
                    #$iobj=json_decode($identity,true);
                    #array_push($resultidentities,$iobj);
                    #$resultarray[$iobj["id"]]=array("identity"=>$iobj);
                }
                #print_r($iobj);
            }

        }
        #foreach($resultarray as $k=>$v)
        #{
        #    if(!is_array($v["identity"]))
        #    {
        #        $key_explode=explode(" ",$k);
        #        if(intval($key_explode[2])>0)
        #        {
        #            $identity_id=$key_explode[2];
        #            $identity=$identityData->getIdentitiesByIdsFromCache($identity_id);
        #            $iobj=json_decode($identity,true);
        #            $resultarray[$k]=array("identity"=>$iobj);
        #        }
        #    }
        #}
        $resultstr=rtrim($resultstr,",");
        $resultstr.="]";
        echo $resultstr;
        #echo json_encode($resultidentities, JSON_FORCE_OBJECT);
    }


    public function doUpdate() {
        /*--------------------+--------------+------+-----+---------+----------------+
        | Field               | Type         | Null | Key | Default | Extra          |
        +---------------------+--------------+------+-----+---------+----------------+
        | id                  | bigint(11)   | NO   | PRI | NULL    | auto_increment |
        | provider            | varchar(255) | YES  |     | NULL    |                |
        | external_identity   | varchar(255) | YES  |     | NULL    |                |
        | name                | varchar(255) | YES  |     | NULL    |                |
        | bio                 | text         | YES  |     | NULL    |                |
        | avatar_url          | varchar(255) | YES  |     | NULL    |                |
        | external_username   | varchar(255) | YES  |     | NULL    |                |
        +---------------------+--------------+------+-----+---------+---------------*/

        // get raw data
        $id                = !isset($_POST['id']) ? null
                           : mysql_real_escape_string(htmlspecialchars($_POST['id']));
        $provider          = !isset($_POST['provider']) ? null
                           : mysql_real_escape_string(htmlspecialchars($_POST['provider']));
        $external_identity = !isset($_POST['external_identity']) ? null
                           : mysql_real_escape_string(htmlspecialchars($_POST['external_identity']));
        $name              = !isset($_POST['name']) ? ''
                           : mysql_real_escape_string(htmlspecialchars($_POST['name']));
        $bio               = !isset($_POST['bio']) ? ''
                           : mysql_real_escape_string(htmlspecialchars($_POST['bio']));
        $avatar_file_name  = !isset($_POST['avatar_url']) ? ''
                           : mysql_real_escape_string(htmlspecialchars($_POST['avatar_url']));
        $external_username = !isset($_POST['external_username']) ? ''
                           : mysql_real_escape_string(htmlspecialchars($_POST['external_username']));

        // chech data
        if (!intval($id) || $provider === null || $external_identity === null) {
            echo json_encode(array('success' => false));
            return;
        }

        // improve data
        $external_identity = "{$provider}_{$external_identity}";
        $avatar_file_name  = preg_replace('/normal(\.[a-z]{1,5})$/i',
                                          'reasonably_small$1',
                                          $userInfo['profile_image_url']);

        // check old identity
        $row   = $this->getRow("SELECT `id` FROM `identities`
                                WHERE  `provider` = '{$provider}'
                                AND    `external_identity` = '{$external_identity}'");
        $wasId = intval($row['id']);

        // update identity
        $chId  = $wasId > 0 ? $wasId : $id;
        $this->query("UPDATE `identities`
                      SET `external_identity` = '{$external_identity}',
                          `name`              = '{$name}',
                          `bio`               = '{$bio}',
                          `avatar_file_name`  = '{$avatar_file_name}',
                          `external_username` = '{$external_username}',
                          `updated_at`        = NOW(),
                          `avatar_updated_at` = NOW()
                      WHERE `id` = {$id}"
        );

        // merge identity
        if ($wasId > 0) {
            $this->query("UPDATE `invitations`
                          SET    `identity_id` = {$wasId}
                          WHERE  `identity_id` = {$id}"
            );
            // @todo: 可能需要更新 log by @leaskh
            $this->query("DELETE FROM `identities` WHERE `id` = {$id}");
        }

        // return
        echo json_encode(array('success' => true));
        return $id;
    }

}
