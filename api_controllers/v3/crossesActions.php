<?php

class CrossesActions extends ActionController {

    public function doWechatImage() {
        // init requirement
        $params = $this->params;
        $curDir = dirname(__FILE__);
        $resDir    = "{$curDir}/../../default_avatar_portrait/";
        $bkgDir    = "{$curDir}/../../static/img/xbg/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config = [
            'width'            => 320,
            'height'           => 180,
            'avatar-size'      => 112,
            'routex-icon'      => "{$resDir}widget_routex_60@2x.png",
            'shadow-file'      => "{$resDir}wechat_x_shadow@2x.png",
            'routex-icon-size' => 60 * 2,
            'routex-icon-x'    => 252,
            'routex-icon-y'    => 12,
            'line-color'       => [127, 127, 127, 0.5],
            'jpeg-quality'     => 60,
            'period'           => 604800, // 60 * 60 * 24 * 7
        ];
        // check cross
        $crossHelper = $this->getHelperByName('Cross');
        $cross       = $crossHelper->getCross((int) @$params['id']);
        if ($cross && $cross->attribute['state'] !== 'deleted') {
        } else {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // check identity
        $identity_id = (int) $params['identity_id'];
        if (!$identity_id) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // header
        imageHeader('jpeg');
        // try cache
        $rsImage = $objLibImage->getImageCache(
            IMG_CACHE_PATH, $this->route, $config['period'], false, 'jpg'
        );
        if ($rsImage) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $rsImage['time']) . ' GMT');
            $imfSince = @strtotime($this->params['if_modified_since']);
            if ($imfSince && $imfSince >= $rsImage['time']) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
            fpassthru($rsImage['resource']);
            fclose($rsImage['resource']);
            return;
        }
        // render background
        $background = 'default.jpg';
        foreach ($cross->widget as $widget) {
            if ($widget->type === 'Background' && $widget->image) {
                $background = $widget->image;
            }
        }
        $backgroundFile = @file_get_contents("{$bkgDir}{$background}");
        $image  = @imagecreatefromstring($backgroundFile);
        $width  = $config['width']  * 2;
        $height = $config['height'] * 2;
        $image  = $objLibImage->rawResizeImage($image, $width, $height);
        // get avatar image
        $modIdentity = $this->getModelByName('Identity');
        $identity    = $modIdentity->getIdentityById($identity_id);
        if ($identity) {
            $imageUrl    = $identity->avatar['320_320'];
            if (strpos($imageUrl, API_URL) !== false) {
                $modUser     = $this->getModelByName('User');
                $arrQuery    = explode('=', explode('&', parse_url($imageUrl, PHP_URL_QUERY))[0]);
                $avatarImage = isset($arrQuery[1]) && $arrQuery[1]
                             ? $modUser->makeDefaultAvatar(urldecode($arrQuery[1]), true) : null;
            } else {
                $avatarImage = httpKit::fetchImageExpress($imageUrl);
            }
            if ($avatarImage) {
                $avatarSize  = $config['avatar-size'] * 2;
                $avatarImage = $objLibImage->rawResizeImage(
                    $avatarImage, $avatarSize, $avatarSize
                );
                imagecopyresampled(
                    $image, $avatarImage,
                    ($width  - $avatarSize) / 2,
                    ($height - $avatarSize) / 2,
                    0, 0, $avatarSize, $avatarSize, $avatarSize, $avatarSize
                );
                $color = imagecolorallocatealpha(
                    $image,
                    $config['line-color'][0],
                    $config['line-color'][1],
                    $config['line-color'][2],
                    (1 - $config['line-color'][3]) * 127
                );
                $x1 = ($width  - $avatarSize) / 2 - 1;
                $y1 = ($height - $avatarSize) / 2 - 1;
                $x2 = ($width  + $avatarSize) / 2;
                $y2 = ($height + $avatarSize) / 2;
                imageline($image, $x1, $y1, $x2, $y1, $color);
                imageline($image, $x2, $y1, $x2, $y2, $color);
                imageline($image, $x2, $y2, $x1, $y2, $color);
                imageline($image, $x1, $y2, $x1, $y1, $color);
                imagedestroy($avatarImage);
            }
        }
        // render shadow
        $shadowImage = @imagecreatefrompng($config['shadow-file']);
        imagecopyresampled(
            $image, $shadowImage, 0, 0, 0, 0, $width, $height, $width, $height
        );
        imagedestroy($shadowImage);
        // render routerx icon
        $iconImage = @imagecreatefrompng($config['routex-icon']);
        imagecopyresampled(
            $image, $iconImage,
            $config['routex-icon-x'] * 2, $config['routex-icon-y'] * 2, 0, 0,
            $config['routex-icon-size'],  $config['routex-icon-size'],
            $config['routex-icon-size'],  $config['routex-icon-size']
        );
        imagedestroy($iconImage);
        // render
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
        imagejpeg($image, null, $config['jpeg-quality']);
        $objLibImage->setImageCache(
            IMG_CACHE_PATH, $this->route, $image, 'jpg', $config['jpeg-quality']
        );
        imagedestroy($image);
    }


    public function doImage() {
        // init requirement
        $params = $this->params;
        $curDir = dirname(__FILE__);
        $resDir    = "{$curDir}/../../default_avatar_portrait/";
        $imgDir    = "{$curDir}/../../static/img/";
        $bkgDir    = "{$imgDir}xbg/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        set_time_limit(10);
        $config = [
            'width'           => 320,
            'height'          => 180,
            'cx'              => -64,
            'cy'              => -123,
            'cr'              => 367,
            'c-x-pos-a'       => 275,
            'c-x-pos-b'       => 555,
            'map-width'       => 285,
            'map-height'      => 235,
            'map-zoom-level'  => 13,
            'map-zoom-time'   => 1,
            'map-accuracy'    => 4,
            'markx'           => 280,
            'marky'           => 116,
            'mark-width'      => 48,
            'mark-height'     => 68,
            'avatar-size'     => 64,
            'mask-file'       => "{$resDir}wechat_x_mask@2x.png",
            'shadow-map-file' => "{$resDir}wechat_x_shadow_map@2x.png",
            'map-default'     => "{$resDir}wechat_default_map@2x.png",
            'map-mark-x'      => "{$imgDir}map_mark_diamond_blue@2x.png",
            'map-mark-routex' => "{$imgDir}map_mark_ring_blue@2x.png",
            'jpeg-quality'    => 100,
            'period'          => 604800, // 60 * 60 * 24 * 7
        ];
        $avatarLayout = [[0, 0], [1, 0], [2, 0], [3, 0], [0, 1], [1, 1], [2, 1]];
        // load models
        $modExfee    = $this->getModelByName('Exfee');
        $modUser     = $this->getModelByName('User');
        $crossHelper = $this->getHelperByName('cross');
        $invitation  = $modExfee->getRawInvitationByToken(@$params['xcode']);
        $cross       = $crossHelper->getCross(@$invitation['cross_id']);
        // check cross
        if ($invitation && $cross
         && $invitation['cross_id']    === (int) $params['id']
         && $invitation['state']       !== 4
         && $cross->attribute['state'] !== 'deleted') {
        } else {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // header
        imageHeader('jpeg');
        // ready
        $updated_at = strtotime($cross->exfee->updated_at);
        // get routex location
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
            $lat  = $geoMarks['lat'];
            $lng  = $geoMarks['lng'];
            $mark = $config['map-mark-routex'];
            $updated_at = $geoMarks['updated_at'] > $updated_at ? $geoMarks['updated_at'] : $updated_at;
        } else if ($cross->place && $cross->place->lat && $cross->place->lng) {
            $lat  = $cross->place->lat;
            $lng  = $cross->place->lng;
            $mark = $config['map-mark-x'];
        } else {
            $lat  = '';
            $lng  = '';
            $mark = '';
        }
        // try cache
        $rsImageKey = "{$this->route}&updated_at={$updated_at}";
        $rsImage = $objLibImage->getImageCache(
            IMG_CACHE_PATH, $rsImageKey, $config['period'], false, 'jpg'
        );
        if ($rsImage) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $rsImage['time']) . ' GMT');
            $imfSince = @strtotime($this->params['if_modified_since']);
            if ($imfSince && $imfSince >= $rsImage['time']) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
            fpassthru($rsImage['resource']);
            fclose($rsImage['resource']);
            return;
        }
        // create base image
        $width  = $config['width']  * 2;
        $height = $config['height'] * 2;
        $image  = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        // render map
        $zoom = $config['map-zoom-level'];
        if ((!$lat || !$lng) && ($user_id = (int) @$params['user_id'])) {
            $userLocation = httpKit::request(
                EXFE_AUTH_SERVER . "/v3/routex/_inner/breadcrumbs/users/{$user_id}",
                ['coordinate' => 'earth'], null,
                false, false, 3, 3, 'json', true
            );
            $userLocation = (
                $userLocation
             && $userLocation['http_code'] === 200
             && $userLocation['json']
             && $userLocation['json']['positions']
             && $userLocation['json']['positions'][0]
             && $userLocation['json']['positions'][0]['gps']
            ) ? $userLocation['json']['positions'][0]['gps'] : null;
            if ($userLocation) {
                $lat = $userLocation[0];
                $lng = $userLocation[1];
            } else {
                $rawUser = $modUser->getRawUserById($user_id);
                if ($rawUser && $rawUser['timezone']) {
                    $modTime = $this->getModelByName('Time');
                    $dgtTime = $modTime->getDigitalTimezoneBy($rawUser['timezone'], true);
                    $lng  = $modTime->getLongitudeBy($dgtTime);
                    $lat  = 1;
                    $zoom = $config['map-zoom-time'];
                }
            }
        }
        $mapImage = null;
        if ($lat && $lng) {
            $lat = round($lat, $config['map-accuracy']);
            $lng = round($lng, $config['map-accuracy']);
            $mapImageKey = "cross_image_map:{$lat},{$lng}";
            $mapImage = $objLibImage->getImageCache(
                IMG_CACHE_PATH, $mapImageKey, 60 * 60 * 24 * 30, true
            );
            if (!$mapImage) {
                $mapImage = httpKit::fetchImageExpress(
                    'https://maps.googleapis.com/maps/api/staticmap?center='
                  . "{$lat},{$lng}&zoom={$zoom}"
                  . "&size={$config['map-width']}x{$config['map-height']}"
                  . '&maptype=road&sensor=false&scale=2'
                );
                $objLibImage->setImageCache(IMG_CACHE_PATH, $mapImageKey, $mapImage);
            }
        }
        if (!$mapImage) {
            $mapImage = @imagecreatefrompng($config['map-default']);
        }
        $mapWidth  = $config['map-width']  * 2;
        $mapHeight = $config['map-height'] * 2;
        imagecopyresampled(
            $image, $mapImage,
            $config['markx'] * 2 - $config['map-width'],
            $config['marky'] * 2 - $config['map-height'],
            0, 0, $mapWidth, $mapHeight, $mapWidth, $mapHeight
        );
        imagedestroy($mapImage);
        // render background
        $background = 'default.jpg';
        foreach ($cross->widget as $widget) {
            if ($widget->type === 'Background') {
                $background = $widget->image;
            }
        }
        $backgroundPath  = "{$bkgDir}{$background}";
        $maskImage = @imagecreatefrompng($config['mask-file']);
        // try background cache
        $bgImageKey      = "cross_image_background:{$backgroundPath}";
        $backgroundImage = $objLibImage->getImageCache(
            IMG_CACHE_PATH, $bgImageKey, 60 * 60 * 24 * 365, true
        );
        if (!$backgroundImage) {
            $backgroundImage = imagecreatetruecolor($width, $height);
            imagefill($backgroundImage, 0, 0, imagecolorallocatealpha($backgroundImage, 0, 0, 0, 127));
            imagesavealpha($backgroundImage, true);
            $tmpBgImage = @imagecreatefromstring(@file_get_contents($backgroundPath));
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
            // set cache
            $objLibImage->setImageCache(IMG_CACHE_PATH, $bgImageKey, $backgroundImage);
        }
        // render mark
        if ($mark) {
            $markImage = @imagecreatefrompng($mark);
            imagecopyresampled(
                $image, $markImage,
                $config['markx'] * 2 - $config['mark-width'] / 2,
                $config['marky'] * 2 - $config['mark-height'], 0, 0,
                $config['mark-width'], $config['mark-height'],
                $config['mark-width'], $config['mark-height']
            );
            imagedestroy($markImage);
        }
        // ready shadow
        $shadowImage = @imagecreatefrompng($config['shadow-map-file']);
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
                $arrQuery    = explode('=', explode('&', parse_url($imageUrl, PHP_URL_QUERY))[0]);
                $imageObject = isset($arrQuery[1]) && $arrQuery[1]
                             ? $modUser->makeDefaultAvatar(urldecode($arrQuery[1]), true) : null;
            } else {
                $imageObject = httpKit::fetchImageExpress($imageUrl);
            }
            if ($imageObject) {
                $imageObject = $objLibImage->rawResizeImage(
                    $imageObject, $avatarSize, $avatarSize
                );
                if ($alI == 3) {
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
        // merge shadow layers
        imagecopyresampled(
            $image, $shadowImage, 0, 0, 0, 0, $width, $height, $width, $height
        );
        imagedestroy($shadowImage);
        // render
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
        imagejpeg($image, null, $config['jpeg-quality']);
        $objLibImage->setImageCache(IMG_CACHE_PATH, $rsImageKey, $image, 'jpg', $config['jpeg-quality']);
        imagedestroy($image);
    }

}
