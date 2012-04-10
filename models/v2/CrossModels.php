<?php

class CrossModels extends DataModel {
    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid";
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

        $sql = "insert into crosses (host_id, created_at, time_type, updated_at,
                state, title, description, begin_at, place_id,
                timezone, origin_begin_at, background,date_word,time_word,date,time,outputformat) values({$cross->by_identity_id}, NOW(),
                '{$time_type}', NOW(), '1', '{$cross->title}',
                '{$cross->description}', '{$begin_at_time_in_old_format}',
                {$place_id}, '{$cross_time->begin_at->timezone}',
                '{$cross_time->origin}', '{$background}','{$cross_time->begin_at->date_word}','{$cross_time->begin_at->time_word}','{$cross_time->begin_at->date}','{$cross_time->begin_at->time}','{$cross_time->outputformat}');";

                print $sql;
        //$result   = $this->query($sql);
        //$cross_id = intval($result['insert_id']);

    }
}
