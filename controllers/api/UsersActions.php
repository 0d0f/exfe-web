<?php
class UsersActions extends ActionController {

    public function doIndex()
    {

    }
    public function doX()
    {
        //print "user/x";
        $params=$this->params;
        //print $params["id"];
        //print "<br/>";
        //print $params["updated_since"];

        $Data=$this->getModelByName("X");
        $crosses=$Data->getCrossByUserId(intval($params["id"]),intval($params["updated_since"]));
        
        $ConversationData=$this->getModelByName("conversation");
        for($i=0;$i<sizeof($crosses);$i++)
        {
            $cross_id=intval($crosses[$i]["id"]);
            if($cross_id>0)
            {
                $conversations=$ConversationData->getConversion($cross_id,'cross',10);
                $crosses[$i]["conversation"]=$conversations;
            }
        }



        $responobj["meta"]["code"]=200;
        $responobj["response"]["crosses"]=$crosses;
        echo json_encode($responobj);
        
        //get x by id and updated_since
        
    }
}
