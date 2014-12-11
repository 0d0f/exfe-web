<?php

require_once dirname(dirname(__FILE__)) . '/../lib/httpkit.php';


class GuessActions extends ActionController {

    public function doIndex() {
        $params  = $this->params;
        $content = @trim($params['content']);
        if (!$content) {
            $this->jsonError(400, 'empty_content');
            return;
        }
        $latitude  = @trim($params['latitude']);
        $longitude = @trim($params['longitude']);
        if (!$latitude || !$longitude) {
            $modMap = $this->getModelByName('Map');
            $location = $modMap->getCurrentLocation();
            if ($location) {
                $latitude  = $location['latitude'];
                $longitude = $location['longitude'];
            }
        }

$latitude  = '31.178636';
$longitude = '121.535699';

        if (!$latitude || !$longitude) {
            $this->jsonError(400, 'unknow_location');
            return;
        }

        $result = httpKit::request(
            'https://maps.googleapis.com/maps/api/place/nearbysearch/json',
            [
                'key'      => 'AIzaSyDGQgOgDUO_sBPwA42wnMgM6yd6Osi6ck8',
                'location' => "{$latitude},{$longitude}",
                'sensor'   => 'false',
                'language' => $this->locale,
                'keyword'  => $content,
                'rankby'   => 'distance',
            ], null, false, false, 3, 3, 'txt', true
        );

        if ($result
        && @$result['json']
        && @$result['json']['results']) {
            $place = new Place(
                0,
                $result['json']['results'][0]['name'],
                $result['json']['results'][0]['vicinity'],
                $result['json']['results'][0]['geometry']['location']['lng'],
                $result['json']['results'][0]['geometry']['location']['lat'],
                'google',
                $result['json']['results'][0]['id']
            );
            $this->jsonResponse($place);
            return;
        }
        $this->jsonError(404, 'not_found');
    }

}
