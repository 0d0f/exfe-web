<?php

class IconsActions extends ActionController {

    public function doMapMark() {
        // init requirement
        $curDir    = dirname(__FILE__);
        $resDir    = "{$curDir}/../../static/img/";
        $fontDir   = "{$curDir}/../../default_avatar_portrait/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config = [
            'red_image'     => "{$resDir}map_mark_red@2x.png",
            'blue_image'    => "{$resDir}map_mark_blue@2x.png",
            'colors'        => ['blue', 'red'],
            'default_color' => 'blue',
            'width'         => 48,
            'height'        => 68,
            'font'          => "{$fontDir}Raleway-Regular.ttf",
            'font_cjk'      => "{$fontDir}wqy-microhei.ttc",
            'font_color'    => [255, 254, 254, 1],
            'font_size'     => 26,
            'font_top'      => -5,
            'font_width'    => 26,
            'font_shadow'   => 0.25,
            'period'        => 604800, // 60 * 60 * 24 * 7
        ];
        // header
        header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        // try cache
        $rsImage = $objLibImage->getImageCache(
            IMG_CACHE_PATH, $this->route, $config['period']
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
        $params  = $this->params;
        $content = @trim($params['content']);
        $content = $content === '' ? 'P' : $content;
        $content = @mb_substr($content, 0, 2, 'UTF-8');
        $font    = checkCjk($content) ? $config['font_cjk'] : $config['font'];
        $content = mb_convert_encoding($content, 'html-entities', 'utf-8');
        $color   = ($color = @strtolower(trim($params['color'])))
                 ? (in_array($color, $config['colors']) ? $color : '')
                 : $config['default_color'];
        if (!$color) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // render background
        $image      = @imagecreatefrompng($config["{$color}_image"]);
        $font_color = imagecolorallocate(
            $image,
            $config['font_color'][0],
            $config['font_color'][1],
            $config['font_color'][2]
        );
        // calcular font size
        $ftSize += $config['font_size'] + 1;
        do {
            $ftSize--;
            $posArr = imagettftext(
                imagecreatetruecolor($config['width'], $config['height']),
                $ftSize, 0, 0, $config['height'], $font_color, $font, $content
            );
            $fWidth = $posArr[2] - $posArr[0];
        } while ($fWidth > $config['font_width']);
        $posArr = imagettftext(
            imagecreatetruecolor($config['width'], $config['height']),
            $ftSize, 0, 0, $config['height'], $font_color, $font, 'x'
        );
        // draw text shadow
        $fontShadowColor = imagecolorallocatealpha(
            $image, 0, 0, 0, 127 * (1 - $config['font_shadow'])
        );
        imagettftext(
            $image, $ftSize, 0, ($config['width'] - $fWidth) / 2,
            ($config['height'] + $posArr[1] - $posArr[7]) / 2 + $config['font_top'] - 1,
            $fontShadowColor, $font, $content
        );
        // draw text
        imagettftext(
            $image, $ftSize, 0, ($config['width'] - $fWidth) / 2,
            ($config['height'] + $posArr[1] - $posArr[7]) / 2 + $config['font_top'],
            $font_color, $font, $content
        );
        // render
        imagealphablending($image, false);
        imagesavealpha($image, true);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
        imagepng($image);
        $objLibImage->setImageCache(IMG_CACHE_PATH, $this->route, $image);
        imagedestroy($image);
    }

}
