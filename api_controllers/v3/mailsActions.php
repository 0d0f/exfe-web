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

        // grep inputs
        $params     = $this->params;
        $background = @base64_url_decode()$params['background'];
        echo $background;
    }

}
