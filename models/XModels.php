<?php

// upgraded
class XModels extends DataModel {

    // v1_v2_bridge
    protected function getCrossIdByExfeeId($exfee_id) {
        $sql      = "SELECT `id` FROM `crosses` WHERE `exfee_id` = {$exfee_id}";
        $dbResult = $this->getRow($sql);
        return intval($dbResult['id']);
    }


    // upgraded
    public function updateCrossUpdateTime($cross_id)
    {
        $sql = "update crosses set updated_at=now() where `id`=$cross_id;";
        $result = $this->query($sql);
    }


    // upgraded
    public function gatherCross($identityId, $cross, $exfee, $draft_id = 0) {
        // gather a empty cross, state=draft
        // state=1 draft
        // $sql="insert into crosses (host_id,created_at,state) values($identityId,FROM_UNIXTIME($time),'1');";
        // $begin_at=$cross["begin_at"];
        // $end_at=$cross["end_at"];
        // $duration=$cross["duration"];
        // time type

        // get exfee id {
        $dbResult = $this->query("INSERT INTO `exfees` SET `id` = 0");
        $exfee_id = intval($dbResult['insert_id']);
        // }

        $datetime_array = explode(' ', $cross['datetime']);
        $time_type = '';
        if ($cross['datetime'] && sizeof($datetime_array) === 1) {
            $time_type = TIMETYPE_ANYTIME; // anytime
        }
        $sql = "insert into crosses (host_id, created_at, time_type, updated_at,
                state, title, description, begin_at, end_at, duration, place_id,
                timezone, origin_begin_at, background, exfee_id,by_identity_id) values({$identityId}, NOW(),
                '{$time_type}', NOW(), '1', '{$cross['title']}',
                '{$cross['description']}', '{$cross['datetime']}', '{$end_at}',
                '{$duration}', {$cross['place_id']}, '{$cross['timezone']}',
                '{$cross['ori_datetime']}', '{$cross['background']}', {$exfee_id},{$identityId});";

