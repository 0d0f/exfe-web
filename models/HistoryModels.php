<?php

class HistoryModels extends DataModel {

    public function addLog($from_obj, $from_id, $action, $to_obj, $to_id, $to_field, $change_summy, $meta = '') {
        $from_obj=dbescape($from_obj);
        $from_id=intval($from_id);
        $action=dbescape($action);
        $to_obj=dbescape($to_obj);
        $to_id=intval($to_id);
        $to_field=dbescape($to_field);
        $change_summy=dbescape($change_summy);
        $meta=dbescape($meta);

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
