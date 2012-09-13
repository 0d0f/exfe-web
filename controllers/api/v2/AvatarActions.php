<?php
set_time_limit(3);

class AvatarActions extends ActionController {

    public function doDefault() {
        $params  = $this->params;
        $modUser = $this->getModelByName('user');
        $modUser->makeDefaultAvatar($params['name']);
    }


    public function doUpdate() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
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
        $sizes  = ['original' => '', '80_80' => ''];
        $intImg = 0;
        foreach ($sizes as $i => $item) {
            if (isset($_FILES[$i])) {
                $intImg++;
                $arrExpName = explode('/', $_FILES[$i]['type']);
                $arrExpName = $arrExpName[0]
                            ? $arrExpName
                            : explode('.', $_FILES[$i]['name']);
                switch ($sizes[$i] = strtolower(array_pop($arrExpName))) {
                    case 'png':
                    case 'gif':
                    case 'bmp':
                    case 'jpg':
                    case 'jpeg':
                        break;
                    default:
                        apiError(400, "error_{$i}_image_format", "Error {$i} size image format.");
                }
            }
        }
        if ($intImg && $intImg !== count($sizes)) {
            apiError(400, "missing_some_sizes", "All size of images must be provided.");
        }

        // save file
        $apiResult = array('avatars' => array());
        if ($intImg) {
            // hash filename
            if (!($fnmHashed = getHashedFilePath(Uniqid()))) {
                apiError(500, 'error_saving_image', 'Error while saving image.');
            }
            foreach ($sizes as $i => $item) {
                $filename  = "{$fnmHashed['filename']}.{$item}";
                $full_path = "{$fnmHashed['path']}/{$i}_{$filename}";
                $movResult = isset($_FILES[$i])
                           ? move_uploaded_file($_FILES[$i]['tmp_name'], $full_path)
                           : false;
                if ($movResult) {
                    $apiResult['avatars'][$i] = getAvatarUrl($filename, $i);
                    continue;
                }
                apiError(500, 'error_saving_image', 'Error while saving image.');
            }
        } else {
            $filename = '';
        }

        // update database
        if ($identity_id) {
            $apiResult['type']        = 'identity';
            $apiResult['identity_id'] = $identity_id;
            $dbResult = $modIdentity->updateAvatarById($identity_id, $filename);
        } else {
            $apiResult['type']        = 'user';
            $apiResult['user_id']     = $user_id;
            $dbResult = $modUser->updateAvatarById($user_id, $filename);
        }

        // get default avatar
        if (!$intImg) {
            $default_avatar
          = $identity_id
          ? $modIdentity->getIdentityById($identity_id, $user_id)->avatar_filename
          : $modUser->getUserById($user_id)->avatar_filename;
            foreach ($sizes as $i => $item) {
                $apiResult['avatars'][$i] = $default_avatar;
            }
        }

        // return
        if ($dbResult) {
            apiResponse($apiResult);
        }
        apiError(500, 'error_saving_image', 'Error while saving image.');
    }


    public function doRender() {
        // init models
        $modUser = $this->getModelByName('user');
        // init requirement
        $curDir    = dirname(__FILE__);
        $resDir    = "{$curDir}/../../../default_avatar_portrait/";
        $icoFile   = "{$curDir}/../../../static/img/icons.png";
        require_once "{$curDir}/../../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config      = array(
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
        $params['url'] = $params['url'] ? base64_decode("{$params['url']}==") : '';
        $image  = null;
        if (strpos($params['url'], API_URL) !== false) {
            $arr_url = explode('=', $params['url']);
            $image   = isset($arr_url[1]) && $arr_url[1]
                     ? $modUser->makeDefaultAvatar($arr_url[1], true) : null;
        } else {
            if (DEBUG) {
                error_log("Start loading image: {$params['url']}");
            }
            $image   = $objLibImage->loadImageByUrl($params['url']);
            if (DEBUG) {
                error_log("Finished loading image: {$params['url']}");
            }
        }
        // get fall back image
        if (!$image) {
            header('HTTP/1.1 404 Not Found');
            return;
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
