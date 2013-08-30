<?php

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
                case 'phone':
                    break;
                default:
                    apiError(400, 'provider_error', 'Can not update avatars of this kind of identities.');
            }
        }
        // check images
        $sizes  = ['original' => '', '320_320' => '', '80_80' => ''];
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
                        apiError(400, "error_image_format", "Error {$i} image format.");
                }
            } else if ($i !== 'original' && $sizes['original']) {
                $intImg++;
                $sizes[$i] = 'new';
            }
        }
        if ($intImg && $intImg !== count($sizes)) {
            apiError(400, "missing_original_sizes", "Original image must be provided.");
        }

        // save file
        require_once dirname(__FILE__) . "/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        $apiResult = ['avatar' => []];
        $iconFiles = [];
        if ($intImg) {
            // hash filename
            if (!($fnmHashed = getHashedFilePath(Uniqid()))) {
                apiError(500, 'error_saving_image', 'Error while saving image.');
            }
            $originFilename  = '';
            foreach ($sizes as $i => $item) {
                if ($item === 'new') {
                    $filename  = "{$fnmHashed['filename']}.png";
                    $full_path = "{$fnmHashed['path']}/{$i}_{$filename}";
                    $size      = explode('_', $i);
                    $movResult = $objLibImage->resizeImage(
                        $originFilename, $size[0], $size[1], $full_path
                    );
                } else {
                    $filename  = "{$fnmHashed['filename']}.{$item}";
                    $full_path = "{$fnmHashed['path']}/{$i}_{$filename}";
                    if ($i === 'original') {
                        $originFilename = $full_path;
                    }
                    $movResult = isset($_FILES[$i])
                               ? move_uploaded_file($_FILES[$i]['tmp_name'], $full_path)
                               : false;
                }
                if ($movResult) {
                    $apiResult['avatar'][$i] = $filename;
                    $iconFiles[$i]           = $filename;
                    continue;
                }
                apiError(500, 'error_saving_image', 'Error while saving image.');
            }
            foreach ($apiResult['avatar'] as $aI => $aItem) {
                $url = implode('/', [IMG_URL, substr($aItem, 0, 1), substr($aItem, 1, 2)]) . '/';
                $apiResult['avatar'][$aI] = "{$url}{$aI}_{$aItem}";
            }
        } else {
            if ($identity->provider === 'email') {
                $apiResult['avatar'] = $modIdentity->getGravatarByExternalUsername(
                    $identity->external_username
                );
            }
        }

        // update database
        if ($identity_id) {
            $apiResult['type']        = 'identity';
            $apiResult['identity_id'] = $identity_id;
            $dbResult = $modIdentity->updateAvatarById($identity_id, $iconFiles);
        } else {
            $apiResult['type']        = 'user';
            $apiResult['user_id']     = $user_id;
            $dbResult = $modUser->updateAvatarById($user_id, $iconFiles);
        }

        // get default avatar
        if (!$apiResult['avatar']) {
            $default_avatar
          = $identity_id
          ? $modIdentity->getIdentityById($identity_id, $user_id)->avatar
          : $modUser->getUserById($user_id)->avatar;
            $apiResult['avatar']  = $default_avatar;
        }

        // return
        if ($dbResult) {
            $apiResult['avatars'] = $apiResult['avatar'];
            apiResponse($apiResult);
        }
        apiError(500, 'error_saving_image', 'Error while saving image.');
    }


    public function doRender() {
        // init models
        $modUser = $this->getModelByName('user');
        // init requirement
        $curDir    = dirname(__FILE__);
        $resDir    = "{$curDir}/../../default_avatar_portrait/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config      = [
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
            'period'         => 604800, // 60 * 60 * 24 * 7
        ];
        // header
        imageHeader();
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
        // get source image
        $params = $this->params;
        $params['url'] = $_GET['url'] ? base64_url_decode($_GET['url']) : '';
        $image  = null;
        if (strpos($params['url'], API_URL) !== false) {
            $arrQuery = explode('=', explode('&', parse_url($params['url'], PHP_URL_QUERY))[0]);
            $image    = isset($arrQuery[1]) && $arrQuery[1]
                      ? $modUser->makeDefaultAvatar(urldecode($arrQuery[1]), true) : null;
        } else {
            $image    = httpKit::fetchImageExpress($params['url']);
        }
        // get fallback image
        if (!$image) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // get resolution
        switch (($resolution = (float) @$params['resolution'])) {
            case 1:
            case 2:
                break;
            default:
                $resolution = 1;
        }
        if ($resolution > 1) {
            $icoFile = "{$curDir}/../../static/img/icons@{$resolution}x.png";
        } else {
            $icoFile = "{$curDir}/../../static/img/icons.png";
        }
        foreach ($config as $cI => $cItem) {
            if ($cI !== 'mates-max') {
                $config[$cI] *= $resolution;
            }
        }
        // resize source image
        $rqs_width  = (int) $params['width']  * $resolution;
        $rqs_height = (int) $params['height'] * $resolution;
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
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 7) . ' GMT');
        imagepng($image);
        $objLibImage->setImageCache(IMG_CACHE_PATH, $this->route, $image);
        imagedestroy($image);
    }

}
