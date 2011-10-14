<?php
class MuteModels extends DataModel {
    public function setMute($object,$object_id,$sender_id,$status)
    {
        $sql="select object,status from mute where object='$object' and object_id=$object_id and sender_id=$sender_id;";
        $row=$this->getRow($sql);
        if($row["object"]==$object)
        {
            if(intval($row["status"])==$status)
                return 1;
            $sql="update mute set status=$status where object='$object' and object_id=$object_id and sender_id=$sender_id;";
            $result=$this->query($sql);
            if(intval($result["insert_id"])>0)
                return intval($result["insert_id"]);
            else if($result>0)
                return $result;
        }
        else
        {
            $sql="insert into mute (object,object_id,sender_id,status) values ('$object',$object_id,$sender_id,$status)";
            $result=$this->query($sql);
            if(intval($result["insert_id"])>0)
                return intval($result["insert_id"]);
            else if($result>0)
                return $result;
        }

    }

}

