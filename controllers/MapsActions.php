<?php
require_once dirname(dirname(__FILE__))."/lib/FoursquareAPI.class.php";

class MapsActions extends ActionController {
    public function doGetLocation(){
        $location = trim(exPost("l"));
        $userLat = trim(exPost("userLat"));
        $userLng = trim(exPost("userLng"));

        $foursquareHandler = new FoursquareAPI(FOURSQUARE_CLIENT_KEY,FOURSQUARE_CLIENT_SECRET);

        $locationArr = explode(" ",$location);

        $searchLocation = trim($location);

        //如果用户输入是以空格分隔的地址。
        if(count($locationArr) > 1){
            $districtLocation = $locationArr[0];
            array_shift($locationArr);
            $searchLocation = implode("", $locationArr);
            // Generate a latitude/longitude pair using Google Maps API
            list($lat,$lng) = $foursquareHandler->GeoLocate($districtLocation);
        }else if($userLat != "" && $userLng != ""){
            $lat = $userLat;
            $lng = $userLng;
        }else{
            $districtLocation = mb_substr(trim($location), 0, 2);
            list($lat,$lng) = $foursquareHandler->GeoLocate($districtLocation);
        }
        
        // Prepare parameters
        $queryParams = array(
            "ll"        =>$lat.",".$lng,
            "query"     =>$searchLocation,
            "limit"     =>10
        );

        $returnData = array( "error"=>0, "msg"=>"","response"=>array());

        $responseData = $foursquareHandler->GetPublic("venues/search",$queryParams);
	    $venuesList = json_decode($responseData);
        if($venuesList->meta->code == 400
            || $venuesList->response->groups == NULL
            || sizeof($venuesList->response) == 0){
            $returnData["error"] = 1;
            $returnData["msg"] = 'empty search..';
            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($returnData);
            exit();
        }
        $responseResult = array();
        foreach($venuesList->response->groups as $group){
            foreach($group->items as $venue){
                $item = array(
                    "place_id"         =>$venue->id,
                    "place_name"       =>$venue->name,
                    "place_address"    =>$venue->location->address.$venue->location->crossStreet,
                    "place_lat"        =>$venue->location->lat,
                    "place_lng"        =>$venue->location->lng,
                    "place_city"       =>$venue->location->city,
                    "place_state"      =>$venue->location->state,
                    "place_country"    =>$venue->location->country
                );
                array_push($responseResult, $item);
            }
        }
        $returnData["response"] = $responseResult;

        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($returnData);
    }
}
