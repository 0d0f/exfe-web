<?php

class CrossesActions extends ActionController {

    public function doIndex() {

    }


    public function doImage() {
        // init requirement
        $params = $this->params;
        $curDir = dirname(__FILE__);
        $bkgDir    = "{$curDir}/../../static/img/xbg/";
        require_once "{$curDir}/../../lib/httpkit.php";
        require_once "{$curDir}/../../xbgutilitie/libimage.php";
        $objLibImage = new libImage;
        // config
        $config = [
            'width'        => 640,
            'height'       => 320,
            'jpeg-quality' => 100,
            'period'       => 604800, // 60 * 60 * 24 * 7
        ];

        $modExfee    = $this->getModelByName('Exfee');
        $crossHelper = $this->getHelperByName('cross');
        $invitation  = $modExfee->getRawInvitationByToken(@$params['xcode']);
        $cross       = $crossHelper->getCross(@$invitation['cross_id']);
        if ($invitation && $cross
         && $invitation['cross_id']    === (int) $params['id']
         && $invitation['state']       !== 4
         && $cross->attribute['state'] !== 'deleted') {
            // header
            header('Pragma: no-cache');
            header('Cache-Control: no-cache');
            header('Content-Transfer-Encoding: binary');
            header('Content-type: image/jpeg');
            // get background
            $background = 'default.jpg';
            foreach ($cross->widget as $widget) {
                if ($widget->type === 'Background') {
                    $background = $widget->image;
                }
            }
            $backgroundFile  = @ file_get_contents("{$bkgDir}{$background}");
            $backgroundImage = @ imagecreatefromstring($backgroundFile);
            $backgroundImage = $objLibImage->rawResizeImage(
                $backgroundImage, $config['width'], $config['height']
            );
            imagepng($backgroundImage);
        }
    }

}
