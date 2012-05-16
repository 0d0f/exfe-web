<?php

class AvatarActions extends ActionController {

	public function doGet() {
		// init requirement
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/../../../default_avatar_portrait/";
        require_once "{$curDir}/../../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
		// config
		$config = array(
			'width'         => 40,
			'height'        => 40,
			'host-left'     => 29,
			'host-top'      => 0,
			'host-width'    => 10,
			'host-height'   => 10,
			'host-color'    => array(17, 117, 165),
			'host-font'     => "{$resDir}/HelveticaNeueDeskUI.ttc",
			'host-fSize'    => 9,
			'host-fColor'   => array(230, 230, 230),
			'host-left-fix' => 1,
			'host-top-fix'  => 1,
			'host-string'   => 'H',
			'with-left'     => 1,
			'with-top'      => 0,
			'with-width'    => 10,
			'with-height'   => 10,
			'with-color'    => array(17, 117, 165),
			'with-font'     => "{$resDir}/HelveticaNeueDeskUI.ttc",
			'with-fSize'    => 9,
			'with-fColor'   => array(230, 230, 230),
			'with-left-fix' => 2,
			'with-top-fix'  => 1,
			'with-max'      => 9,
		);
		// get source image
		$params = $this->params;
		try {
			if (!$params['url']) {
				throw new Exception('Image url must be given.');
			}
			$type = strtolower(array_pop(explode('.', $params['url'])));
			switch ($type) {
				case 'png':
					@$image = ImageCreateFromPNG($params['url']);
					break;
				case 'jpg':
				case 'jpeg':
					@$image = ImageCreateFromJpeg($params['url']);
					break;
				case 'gif':
					@$image = ImageCreateFromGif($params['url']);
					break;
				default:
					throw new Exception('Error image type.');
			}
			if (!$image) {
				throw new Exception('Error while fetching image.');
			}
		} catch (Exception $error) {
			// get fall back image
			$image = ImageCreateFromPNG("{$curDir}/../../../eimgs/web/80_80_default.png");
		}
		// resize source image
		$image = $objLibImage->rawResizeImage($image, $config['width'], $config['height']);
		// draw alpha overlay
		$alpha = (float)$params['alpha'];
		if ($alpha) {
			$color = imagecolorallocatealpha($image, 255, 255, 255, 127 * $alpha);
			for ($x = 0; $x <= $config['width'] - 1; $x++) {
				for ($y = 0; $y <= $config['height'] - 1; $y++) {
					$rawPixel = imagecolorat($image, $x, $y);
					$pixel    = imagecolorsforindex($image, $rawPixel);
					if ($pixel['alpha'] !== 127) {
						imagesetpixel($image, $x, $y, $color);
					}
				}
			}
		}
		// draw host icon
		if (strtolower($params['host']) === 'true') {
			$image = $objLibImage->drawDrectangle(
				$image, $config['host-left'], $config['host-top'],
				$config['host-width'], $config['host-height'], $config['host-color']
			);
			$image = $objLibImage->drawString(
				$image, $config['host-left'] + $config['host-left-fix'], $config['host-top'] + $config['host-top-fix'],
				$config['host-string'], $config['host-font'], $config['host-fSize'], $config['host-fColor']
			);
		}
		$with = (int)$params['with'];
		// draw with-someone icon
		if ($with) {
			$image = $objLibImage->drawDrectangle(
				$image, $config['with-left'], $config['with-top'],
				$config['with-width'], $config['with-height'], $config['with-color']
			);
			$image = $objLibImage->drawString(
				$image, $config['with-left'] + $config['with-left-fix'], $config['with-top'] + $config['with-top-fix'],
				$with > $config['with-max'] ? $config['with-max'] : $with,
				$config['with-font'], $config['with-fSize'], $config['with-fColor']
			);
		}
		// render
		header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
	}

}
