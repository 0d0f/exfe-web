<?php
class XModels extends DataModel{
    public function gatherCross($identityId,$cross)
    {
        // gather a empty cross, state=draft
        // state=1 draft
        $time=time();
        //$sql="insert into crosses (host_id,created_at,state) values($identityId,FROM_UNIXTIME($time),'1');";	

        //$begin_at=$cross["begin_at"];
        //$end_at=$cross["end_at"];
        //$duration=$cross["duration"];

        $sql="insert into crosses (host_id,created_at,updated_at,state,title,description,begin_at,end_at,duration,place_id) values($identityId,FROM_UNIXTIME($time),FROM_UNIXTIME($time),'1','".$cross["title"]."','".$cross["description"]."','".$cross["datetime"]."','$end_at','$duration',".$cross["place_id"].");";	
        $result=$this->query($sql);
        if(intval($result["insert_id"])>0)
            return intval($result["insert_id"]);
    }

    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid";
        $result=$this->getRow($sql);
        return $result;
    }

    //update cross
    public function updateCross($cross)
    {
        $time=time();
        $sql = "UPDATE crosses SET updated_at=FROM_UNIXTIME($time), title='".$cross["title"]."', description='".$cross["desc"]."', begin_at='".$cross["start_time"]."' WHERE id=".$cross["id"];
        $result = $this->query($sql);
        return $result;
    }

    public function getCrossByUserId($userid,$updated_since=0)
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
            $sql="select distinct cross_id from invitations where  ($str)  order by created_at limit 50";
        else
            $sql="select distinct cross_id from invitations where  ($str) and created_at>FROM_UNIXTIME($updated_since) order by created_at limit 50";
        $cross_id_list=$this->getColumn($sql);
        if(sizeof($cross_id_list)>0)
        {
            for($i=0;$i<sizeof($cross_id_list);$i++)
            {
                $cross_id_list[$i]= "c.id=".$cross_id_list[$i];
            }
            $str=implode(" or ",$cross_id_list);
            $sql="select c.*,places.place_line1,places.place_line2 from crosses c,places where ($str) and c.place_id=places.id order by created_at desc;";
            $crosses=$this->getAll($sql);
            return $crosses;
        }

        //get my host cross or cross_id
        //now, if a cross related with you, you must have a invitation.
    }
}

