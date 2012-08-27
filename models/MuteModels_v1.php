<?php

class MuteModels extends DataModel {

    public function ifIdentityMute($object,$object_id,$identity_id)
    {
        if($identity_id>0)
        {
            $sql="select userid from user_identity where identityid=$identity_id";
            $trow=$this->getRow($sql);
            if(intval($trow["userid"])>0)
                $userid=intval($trow["userid"]);
            if($userid>0)
                return $this->ifMute($object,$object_id,$userid);
        }
        return FALSE;
    }


    public function ifMute($object,$object_id,$sender_id)
    {
        $sql="select status from mute where object='$object' and object_id=$object_id and sender_id=$sender_id;";
        $row=$this->getRow($sql);
        if(sizeof($row)>0)
        {
            if(intval($row["status"])==1)
                return TRUE;
        }
        return FALSE;

    }


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
