<?php

class AvatarActions extends ActionController {

	public function doDefault() {
		$params  = $this->params;
        $modUser = $this->getModelByName('user', 'v2');
        $modUser->makeDefaultAvatar($params['name']);
	}


	public function doGet() {
		$params  = $this->params;
        $modUser = $this->getModelByName('user', 'v2');
        if ($params['provider'] && $params['external_id']
         && $modUser->getUserAvatarByProviderAndExternalId($params['provider'], $params['external_id'])) {
			return;
        }
        $modUser->makeDefaultAvatar($params['external_id']);
	}


	public function doRender() {
		// init requirement
        $curDir = dirname(__FILE__);
        $resDir = "{$curDir}/../../../default_avatar_portrait/";
        require_once "{$curDir}/../../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
		// config
		$config = array(
			'width'          => 40,
			'height'         => 40,
			'host-right'     => 1,
			'host-top'       => 1,
			'host-width'     => 10,
			'host-height'    => 10,
			'host-color'     => array(17, 117, 165),
			'host-font'      => "{$resDir}/Arial Bold.ttf",
			'host-fSize'     => 9,
			'host-fColor'    => array(230, 230, 230),
			'host-left-fix'  => 2,
			'host-top-fix'   => 1,
			'host-string'    => 'H',
			'mates-left'     => 1,
			'mates-top'      => 1,
			'mates-width'    => 10,
			'mates-height'   => 10,
			'mates-color'    => array(17, 117, 165),
			'mates-font'     => "{$resDir}/Arial Bold.ttf",
			'mates-fSize'    => 9,
			'mates-fColor'   => array(230, 230, 230),
			'mates-left-fix' => 2,
			'mates-top-fix'  => 1,
			'mates-max'      => 9,
		);
		// get source image
		$params = $this->params;
		try {
			if (!$params['url']) {
				$error = new Exception('Image url must be given.');
				throw $error;
			}
			$arrUrl = explode('.', $params['url']);
			$type   = strtolower(array_pop($arrUrl));
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
					$error  = new Exception('Error image type.');
					throw $error;
			}
			if (!$image) {
				$error = new Exception('Error while fetching image.');
				throw $error;
			}
		} catch (Exception $error) {
			// get fall back image
			$image = ImageCreateFromPNG("{$curDir}/../../../eimgs/web/80_80_default.png");
		}
		// resize source image
		$rqs_width  = (int)$params['width'];
		$rqs_height = (int)$params['height'];
		$config['width']  = $rqs_width  > 0 ? $rqs_width  : $config['width'];
		$config['height'] = $rqs_height > 0 ? $rqs_height : $config['height'];
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
			$host_left = $config['width'] - $config['host-width'] - $config['host-right'] - 1;
			$image = $objLibImage->drawDrectangle(
				$image, $host_left, $config['host-top'], $config['host-width'],
				$config['host-height'], $config['host-color']
			);
			$image = $objLibImage->drawString(
				$image, $host_left + $config['host-left-fix'], $config['host-top'] + $config['host-top-fix'],
				$config['host-string'], $config['host-font'], $config['host-fSize'], $config['host-fColor']
			);
		}
		$mates = (int)$params['mates'];
		// draw mates-someone icon
		if ($mates > 0) {
			$image = $objLibImage->drawDrectangle(
				$image, $config['mates-left'], $config['mates-top'],
				$config['mates-width'], $config['mates-height'], $config['mates-color']
			);
			$image = $objLibImage->drawString(
				$image, $config['mates-left'] + $config['mates-left-fix'], $config['mates-top'] + $config['mates-top-fix'],
				$mates > $config['mates-max'] ? $config['mates-max'] : $mates,
				$config['mates-font'], $config['mates-fSize'], $config['mates-fColor']
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
