<?php
class XActions extends ActionController {

    public function doIndex()
    {
        $Data=$this->getModelByName("X");
        $cross=$Data->getCross(base62_to_int($_GET["id"]));
        if($cross)
        {
            $place_id=$cross["place_id"];
            $cross_id=$cross["id"];
            if(intval($place_id)>0)
            {
                $placeData=$this->getModelByName("place");
                $place=$placeData->getPlace($place_id);
                $cross["place"]=$place;


            }
            $invitationData=$this->getModelByName("invitation");
            $invitations=$invitationData->getInvitation_Identities($cross_id);


            $host_exfee=array();
            $normal_exfee=array();
            if($invitations)
                foreach ($invitations as $invitation)
                {
                    if ($invitation["identity_id"]==$cross["host_id"])
                        array_push($host_exfee,$invitation);
                    else
                        array_push($normal_exfee,$invitation);
                }

            $cross["host_exfee"]=$host_exfee;
            $cross["normal_exfee"]=$normal_exfee;

            $ConversionData=$this->getModelByName("conversation");
            $conversationPosts=$ConversionData->getConversion(base62_to_int($_GET["id"]),'cross');
            $cross["conversation"]=$conversationPosts;

            $this->setVar("cross", $cross);
            $this->displayView();
        }
    }
    //public function doGather()
    //{
    //  $crossdata=$this->getDataModel("x");
    //  $result=$crossdata->getCross(base62_to_int($_GET["id"]));
    //  $this->setVar("cross", $result);
    //  $this->displayView();
    // // echo "do edit:".base62_to_int($_GET["id"]);
    //  
    //}

}
