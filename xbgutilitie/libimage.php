<?php
#image lib by @Leaskh

class libImage {

    public function resizeImage($srcFile, $toWidth, $toHeight, $toFile = '', $toQuality = 100) {
        $tinfo = '';
        $data  = GetImageSize($srcFile,$tinfo);
        switch ($data[2]) {
            case 1:
                $curImage = ImageCreateFromGIF($srcFile);
                break;
            case 2:
                $curImage = ImageCreateFromJpeg($srcFile);
                break;
            case 3:
                $curImage = ImageCreateFromPNG($srcFile);
                break;
            default:
                return false;
        }

        $curWidth    = imagesx($curImage);
        $curHeight   = imagesy($curImage);

        $ratioWidth  = $toWidth  / $curWidth;
        $ratioHeight = $toHeight / $curHeight;

        $ratioX = $ratioWidth < $ratioHeight ? $ratioHeight : $ratioWidth;

        $draftWidth  = $curWidth  * $ratioX;
        $draftHeight = $curHeight * $ratioX;
        $draftImage  = imagecreatetruecolor($draftWidth, $draftHeight);
        imagecopyresampled($draftImage, $curImage, 0, 0, 0, 0, $draftWidth,
                           $draftHeight, $curWidth, $curHeight);
        ImageDestroy($curImage);

        $newImage    = imagecreatetruecolor($toWidth, $toHeight);
        imagecopyresampled($newImage, $draftImage, 0, 0,
                           ($draftWidth  - $toWidth)/2,
                           ($draftHeight - $toHeight)/2,
                           $toWidth, $toHeight, $toWidth, $toHeight);

        if ($toFile === '') {
            return $newImage;
        }

        $toType = explode('.', $toFile);
        $toType = strtolower($toType[count($toType) - 1]);

        if (file_exists($toFile)) {
            unlink($toFile);
        }

        switch ($toType) {
            case 'gif':
                ImageGif($newImage, $toFile);
                break;
            case 'jpg':
            case 'jpeg':
                ImageJpeg($newImage, $toFile, $toQuality);
                break;
            case 'png':
                ImagePng($newImage, $toFile);
                break;
            default:
                return false;
        }

        ImageDestroy($draftImage);
        ImageDestroy($newImage);

        return true;
    }


    public function rawResizeImage($curImage, $toWidth, $toHeight) {
        $curWidth    = imagesx($curImage);
        $curHeight   = imagesy($curImage);

        $ratioWidth  = $toWidth  / $curWidth;
        $ratioHeight = $toHeight / $curHeight;

        $ratioX = $ratioWidth < $ratioHeight ? $ratioHeight : $ratioWidth;

        $draftWidth  = $curWidth  * $ratioX;
        $draftHeight = $curHeight * $ratioX;
        $draftImage  = imagecreatetruecolor($draftWidth, $draftHeight);
        // for alpha editing {
        imagealphablending($draftImage, true);
        imagesavealpha($draftImage, true);
        imagefill($draftImage, 0, 0, imagecolorallocatealpha($draftImage, 0, 0, 0, 127));
        // }
        imagecopyresampled($draftImage, $curImage, 0, 0, 0, 0, $draftWidth,
                           $draftHeight, $curWidth, $curHeight);
        ImageDestroy($curImage);

        $newImage    = imagecreatetruecolor($toWidth, $toHeight);
        // for alpha editing {
        imagealphablending($newImage, true);
        imagesavealpha($newImage, true);
        imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
        // }
        imagecopyresampled($newImage, $draftImage, 0, 0,
                           ($draftWidth  - $toWidth)/2,
                           ($draftHeight - $toHeight)/2,
                           $toWidth, $toHeight, $toWidth, $toHeight);
        ImageDestroy($draftImage);

        return $newImage;
    }


    public function drawDrectangle($image, $left, $top, $width, $height, $rgbColor) {
        imagefilledrectangle(
            $image, $left, $top, $left + $width, $top + $height,
            imagecolorallocate(
                $image, $crgbColor[0], $rgbColor[1], $rgbColor[2]
            )
        );
        return $image;
    }


