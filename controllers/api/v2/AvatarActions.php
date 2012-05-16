<?php

class AvatarActions extends ActionController {

	public function doGet() {
		// get source image
		$params = $this->params;
		$type = strtolower(array_pop(explode('.', $params['url'])));
		switch ($type) {
			case 'png':
				$srcImage = ImageCreateFromPNG($params['url']);
				break;
			case 'jpg':
			case 'jpeg':
				$srcImage = ImageCreateFromJpeg($params['url']);
				break;
			case 'gif':
				$srcImage = ImageCreateFromGif($params['url']);
				break;
			default:
				// error
		}
		$imgSize  = array(imagesx($srcImage), imagesy($srcImage));
		// make target image
		$tgtImage = imagecreatetruecolor($imgSize[0], $imgSize[1]);
		imagealphablending($tgtImage, true);
		imagesavealpha($tgtImage, true);
		imagefill($tgtImage, 0, 0, imagecolorallocatealpha($tgtImage, 0, 0, 0, 127));
		// copy source image into target image
		imagecopyresampled($tgtImage, $srcImage, 0, 0, 0, 0, $imgSize[0], $imgSize[1], $imgSize[0], $imgSize[1]);
		imagedestroy($srcImage);
		// draw alpha overlay
		$alpha = (float)$params['alpha'];
		if ($alpha) {
			$color = imagecolorallocatealpha($tgtImage, 255, 255, 255, 127 * $alpha);
			for ($x = 0; $x <= $imgSize[0] - 1; $x++) {
				for ($y = 0; $y <= $imgSize[0] - 1; $y++) {
					$rawPixel = imagecolorat($tgtImage, $x, $y);
					$pixel    = imagecolorsforindex($tgtImage, $rawPixel);
					if ($pixel['alpha'] !== 127) {
						imagesetpixel($tgtImage, $x, $y, $color);
					}
				}
			}
		}
		// render
		header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        imagepng($tgtImage);
        imagedestroy($tgtImage);
	}

}
