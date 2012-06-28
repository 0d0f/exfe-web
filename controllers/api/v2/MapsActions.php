<?php
session_write_close();
require_once dirname(dirname(__FILE__)).'/../../lib/FoursquareAPI.class.php';

class MapsActions extends ActionController {
    public function doGetLocation(){

        $params = $this->params;
        $location = trim($params["key"]);
        $lat= trim($params["lat"]);
        $lng= trim($params["lng"]);

        $foursquareHandler = new FoursquareAPI(FOURSQUARE_CLIENT_KEY,FOURSQUARE_CLIENT_SECRET);

        $queryParams = array(
            "ll"        =>$lat.",".$lng,
            "query"     =>$location,
            "limit"     =>10,
             'v' => date('Ymd')
        );

        $responseData = $foursquareHandler->GetPublic("venues/search",$queryParams);
	    $data= json_decode($responseData,true);
        $result_response=array();
        $result_response["places"]=array();
        $result_response["meta"]=$data["meta"];
        
        if($data["meta"]["code"]=="200")
        {
            $venues=$data["response"]["venues"];
            foreach($venues as $venue)
            {
                $place=new Place(0,$venue["name"],$venue["location"]["address"],$venue["location"]["lng"],$venue["location"]["lat"],"4sq",$venue["id"]);
                array_push($result_response["places"],$place);
            }
        }
        echo json_encode($result_response);
    }
}


