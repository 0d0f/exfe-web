<?php

class CrossesActions extends ActionController {

    public function doIndex() {

    }


    public function doImage() {
        // init requirement
        $params = $this->params;
        $curDir = dirname(__FILE__);
        $resDir    = "{$curDir}/../../static/img/";
        $bkgDir    = "{$resDir}xbg/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        set_time_limit(10);
        $config = [
            'width'          => 320,
            'height'         => 180,
            'cx'             => -64,
            'cy'             => -123,
            'cr'             => 367,
            'c-x-pos-a'      => 275,
            'c-x-pos-b'      => 555,
            'map-width'      => 285,
            'map-height'     => 235,
            'map-zoom-level' => 13,
            'markx'          => 280,
            'marky'          => 116,
            'jpeg-quality'   => 100,
            'avatar-size'    => 64,
            'mask-file'      => "{$resDir}wechat_x_mask@2x.png",
            'shadow-file'    => "{$resDir}wechat_x_shadow@2x.png",
            'jpeg-quality'   => 100,
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
            // ready
            $updated_at = strtotime($cross->exfee->updated_at);
            // get gps
            $geoMarks = httpKit::request(
                EXFE_AUTH_SERVER . "/v3/routex/_inner/geomarks/crosses/{$cross->id}",
                ['tags' => 'destination'], null,
                false, false, 3, 3, 'json', true
            );
            $geoMarks = (
                $geoMarks
             && $geoMarks['http_code'] === 200
             && $geoMarks['json']
             && $geoMarks['json'][0]
            ) ? $geoMarks['json'][0] : [];
            if ($geoMarks) {
                $lat = $geoMarks['lat'];
                $lng = $geoMarks['lng'];
                $updated_at = $geoMarks['updated_at'] > $updated_at ? $geoMarks['updated_at'] : $updated_at;
            } else if ($cross->place && $cross->place->lat && $cross->place->lng) {
                $lat = $cross->place->lat;
                $lng = $cross->place->lng;
            } else {
                $lat = '';
                $lng = '';
            }
            // try cache
            $rsImageKey = "{$this->route}&updated_at={$updated_at}";
            $rsImage = $objLibImage->getImageCache(
                IMG_CACHE_PATH, $rsImageKey, $config['period'], false, 'jpg'
            );
            if ($rsImage) {
                fpassthru($rsImage);
                fclose($rsImage);
                return;
            }
            // create base image
            $width  = $config['width']  * 2;
            $height = $config['height'] * 2;
            $image  = imagecreatetruecolor($width, $height);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            // render map
            $avatarLayout = [[0, 0], [1, 0], [2, 0], [3, 0]];
            if ($lat && $lng) {
                $mapImageKey = "cross_image_map:{$lat},{$lng}";
                $mapImage = $objLibImage->getImageCache(
                    IMG_CACHE_PATH, $mapImageKey, 60 * 60 * 24 * 30, true
                );
                if (!$mapImage) {
                    $mapImage = httpKit::fetchImageExpress(
                        'https://maps.googleapis.com/maps/api/staticmap?center='
                      . "{$lat},{$lng}&markers=scale:2|icon%3a"
                      . urlencode('http://img.exfe.com/web/map_pin_blue@2x.png')
                      . "%7C{$lat},{$lng}&zoom={$config['map-zoom-level']}"
                      . "&size={$config['map-width']}x{$config['map-height']}"
                      . '&maptype=road&sensor=false&scale=2'
                    );
                    $objLibImage->setImageCache(IMG_CACHE_PATH, $mapImageKey, $mapImage);
                }
                if ($mapImage) {
                    $mapWidth  = $config['map-width']  * 2;
                    $mapHeight = $config['map-height'] * 2;
                    imagecopyresampled(
                        $image, $mapImage,
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
                    $avatarLayout, [[4, 0], [0, 1], [1, 1], [2, 1], [3, 1], [4, 1]]
                );
            }
            // render background
            $background = 'default.jpg';
            foreach ($cross->widget as $widget) {
                if ($widget->type === 'Background') {
                    $background = $widget->image;
                }
            }
            $backgroundPath  = "{$bkgDir}{$background}";
            $maskImage = @imagecreatefrompng($config['mask-file']);
            if ($lat && $lng) {
                // try background cache
                $bgImageKey      = "cross_image_background:{$backgroundFile}";
                $backgroundImage = $objLibImage->getImageCache(
                    IMG_CACHE_PATH, $bgImageKey, 60 * 60 * 24 * 365, true
                );
                if (!$backgroundImage) {
                    $backgroundImage = imagecreatetruecolor($width, $height);
                    imagefill($backgroundImage, 0, 0, imagecolorallocatealpha($backgroundImage, 0, 0, 0, 127));
                    imagesavealpha($backgroundImage, true);
                    $backgroundFile  = @ file_get_contents($backgroundPath);
                    $tmpBgImage = @ imagecreatefromstring($backgroundFile);
                    $tmpBgImage = $objLibImage->rawResizeImage(
                        $tmpBgImage, $width, $height
                    );
                    // masking background
                    imagecopyresampled(
                        $backgroundImage, $tmpBgImage, 0, 0,
                        0, 0, $config['c-x-pos-a'], $height, $config['c-x-pos-a'], $height
                    );
                    $calHeight = $height;
                    for ($x = $config['c-x-pos-a']; $x < $config['c-x-pos-b']; $x++) {
                        for ($y = 0; $y < $calHeight; $y++) {
                            $alpha = imagecolorsforindex($maskImage, imagecolorat($maskImage, $x, $y));
                            if ($alpha['red'] === 0) {
                                $calHeight = $y;
                                continue;
                            }
                            $alpha = 127 - floor($alpha['red'] / 2 );
                            if (127 == $alpha) { // int ? float
                                continue;
                            }
                            $color = imagecolorsforindex($tmpBgImage, imagecolorat($tmpBgImage, $x, $y));
                            imagesetpixel($backgroundImage, $x, $y, imagecolorallocatealpha($backgroundImage, $color['red'], $color['green'], $color['blue'], $alpha));
                        }
                    }
                    // merge shadow layers
                    $shadowImage = @imagecreatefrompng($config['shadow-file']);
                    imagecopyresampled(
                        $backgroundImage, $shadowImage, 0, 0,
                        0, 0, $width, $height, $width, $height
                    );
                    imagedestroy($shadowImage);
                    $objLibImage->setImageCache(IMG_CACHE_PATH, $bgImageKey, $backgroundImage);
                }
            } else {
                // render purge background
                $backgroundFile  = @ file_get_contents($backgroundPath);
                $backgroundImage = @ imagecreatefromstring($backgroundFile);
                $backgroundImage = $objLibImage->rawResizeImage(
                    $backgroundImage, $width, $height
                );
            }
            // merge background layer
            imagecopyresampled(
                $image, $backgroundImage, 0, 0,
                0, 0, $width, $height, $width, $height
            );
            imagedestroy($backgroundImage);
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
                    if ($alI == 3 && sizeof($avatarLayout) === 7) {
                        $maxY = $avatarSize;
                        for ($x = 0; $x < $avatarSize; $x++) {
                            $currentX = $alItem[0] + $x;
                            for ($y = 0; $y < $maxY; $y++) {
                                $currentY = $alItem[1] + $y;
                                $alpha = imagecolorsforindex($maskImage, imagecolorat($maskImage, $currentX, $currentY));
                                if ($alpha['red'] === 0) {
                                    $maxY = $y;
                                    continue;
                                }
                                $alpha = 127 - floor($alpha['red'] / 2 );
                                if (127 == $alpha) { // int ? float
                                    continue;
                                }
                                $color = imagecolorsforindex($imageObject, imagecolorat($imageObject, $x, $y));
                                imagesetpixel($image, $currentX, $currentY, imagecolorallocatealpha($image, $color['red'], $color['green'], $color['blue'], $alpha));
                            }
                        }
                    } else {
                        imagecopyresampled(
                            $image, $imageObject, $alItem[0], $alItem[1],
                            0, 0, $avatarSize, $avatarSize, $avatarSize, $avatarSize
                        );
                    }

                }
            }
            imagedestroy($maskImage);
            // render
            imagejpeg($image, null, $config['jpeg-quality']);
            $objLibImage->setImageCache(IMG_CACHE_PATH, $rsImageKey, $image, 'jpg', $config['jpeg-quality']);
            imagedestroy($image);
        } else {
            header('HTTP/1.1 404 Not Found');
        }
    }

}
