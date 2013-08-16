<?php

class CrossesActions extends ActionController {

    public function doIndex() {

    }


    public function doImage() {
        // init requirement
        $params = $this->params;
        $curDir = dirname(__FILE__);
        $bkgDir    = "{$curDir}/../../static/img/xbg/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config = [
            'width'          => 320,
            'height'         => 180,
            'cx'             => -64,
            'cy'             => -123,
            'cr'             => 367,
            'map-width'      => 360,
            'map-height'     => 300,
            'map-zoom-level' => 13,
            'markx'          => 272,
            'marky'          => 134,
            'jpeg-quality'   => 100,
            'avatar-size'    => 64,
            'period'         => 604800, // 60 * 60 * 24 * 7
        ];
        // load models
        $modExfee    = $this->getModelByName('Exfee');
        $modUser     = $this->getModelByName('User');
        $crossHelper = $this->getHelperByName('cross');
        $invitation  = $modExfee->getRawInvitationByToken(@$params['xcode']);
        $cross       = $crossHelper->getCross(@$invitation['cross_id']);
        if ($invitation && $cross
         && $invitation['cross_id']    === (int) $params['id']
         && $invitation['state']       !== 4
         && $cross->attribute['state'] !== 'deleted') {
            // header
            header('Pragma: no-cache');
            header('Cache-Control: no-cache');
            header('Content-Transfer-Encoding: binary');
            header('Content-type: image/jpeg');
            // get background
            $width  = $config['width']  * 2;
            $height = $config['height'] * 2;
            $background = 'default.jpg';
            foreach ($cross->widget as $widget) {
                if ($widget->type === 'Background') {
                    $background = $widget->image;
                }
            }
            $backgroundFile  = @ file_get_contents("{$bkgDir}{$background}");
            $backgroundImage = @ imagecreatefromstring($backgroundFile);
            $backgroundImage = $objLibImage->rawResizeImage(
                $backgroundImage, $width, $height
            );
            // create masking
            $mask = imagecreatetruecolor($width, $height);
            // render map
            $lat = '';
            $lng = '';
            if ($cross->place && $cross->place->lat && $cross->place->lng) {
                $lat = $cross->place->lat;
                $lng = $cross->place->lng;
            }
            $avatarLayout = [[0, 0], [1, 0], [2, 0], [3, 0]];
            if ($lat && $lng) {
                $mapImage = httpKit::fetchImageExpress(
                    'https://maps.googleapis.com/maps/api/staticmap?center='
                  . "{$lat},{$lng}&markers=scale:2|icon%3a"
                  . urlencode('http://img.exfe.com/web/map_pin_blue@2x.png')
                  . "%7C{$lat},{$lng}&zoom={$config['map-zoom-level']}"
                  . "&size={$config['map-width']}x{$config['map-height']}"
                  . '&maptype=road&sensor=false&scale=2'
                );
                if ($mapImage) {
                    $mapWidth  = $config['map-width']  * 2;
                    $mapHeight = $config['map-height'] * 2;
                    imagecopyresampled(
                        $mask, $mapImage,
                        $config['markx'] * 2 - $config['map-width'],
                        $config['marky'] * 2 - $config['map-height'],
                        0, 0,
                        $mapWidth, $mapHeight,
                        $mapWidth, $mapHeight
                    );
                    imagedestroy($mapImage);
                }
                $avatarLayout = array_merge(
                    $avatarLayout, [[0, 1], [1, 1], [2, 1]]
                );
            } else {
                $avatarLayout = array_merge(
                    $avatarLayout, [[4, 0], [1, 1], [2, 1], [3, 1], [4, 1]]
                );
            }
            // render avatar
            $avatarSize = $config['avatar-size'] * 2;
            foreach ($avatarLayout as $alI => $alItem) {
                $avatarLayout[$alI] = [
                    $avatarSize * $alItem[0],
                    $avatarSize * $alItem[1],
                ];
            }
            foreach ($avatarLayout as $alI => $alItem) {
                $imageUrl    = $cross->exfee->invitations[$alI]->identity->avatar['320_320'];
                $imageObject = null;
                if (strpos($imageUrl, API_URL) !== false) {
                    $arr_url     = explode('=', $imageUrl);
                    $imageObject = isset($arr_url[1]) && $arr_url[1]
                                 ? $modUser->makeDefaultAvatar(urldecode($arr_url[1]), true)
                                 : null;
                } else {
                    $imageObject = httpKit::fetchImageExpress($imageUrl);
                }
                if ($imageObject) {
                    $imageObject = $objLibImage->rawResizeImage(
                        $imageObject, $avatarSize, $avatarSize
                    );
                    imagecopyresampled(
                        $backgroundImage, $imageObject, $alItem[0], $alItem[1],
                        0, 0, $avatarSize, $avatarSize, $avatarSize, $avatarSize
                    );
                }
            }
            // merge layers
            $transparent = imagecolorallocate($mask, 0, 0, 0);
            imagecolortransparent($mask, $transparent);
            imagefilledellipse(
                $mask, $config['cx'] * 2, $config['cy'] * 2,
                $config['cr'] * 2 * 2, $config['cr'] * 2 * 2, $transparent
            );
            imagecopymerge($backgroundImage, $mask, 0, 0, 0, 0, $width, $height, 100);
            imagedestroy($mask);
            // output
            imagepng($backgroundImage);
            imagedestroy($backgroundImage);
        }
    }

}
