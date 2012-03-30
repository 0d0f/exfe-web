<?php

class LogModels extends DataModel {

    public function addLog($from_obj, $from_id, $action, $to_obj, $to_id, $to_field, $change_summy, $meta = '') {
        $from_obj=mysql_real_escape_string($from_obj);
        $from_id=intval($from_id);
        $action=mysql_real_escape_string($action);
        $to_obj=mysql_real_escape_string($to_obj);
        $to_id=intval($to_id);
        $to_field=mysql_real_escape_string($to_field);
        $change_summy=mysql_real_escape_string($change_summy);
        $meta=mysql_real_escape_string($meta);

        $sql="insert into logs (from_obj, from_id, action, to_obj, to_id, to_field, change_summy, meta, time) values ('$from_obj',$from_id,'$action','$to_obj',$to_id,'$to_field','$change_summy','$meta', NOW());";
        $this->query($sql);
    }

    public function getRecentlyLogsByCrossIds($cross_ids, $time = '', $limit = 1000) {
        if ($cross_ids) {
            $cross_ids = implode(' OR `to_id` = ', $cross_ids);
            if ($time) {
                $sql = "SELECT * FROM `logs` WHERE `to_obj` = 'cross' AND (`to_id` = {$cross_ids}) AND time > '{$time}' ORDER BY `id` DESC LIMIT {$limit};";
            } else {
                $sql = "SELECT * FROM `logs` WHERE `to_obj` = 'cross' AND (`to_id` = {$cross_ids}) ORDER BY `id` DESC LIMIT {$limit};";
            }
            return $this->getAll($sql);
        } else {
            return array();
        }
    }

}
