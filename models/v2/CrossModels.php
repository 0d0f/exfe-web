<?php

class CrossModels extends DataModel {
    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid;";
        $result=$this->getRow($sql);
        return $result;
    }
    public function addCross($cross,$place_id=0,$exfee_id=0)
    {
        $cross_time=$cross->time;
        $widgets=$cross->widget;
        $background="";
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
                timezone, origin_begin_at, background,date_word,time_word,`date`,`time`,outputformat) values({$cross->by_identity_id}, NOW(),
                '{$time_type}', NOW(), '1', '{$cross->title}',
                '{$cross->description}',{$exfee_id},'{$begin_at_time_in_old_format}',
                {$place_id}, '{$cross_time->begin_at->timezone}',
                '{$cross_time->origin}', '{$background}','{$cross_time->begin_at->date_word}','{$cross_time->begin_at->time_word}','{$cross_time->begin_at->date}','{$cross_time->begin_at->time}','{$cross_time->outputformat}');";

            $result   = $this->query($sql);
            $cross_id = intval($result['insert_id']);
            return $cross_id;
        }
        else {
            $sql="update crosses set host_id={$cross->by_identity_id},  updated_at=now(),title='{$cross->title}', description='{$cross->description}',exfee_id=$exfee_id, begin_at='{$cross_time->begin_at->date_word}', place_id=$place_id, timezone='{$cross_time->begin_at->timezone}', origin_begin_at='{$cross_time->origin}', background='{$background}',date_word='{$cross_time->begin_at->date_word}',time_word='{$cross_time->begin_at->time_word}',`date`='{$cross_time->begin_at->date}',`time`='{$cross_time->begin_at->time}',outputformat='{$cross_time->outputformat}' where `id`=$cross->id;";

            $result   = $this->query($sql);
            if($result   >0)
                return $cross->id;
            else return 0;
        }

    }
    public function getExfeeByCrossId($cross_id)
    {
        $sql="select exfee_id from crosses where `id`=$cross_id";
        $result=$this->getRow($sql);
        return $result["exfee_id"];
    }
}