        $result   = $this->query($sql);
        $cross_id = intval($result['insert_id']);
        if($cross_id > 0) {
            // invit exfee
            $hlpExfee = $this->getHelperByName('exfee');
            $hlpExfee->addExfeeIdentify($cross_id, $exfee, $identityId, null, $identityId);
            $hlpExfee->sendInvitation($cross_id, $identityId);
            // update exfee_update_time for v1 v2 bridge
            $this->updateExfeeTime($exfee_id);
            // log x
            $hlpX = $this->getHelperByName('x');
            $hlpX->logX($identityId, $cross_id, $cross['title']);
            // del draft
            if ($draft_id) {
                $hlpX->delDraft($draft_id);
            }
            // return
            return $cross_id;
        }
    }


    // v1 v2 bridge
    public function updateExfeeTime($exfee_id)
    {
        $sql="update invitations set exfee_updated_at=NOW() where `cross_id`=$exfee_id;";
        $this->query($sql);
    }


    // upgraded
    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid";
        $result=$this->getRow($sql);
        return $result;
    }


    // upgraded
    public function updateCrossUpdatedAt($crossId)
    {
        $sql  = "UPDATE `crosses` SET `updated_at` = NOW() WHERE `id` =  {$crossId}";

        return $this->query($sql);
    }


    // upgraded
    public function checkCrossExists($cross_id)
    {
        $sql = "SELECT * FROM crosses WHERE id={$cross_id}";
        $result = $this->getRow($sql);
        return $result;
    }


    // upgraded
    public function updateCross($cross)
    {
        // update place
        $placeHelper  = $this->getHelperByName('place');
        $sql          = "SELECT `place_id`, `exfee_id` FROM `crosses` WHERE `id` = {$cross['id']}";
        $place_id_arr = $this->getRow($sql);
        $place_id     = $place_id_arr['place_id'];
        if($place_id) {
            $placeHelper->savePlace($cross['place'], $place_id);
        } else {
            $place_id = $placeHelper->savePlace($cross['place']);
        }
        // update cross
        $datetime_array = explode(' ', $cross['start_time']);
        $time_type = '';
        if ($cross['start_time'] && sizeof($datetime_array) === 1) {
            $time_type = TIMETYPE_ANYTIME; // anytime
        }
        $sql  = "UPDATE `crosses`
                    SET `updated_at`      = NOW(),
                        `title`           = '{$cross['title']}',
                        `description`     = '{$cross['desc']}',
                        `begin_at`        = '{$cross['start_time']}',
                        `time_type`       = '{$time_type}',
                        `timezone`        = '{$cross['timezone']}',
                        `origin_begin_at` = '{$cross['origin_begin_at']}',
                        `place_id`        =  {$place_id}
                  WHERE `id`              =  {$cross['id']}";
        // update exfee_update_time for v1 v2 bridge {
        $this->updateExfeeTime($place_id_arr['exfee_id']);
        // }
        return $this->query($sql);
    }


    // upgraded
    public function getCrossesByIds($cross_id_list)
    {
        if(sizeof($cross_id_list)>0)
        {
            for($i=0;$i<sizeof($cross_id_list);$i++)
            {
                $cross_id_list[$i]= "c.id=".$cross_id_list[$i];
            }
            $str=implode(" or ",$cross_id_list);
            $sql = "SELECT c.*, p.place_line1, p.place_line2 FROM crosses c LEFT JOIN places p ON(c.place_id = p.id) WHERE ({$str}) ORDER BY created_at DESC;";
            $crosses=$this->getAll($sql);
            return $crosses;
        }
    }


    // upgraded
    public function getCrossByUserId($userid, $updated_since="")
    {
        //get all identityid
        $sql="select identityid from user_identity where userid=$userid ;";
        $identity_id_list=$this->getColumn($sql);
        for($i=0;$i<sizeof($identity_id_list);$i++)
        {
            $identity_id_list[$i]= "identity_id=".$identity_id_list[$i];
        }
        $str=implode(" or ",$identity_id_list);

        //get my invitations
        //find cross_id
        if (intval($updated_since)==0)
            $sql="select distinct cross_id from invitations where  ($str)  order by created_at desc limit 50";
        else
            $sql="select distinct cross_id from invitations where  ($str) and created_at>'$updated_since' order by created_at desc limit 50";
        $cross_id_list=$this->getColumn($sql);
        if(sizeof($cross_id_list)>0)
        {
            for($i=0;$i<sizeof($cross_id_list);$i++)
            {
                $cross_id_list[$i]= "c.id=" . $this->getCrossIdByExfeeId($cross_id_list[$i]);
            }
            $str=implode(" or ",$cross_id_list);
            $sql = "SELECT c.*, p.place_line1, p.place_line2,p.provider as place_provider,p.external_id as place_external_id,p.lng as place_lng,p.lat as place_lat FROM crosses c LEFT JOIN places p ON(c.place_id = p.id) WHERE ({$str}) ORDER BY created_at DESC;";
            $crosses=$this->getAll($sql);
            return $crosses;
        }

        //get my host cross or cross_id
        //now, if a cross related with you, you must have a invitation.
    }


    // upgraded
    public function fetchCross($userid, $begin_at = 0, $opening = 'yes',
                               $order_by = '`begin_at`', $limit = null,
                               $actions  = '')
    {
        // Get user identities
        $sql = "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$userid};";
        $identity_id_list = $this->getColumn($sql);

        // Get crosses id
        for ($i = 0; $i < sizeof($identity_id_list); $i++) {
            $identity_id_list[$i] = '`identity_id` = ' . $identity_id_list[$i];
        }
        $str = implode(' or ', $identity_id_list);
        $sql = "SELECT distinct `cross_id` FROM `invitations` WHERE {$str}";
        $cross_id_list = $this->getColumn($sql);

        // If just get corss number.
        if ($actions === 'count') {
            return count($cross_id_list);
        }

        // Get crosses
        if (!sizeof($cross_id_list)) {
            return array();
        }
        for ($i = 0; $i < sizeof($cross_id_list); $i++) {
            $cross_id_list[$i] = 'c.id = ' . $this->getCrossIdByExfeeId($cross_id_list[$i]);
        }
        $str = implode(' or ', $cross_id_list);
        switch ($opening) {
            case 'yes':
                $strTime = "AND `begin_at` >= FROM_UNIXTIME({$begin_at})
                            AND `begin_at` <> 0";
                break;
            case 'no':
                $strTime = "AND `begin_at` <  FROM_UNIXTIME({$begin_at})
                            AND `begin_at` <> 0";
                break;
            case 'anytime':
                $strTime = "AND `begin_at`  = 0";
                break;
            default:
                $strTime = '';
        }
        $order_by = $order_by ? "ORDER BY {$order_by}" : '';
        $limit    = $limit    ? "LIMIT {$limit}"       : '';

        if ($actions === 'simple') {
            $sql  = "SELECT c.id, c.title, c.begin_at FROM crosses c WHERE ({$str}) {$strTime} {$order_by} {$limit};";
        } else {
            $sql  = "SELECT c.*, p.place_line1, p.place_line2, p.provider as place_provider, p.external_id as place_external_id, p.lng as place_lng, p.lat as place_lat FROM crosses c LEFT JOIN places p ON(c.place_id = p.id) WHERE ({$str}) {$strTime} {$order_by} {$limit};";
        }
        $crosses  = $this->getAll($sql);

        return $crosses;
    }

}
