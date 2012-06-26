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


	public function doUpdate() {
		// check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        $identity_id = isset($_POST['identity_id'])
        			 ? (int) $_POST['identity_id'] : 0;
		// check identity
        if ($identity_id) {
        	$identity = $modIdentity->getIdentityById($identity_id, $user_id);
        	if (!$identity) {
        		apiError(400, 'identity_does_not_exist', 'The identity you want to update does not exist.');
        	}
        	if ($identity->status !== 'CONNECTED') {
        		apiError(401, 'not_allowed', 'The identity you want to update does not belong to you.');
        	}
        	switch ($identity->provider) {
        		case 'email':
        			break;
        		default:
        			apiError(400, 'provider_error', 'Can not update avatars of this kind of identities.');
        	}
        }
		// check images
        $sizes       = array('original', '80_80');
		$extensions  = array();
        foreach ($sizes as $size) {
        	if (!isset($_FILES[$size])) {
	        	apiError(400, "no_{$size}_image",  "{$size} size image must be provided.");
	        }
	        $arrExpName = explode('.', $_FILES[$size]['name']);
	        $extensions[$size] = strtolower(array_pop($arrExpName));
			switch ($extensions[$size]) {
				case 'png':
				case 'gif':
				case 'bmp':
				case 'jpg':
				case 'jpeg':
					break;
				default:
					apiError(400, "error_{$size}_image_format",  "Error {$size} size image format.");
			}
        }
	    // hash filename
	    if (!($fnmHashed = getHashedFilePath(Uniqid()))) {
	    	apiError(500, 'error_saving_image', 'Error while saving image.');
	    }
	   	// save file
	    $apiResult = array('avatars' => array());
	    foreach ($sizes as $size) {
	    	$filename  = "{$fnmHashed['filename']}.{$extensions[$size]}";
	    	$full_path = "{$fnmHashed['path']}/{$size}_{$filename}";
    		$movResult = move_uploaded_file($_FILES[$size]['tmp_name'], $full_path);
    		if ($movResult) {
    			$apiResult['avatars'][$size] = getAvatarUrl('', '', $filename, $size);
    			continue;
    		}
        }
        // update database
        if ($identity_id) {
        	$apiResult['type'] 		  = 'identity';
        	$apiResult['identity_id'] = $identity_id;
        	$dbResult = $modIdentity->updateAvatarById($identity_id, $filename);
        } else {
        	$apiResult['type']        = 'user';
        	$apiResult['user_id'] 	  = $user_id;
        	$dbResult = $modUser->updateAvatarById($user_id, $filename);
        }
      	if ($dbResult) {
      		apiResponse($apiResult);
      	}
      	apiError(500, 'error_saving_image', 'Error while saving image.');
	}


	public function doRender() {
		// init requirement
        $curDir    = dirname(__FILE__);
        $resDir    = "{$curDir}/../../../default_avatar_portrait/";
        $defImg    = "{$curDir}/../../../eimgs/web/80_80_default.png";
        $icoFile   = "{$curDir}/../../../static/images/icons_v2.png";
        require_once "{$curDir}/../../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
		// config
		$config    = array(
			'width'          => 40,
			'height'         => 40,
			'host-right'     => 1,
			'host-top'       => 1,
			'host-width'     => 10,
			'host-height'    => 10,
			'host-sprite-x'  => 44,
			'host-sprite-y'  => 44,
			'mates-left'     => 1,
			'mates-top'      => 1,
			'mates-width'    => 10,
			'mates-height'   => 10,
			'mates-max'      => 9,
			'mates-sprite-x' => 44,
			'mates-sprite-y' => 44,
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
			$image  = ImageCreateFromPNG($defImg);
		}
		// resize source image
		$rqs_width  = (int) $params['width'];
		$rqs_height = (int) $params['height'];
		$config['width']  = $rqs_width  > 0 ? $rqs_width  : $config['width'];
		$config['height'] = $rqs_height > 0 ? $rqs_height : $config['height'];
		$image = $objLibImage->rawResizeImage($image, $config['width'], $config['height']);
		// draw alpha overlay
		$alpha = (float) $params['alpha'];
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
		// load icons image file
		$imgIcons = ImageCreateFromPNG($icoFile);
        imagealphablending($imgIcons, true);
        imagesavealpha($imgIcons, true);
        imagefill($imgIcons, 0, 0, imagecolorallocatealpha($imgIcons, 0, 0, 0, 127));
        // draw host icon
        if (strtolower($params['ishost']) === 'true') {
        	$config['host-left'] = $config['width'] - $config['host-width'] - $config['host-right'];
			imagecopyresampled(
				$image, $imgIcons,
				$config['host-left'],      $config['host-top'],
				$config['host-sprite-x'],  $config['host-sprite-y'],
				$config['host-width'],     $config['host-height'],
				$config['host-width'],     $config['host-height']
			);
		}
		// draw mates-someone icon
		$mates = (int) $params['mates'];
		if ($mates > 0) {
			$mates = $mates > $config['mates-max'] ? $config['mates-max'] : $mates;
			$config['mates-sprite-x'] += $config['mates-width'] * $mates;
			imagecopyresampled(
				$image, $imgIcons,
				$config['mates-left'],     $config['mates-top'],
				$config['mates-sprite-x'], $config['mates-sprite-y'],
				$config['mates-width'],    $config['mates-height'],
				$config['mates-width'],    $config['mates-height']
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