    public function drawString($image, $left, $top, $string, $fontfile, $fontsize, $rgbColor, $bold = false) {
        $top += $fontsize;
        if ($bold) {
            $bold_x = array(1,  0,  1, 0, -1, -1, 1, 0, -1);
            $bold_y = array(0, -1, -1, 0,  0, -1, 1, 1,  1);
            for ($i = 0; $i <= 8; $i++) {
                imagettftext(
                    $image, $fontsize, 0, $left + $bold_x[$i], $top,
                    imagecolorallocate($image, $rgbColor[0], $rgbColor[1], $rgbColor[2]),
                    $fontfile, $string
                );
            }
        } else {
            imagettftext(
                $image, $fontsize, 0, $left, $top,
                imagecolorallocate($image, $rgbColor[0], $rgbColor[1], $rgbColor[2]),
                $fontfile, $string
            );
        }
        return $image;
    }


    function loadImageByUrl($url) {
        if ($url) {
            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $url);
            curl_setopt($objCurl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
            $rawData = curl_exec($objCurl);
            $image   = $rawData ? imagecreatefromstring($rawData) : null;
            curl_close($objCurl);
        } else {
            $image   = null;
        }
        return $image;
    }


    public function imagetextouter(&$im, $size, $x, $y, $color, $fontfile, $text, $outer) {
        if (!function_exists('ImageColorAllocateHEX'))
        {
            function ImageColorAllocateHEX($im, $s)
            {
               if($s{0} == "#") $s = substr($s,1);
               $bg_dec = hexdec($s);
               return imagecolorallocate($im,
                           ($bg_dec & 0xFF0000) >> 16,
                           ($bg_dec & 0x00FF00) >>  8,
                           ($bg_dec & 0x0000FF)
                           );
            }
        }

        $ttf = false;

        if (is_file($fontfile))
        {
            $ttf = true;
            $area = imagettfbbox($size, $angle, $fontfile, $text);

            $width  = $area[2] - $area[0] + 2;
            $height = $area[1] - $area[5] + 2;
        }
        else
        {
            $width  = strlen($text) * 10;
            $height = 16;
        }

        $im_tmp = imagecreate($width, $height);
        $white = imagecolorallocate($im_tmp, 255, 255, 255);
        $black = imagecolorallocate($im_tmp, 0, 0, 0);

        $color = ImageColorAllocateHEX($im, $color);
        $outer = ImageColorAllocateHEX($im, $outer);

        if ($ttf)
        {
            imagettftext($im_tmp, $size, 0, 0, $height - 2, $black, $fontfile, $text);
            imagettftext($im, $size, 0, $x, $y, $color, $fontfile, $text);
            $y = $y - $height + 2;
        }
        else
        {
            imagestring($im_tmp, $size, 0, 0, $text, $black);
            imagestring($im, $size, $x, $y, $text, $color);
        }

        for ($i = 0; $i < $width; $i ++)
        {
            for ($j = 0; $j < $height; $j ++)
            {
                $c = ImageColorAt($im_tmp, $i, $j);
                if ($c !== $white)
                {
                    ImageColorAt ($im_tmp, $i, $j - 1) != $white || imagesetpixel($im, $x + $i, $y + $j - 1, $outer);
                    ImageColorAt ($im_tmp, $i, $j + 1) != $white || imagesetpixel($im, $x + $i, $y + $j + 1, $outer);
                    ImageColorAt ($im_tmp, $i - 1, $j) != $white || imagesetpixel($im, $x + $i - 1, $y + $j, $outer);
                    ImageColorAt ($im_tmp, $i + 1, $j) != $white || imagesetpixel($im, $x + $i + 1, $y + $j, $outer);
                    // 发光效果
                    /*
                    ImageColorAt ($im_tmp, $i - 1, $j - 1) != $white || imagesetpixel($im, $x + $i - 1, $y + $j - 1, $outer);
                    ImageColorAt ($im_tmp, $i + 1, $j - 1) != $white || imagesetpixel($im, $x + $i + 1, $y + $j - 1, $outer);
                    ImageColorAt ($im_tmp, $i - 1, $j + 1) != $white || imagesetpixel($im, $x + $i - 1, $y + $j + 1, $outer);
                    ImageColorAt ($im_tmp, $i + 1, $j + 1) != $white || imagesetpixel($im, $x + $i + 1, $y + $j + 1, $outer);
                    */
                }
            }
        }

        imagedestroy($im_tmp);
    }

}
