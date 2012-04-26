<?php

class CrossModels extends DataModel {
    public function getCrossesByExfeeids($exfee_id_list)
    {
        $exfee_ids=implode($exfee_id_list,",");
        $sql="select c.*,p.place_line1,p.place_line2,p.provider,p.external_id,p.lng,p.lat from crosses c,places p where c.place_id=p.id and c.exfee_id in ({$exfee_ids});";
        $result=$this->getAll($sql);
        return $result;
    }
    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid;";
        $result=$this->getRow($sql);
        return $result;
    }
    public function addCross($cross,$place_id=0,$exfee_id=0,$by_identity_id=0)
    {
        $cross_time=$cross->time;
        $widgets=$cross->widget;
        $background="";
        if($widgets)
            foreach($widgets as $widget)
            {
                if($widget->type==="Background")
                    $background=$widget->image;
            }

        
        $begin_at_time_in_old_format=$cross_time->begin_at->date." ".$cross_time->begin_at->time;


        if(intval($cross->id)==0)
        {
            $sql = "insert into crosses (host_id, created_at, time_type, updated_at,
                state, title, description,exfee_id, begin_at, place_id,
                timezone, origin_begin_at, background,date_word,time_word,`date`,`time`,outputformat) values({$cross->host_id}, NOW(),
                '{$time_type}', NOW(), '1', '{$cross->title}',
                '{$cross->description}',{$exfee_id},'{$begin_at_time_in_old_format}',
                {$place_id}, '{$cross_time->begin_at->timezone}',
                '{$cross_time->origin}', '{$background}','{$cross_time->begin_at->date_word}','{$cross_time->begin_at->time_word}','{$cross_time->begin_at->date}','{$cross_time->begin_at->time}','{$cross_time->outputformat}');";

            $result   = $this->query($sql);
            $cross_id = intval($result['insert_id']);
            return $cross_id;
        }
        else {
            $updatefields=array();
            $cross_updated=array();
            
            $updated=array("updated_at"=>time(),"identity_id"=>$by_identity_id);
            if($place_id>0) 
            {
                array_push($updatefields,"place_id=$place_id");
                $cross_updated["place"]=$updated;
            }
            if($cross->title)
            {
                array_push($updatefields,"title='{$cross->title}'");
                $cross_updated["title"]=$updated;
            }
            if($cross->description)
            {
                array_push($updatefields,"description='{$cross->description}'");
                $cross_updated["description"]=$updated;
            }
            if($cross_time)
            {
                array_push($updatefields,"begin_at='{$begin_at_time_in_old_format}'");
                array_push($updatefields,"date_word='{$cross_time->begin_at->date_word}'");
                array_push($updatefields,"time_word='{$cross_time->begin_at->time_word}'");
                array_push($updatefields,"`date`='{$cross_time->begin_at->date}'");
                array_push($updatefields,"`time`='{$cross_time->begin_at->time}'");
                array_push($updatefields,"outputformat='{$cross_time->outputformat}'");
                array_push($updatefields,"timezone='{$cross_time->begin_at->timezone}'");
                array_push($updatefields,"origin_begin_at='{$cross_time->origin}'");
                $cross_updated["time"]=$updated;
            }
            if($background)
            {
                array_push($updatefields,"background='{$background}'");
                $cross_updated["background"]=$updated;
            }
            if($cross->host_id)
            {
                array_push($updatefields,"host_id={$cross->host_id}'");
                $cross_updated["host"]=$updated;
            }
                
            $updatesql=implode($updatefields,",");

            $sql="update crosses set updated_at=now(), $updatesql where `id`=$cross->id;";

            $result = $this->query($sql);
            if($result >0)
            {
                saveUpdate($cross->id,$cross_updated);
                return $cross->id;
            }
            else return 0;
        }

    }
    
    
    public function getExfeeByCrossId($cross_id)
    {
        $sql="select exfee_id from crosses where `id`=$cross_id;";
        $result=$this->getRow($sql);
        return $result["exfee_id"];
    }
    
}
