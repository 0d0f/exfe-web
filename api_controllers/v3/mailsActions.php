<?php

class MailsActions extends ActionController {

    public function doTitleimage() {
        // init requirement
        $curDir    = dirname(__FILE__);
        $resDir    = "{$curDir}/../../default_avatar_portrait/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config = [
            'width'            => 640,
            'min-height'       => 80,
            'margin-top'       => 200,
            'default-bg-image' => 'default.jpg',
            'line-left'        => 50,
            'line-width'       => 2,
            'line-color'       => [255, 254, 254, 95],
            'map-width'        => 80,
            'map-border-color' => [0, 0, 0, 101],
            'map-zoom-level'   => 13,
            'ribbon-image'     => "{$curDir}/../../static/img/ribbon_280@2x.png",
            'ribbon-padding'   => 7,
            'jpeg-quality'     => 60,
            'period'           => 604800, // 60 * 60 * 24 * 7
        ];

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

        // grep inputs
        $params     = $this->params;
        $background = @ trim(base64_url_decode($params['background']) ?: '');
        $ribbon     = @ strtolower($params['ribbon']) === 'true' ? true : false;
        $lat        = @ $params['lat'] ?: '';
        $lng        = @ $params['lng'] ?: '';
        $mapZoom    = @ (int) $params['map-zoom-level'] ?: $config['map-zoom-level'];
        $background = preg_replace('/^.*\/([^\/]*)$/', '$1', $background);

        // get background
        $background = "{$curDir}/../../static/img/xbg/{$background}";
        $backgroundFile  = @ file_get_contents($background);
        $backgroundImage = @ imagecreatefromstring($backgroundFile);
        if (!$backgroundImage) {
            $background = "{$curDir}/../../static/img/xbg/"
                        . $config['default-bg-image'];
            $backgroundFile  = @ file_get_contents($background);
            $backgroundImage = @ imagecreatefromstring($backgroundFile);
        }
        if (!$backgroundImage) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        $rawBackgroundWidth  = imagesx($backgroundImage);
        $rawBackgroundHeight = imagesy($backgroundImage);
        $backgroundWidth  = $config['width'];
        $backgroundHeight = ($backgroundWidth * $rawBackgroundHeight)
                          /  $rawBackgroundWidth;
        $backgroundImage = $objLibImage->rawResizeImage(
            $backgroundImage, $backgroundWidth, $backgroundHeight
        );

        // make raw image
        $imageWidth  = $backgroundWidth;
        $imageHeight = $backgroundHeight - $config['margin-top'];
        $image       = imagecreatetruecolor($imageWidth, $imageHeight);

        // render background
        imagecopyresampled(
            $image, $backgroundImage,
            0, -$config['margin-top'], 0, 0,
            $backgroundWidth, $backgroundHeight,
            $backgroundWidth, $backgroundHeight
        );

        // render map
        if ($lat && $lng) {
            $mapHeight = $imageHeight * 2;
            $mapImage  = httpKit::fetchImageExpress(
                'https://maps.googleapis.com/maps/api/staticmap?center='
              . "{$lat},{$lng}&markers=icon%3a"
              . urlencode('http://img.exfe.com/web/map_mark_diamond_blue.png')
              . "%7C{$lat},{$lng}&zoom={$mapZoom}"
              . "&size={$config['map-width']}x{$mapHeight}"
              . '&maptype=road&sensor=false&scale=1'
            );
            if ($mapImage) {
                imagecopyresampled(
                    $image, $mapImage,
                    $imageWidth - $config['map-width'],
                    - ($mapHeight - $config['min-height']) / 2 + 50 / 4,
                    0, 0,
                    $config['map-width'], $mapHeight,
                    $config['map-width'], $mapHeight
                );
                $objLibImage->drawDrectangle(
                    $image, $config['width'] - $config['map-width'], 0, 1 / 2, // @dontTouchThisCode @leask
                    $imageHeight, $config['map-border-color']
                );
            }
        }

        // render ribbon
        if ($ribbon) {
            $ribbonFile  = file_get_contents($config['ribbon-image']);
            $ribbonImage = imagecreatefromstring($ribbonFile);
            if (!$ribbonImage) {
                header('HTTP/1.1 500 Internal Server Error');
                return;
            }
            $ribbonWidth  = imagesx($ribbonImage);
            $ribbonHeight = imagesy($ribbonImage);
            imagecopyresampled(
                $image, $ribbonImage, 0, $config['ribbon-padding'], 0, 0,
                $ribbonWidth / 2, $ribbonHeight / 2, $ribbonWidth, $ribbonHeight
            );
        }

        // render line
        $objLibImage->drawDrectangle(
            $image, $config['line-left'], 0, $config['line-width'] / 2, // @dontTouchThisCode @leask
            $imageHeight, $config['line-color']
        );

        // render
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
        imagejpeg($image, null, $config['jpeg-quality']);
        $objLibImage->setImageCache(IMG_CACHE_PATH, $this->route, $image, 'jpg', $config['jpeg-quality']);
        imagedestroy($image);
    }

}
