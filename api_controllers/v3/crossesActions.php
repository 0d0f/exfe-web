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
            'period'         => 604800, // 60 * 60 * 24 * 7
        ];

        $modExfee    = $this->getModelByName('Exfee');
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
$lat = 132;
$lng = 44;

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
}

$transparent = imagecolorallocate($mask, 0, 0, 0);
imagecolortransparent($mask, $transparent);
imagefilledellipse($mask, -128, -246, 1468, 1468, $transparent);
imagecopymerge($backgroundImage, $mask, 0, 0, 0, 0, $width, $height, 100);
imagedestroy($mask);







imagepng($backgroundImage);
imagedestroy($backgroundImage);




return;












        }
    }

}
