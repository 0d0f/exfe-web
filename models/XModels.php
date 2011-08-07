<?php
class XModels extends DataModel{
    

    public function gatherCross($identityId,$cross)
    {
	// gather a empty cross, state=draft
	// state=1 draft
	$time=time();
	//$sql="insert into crosses (host_id,created_at,state) values($identityId,FROM_UNIXTIME($time),'1');";	

	$title=$cross["title"];
	$description=$cross["description"];

	$datetime=$cross["datetime"];

	$begin_at=$datetime;
	//$begin_at=$cross["begin_at"];
	//$end_at=$cross["end_at"];
	//$duration=$cross["duration"];
	$place_id=intval($cross["place_id"]);

    
	$title=mysql_real_escape_string($title);
	$description=mysql_real_escape_string($description);

	$sql="insert into crosses (host_id,created_at,state,title,description,begin_at,end_at,duration,place_id) values($identityId,FROM_UNIXTIME($time),'1','$title','$description','$begin_at','$end_at','$duration',$place_id);";	
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

}

