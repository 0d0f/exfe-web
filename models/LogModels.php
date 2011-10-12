<?php
class LogModels extends DataModel{

    public function addLog($from_obj, $from_id, $action, $to_obj, $to_id, $to_field, $change_summy, $meta = '')
    {
        $from_obj=mysql_real_escape_string($from_obj);
        $from_id=intval($from_id);
        $action=mysql_real_escape_string($action);
        $to_obj=mysql_real_escape_string($to_obj);
        $to_id=intval($to_id);
        $to_field=mysql_real_escape_string($to_field);
        $change_summy=mysql_real_escape_string($change_summy);
        $meta=mysql_real_escape_string($meta);

        $sql="insert into logs (from_obj, from_id, action, to_obj, to_id, to_field, change_summy, meta) values ('$from_obj',$from_id,'$action','$to_obj',$to_id,'$to_field','$change_summy','$meta');";
        $this->query($sql);

    }

}

