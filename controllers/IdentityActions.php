<?php

class IdentityActions extends ActionController
{

    public function doGet()
    {
        $IdentityData  = $this->getModelByName('identity');

        $arrIdentities = json_decode($_GET['identities'], true);

        $responobj['response']['identities'] = array();

        if ($arrIdentities) {
            foreach ($arrIdentities as $identityI => $identityItem) {
                $identity = $IdentityData->getIdentity($identityItem['id']);
                if (intval($identity['id']) > 0) {
                    if (!$identity['avatar_file_name'] || !$identity['name']) {
                        $userData = $this->getModelByName('user');
                        $user     = $userData->getUserProfileByIdentityId($identity['id']);
                        $identity = humanIdentity($identity, $user);
                    }
                }

                if ($identity) {
                    $responobj['response']['identities'][] = $identity;
                }
            }
        }

        $responobj['meta']['code'] = 200;
        //$responobj['meta']['errType'] = 'Bad Request';
        //$responobj['meta']['errorDetail'] = 'invalid_auth';

        echo json_encode($responobj);

        exit();
    }


    public function doComplete()
    {
        $rangelen=50;
        $key=mb_strtolower($_GET["key"]);
        $userid=$_SESSION["userid"];
        $resultarray=array();
        if(trim($key)!="" && intval($userid)>0)
        {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $count=$redis->zCard('u_'.$userid);
            if($count==0)
            {
                $identityData = $this->getModelByName('identity');
                $identities=$identityData->getIdentitiesByUser($userid);
                if(sizeof($identities)==0)
                    return;
                else
                {
                    $identityData->buildIndex($userid,$identities);
                }
            }

            $start=$redis->zRank('u_'.$userid, $key);
            if(is_numeric($start))
            {
                $endflag=FALSE;
                $result=$redis->zRange('u_'.$userid, $start+1, $start+$rangelen);
                while(sizeof($result)>0)
                {
                    foreach($result as $r)
                    {
                        if($r[strlen($r)-1]=="*")
                        {
                            $arr_explode=explode("|",$r);
                            if(sizeof($arr_explode)==2)
                            {
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
                    $result=$redis->zRange('u_'.$userid, $start+1, $start+$rangelen);
                }
            }
        }
        echo json_encode($resultarray, JSON_FORCE_OBJECT);
    }

}
