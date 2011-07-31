<?php

class ConversationActions extends ActionController {

  public function doAdd()
  {
    if(checklogin()===FALSE)
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }
    $postData=$this->getModelByName("conversation");
    $cross_id=intval($_GET["id"]);
    $identity_id=$_SESSION["identity_id"];
    $postData->addConversion($cross_id,"cross",$identity_id,"",$_POST["comment"]);
    
    $cross_id=intval($_GET["id"]);
    $cross_id_base62=int_to_base62($cross_id);
    header( "Location: /!$cross_id_base62" ) ;
    exit(0);
  }
   
}
