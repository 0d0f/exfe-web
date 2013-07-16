<?php

require_once dirname(dirname(__FILE__)) . '/../lib/httpkit.php';


class GuessActions extends ActionController {

    public function doIndex() {
        $params  = $this->params;
        $content = @trim($params['content']);
        if (!$content) {
            // 400 bad req
        }
        $modMap = $this->getModelByName('Map');
        $location = $modMap->getCurrentLocation();

        var_dump($location);
        exit();

        $result = httpKit::request(
            'https://maps.googleapis.com/maps/api/place/nearbysearch/json',
            [
                'key'      => 'AIzaSyDGQgOgDUO_sBPwA42wnMgM6yd6Osi6ck8',
                'location' => '31.250543878922706,121.6255542',
                'radius'   => 50000,
                'sensor'   => 'false',
                'language' => 'zh_cn',
            ], null, false, false, 3, 3, 'txt', true);

        print_r($result['json']);

    }

}
