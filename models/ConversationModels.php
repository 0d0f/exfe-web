<?php
class ConversationModels extends DataModel{
    public function addConversion($postable_id,$postable_type,$identity_id,$title,$content)
    {
	$time=time();
	$content=mysql_real_escape_string($content);
	$title=mysql_real_escape_string($title);
	$sql="insert into posts (identity_id,title,content,postable_id,postable_type,created_at,updated_at) values($identity_id,'$title','$content',$postable_id,'$postable_type',FROM_UNIXTIME($time),FROM_UNIXTIME($time))";
    	$result=$this->query($sql);
	if(intval($result["insert_id"])>0)
	    return intval($result["insert_id"]);
    }

    public function getConversion($postable_id,$postable_type)
    {
	$sql="select * from posts where postable_id=$postable_id and postable_type='$postable_type' order by updated_at desc; ";
    	$result=$this->getAll($sql);
	$identity=array();
	$posts=array();
	if($result)
	    foreach ($result as $post)
	    {
		$identity_id=$post["identity_id"];
		if($identity[$identity_id]=="")
		{	
		    $sql="select external_identity,name,bio,avatar_file_name from identities where id=$identity_id;";
		    $identity=$this->getRow($sql);
		    if($identity)
		    {

			$sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$identity_id";
			$user=$this->getRow($sql);
			if(trim($identity["name"])=="" )
			    $identity["name"]=$user["name"];
			if(trim($identity["avatar_file_name"])=="")
			    $identity["avatar_file_name"]=$user["avatar_file_name"];


		        $post["identity"]=$identity;
		        $identity[$identity_id]=$identity;
		    }
		}
		else
		    $post["identity"]=$identity[$identity_id];
		array_push($posts,$post);
	    }
	return $posts;
	
    }


}
