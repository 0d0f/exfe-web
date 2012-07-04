<?php
error_reporting(E_ALL ^ E_NOTICE);

$curDir = dirname(__FILE__);
require_once "{$curDir}/../common.php";
require_once "{$curDir}/../DataModel.php";
require_once "{$curDir}/libimage.php";

class xbgUtilitie extends DataModel {

    protected $objLibFileSystem = null;

    protected $objLibImage      = null;

    protected $fmImagePath      = '';

    protected $toImagePath      = '';

    protected $toSpecification  = array(
        'web' => array('type'       => 'jpg',
                       'quality'    => 90,
                       'width'      => 882,
                       'height'     => 936,
                       'img-width'  => 880,
                       'img-height' => 495),
        'ios' => array('type'       => 'jpg',
                       'quality'    => 90,
                       'width'      => 640,
                       'height'     => 681,
                       'img-width'  => 640,
                       'img-height' => 360),
    );


    public function __construct() {
        // init
        global $curDir;
        $this->objLibFileSystem = new libFileSystem;
        $this->objLibImage      = new libImage;
        $this->fmImagePath      = "{$curDir}/../eimgs/xbgimage_origin";
        $this->toImagePath      = "{$curDir}/../eimgs/xbgimage";

        // build dir
        @mkdir($this->fmImagePath, 0777, true);
        @mkdir($this->toImagePath, 0777, true);
    }


    public function make() {
        // init
        global $curDir;
        echo "Source folder: {$this->fmImagePath}\r\n"
           . "Destination folder: {$this->toImagePath}\r\n\r\n";

        // del old images
        echo "Del old images...\r\n";
        $sql = "DELETE FROM `background`;";
        $this->query($sql);
        $this->objLibFileSystem->emptyFolder($this->toImagePath);
        echo "\r\n";

        // processing images
        echo "Processing images...\r\n";
        $ph  = opendir($this->fmImagePath);
        $num = 0;
        while (($file = readdir($ph))) {
            if ($file !== '.' && $file !== '..' && $file !== '.DS_Store') {
                if (!is_dir($fmFullpath = "{$this->fmImagePath}/{$file}")) {
                    echo ++$num . ": {$fmFullpath}";
                    $toFileName  = preg_replace('/^.*\/([^\/]*)\.[^\.]*$/', '$1', $fmFullpath);
                    foreach ($this->toSpecification as $sI => $sItem) {
                        // create image
                        $dtImage = imagecreatetruecolor($sItem['width'], $sItem['height']);
                        // resize
                        $dfImage = $this->objLibImage->resizeImage(
                            $fmFullpath, $sItem['img-width'], $sItem['img-height']
                        );
                        // copy
                        imagecopyresampled(
                            $dtImage, $dfImage, 0, 0, 0, 0,
                            $sItem['img-width'], $sItem['img-height'],
                            $sItem['img-width'], $sItem['img-height']
                        );
                        // mask
                        $mask    = "{$curDir}/res/{$sI}_mask.png";
                        $msImage = ImageCreateFromPNG($mask);
                        imagecopyresampled(
                            $dtImage, $msImage, 0, 0, 0, 0,
                            $sItem['width'], $sItem['height'],
                            $sItem['width'], $sItem['height']);
                        // write
                        $toFullpath = "{$this->toImagePath}/{$toFileName}_{$sI}.jpg";
                        ImageJpeg($dtImage, $toFullpath, $sItem['quality']);
                        // output
                        echo " [$sI]";
                    }
                    // registration
                    $sql = "INSERT INTO `background` SET `image` = '{$toFileName}';";
                    $this->query($sql);
                    // output
                    echo "\r\n";
                }
            }
        }
        echo "Total {$num} item(s) processed.\r\n\r\n";

        // return
        echo "All done.\r\n";
        return true;
    }

}

$objXbgUtilitie = new xbgUtilitie;
array_shift($argv);

switch (strtolower($argv[0])) {
    case 'make':
        $objXbgUtilitie->make();
        break;
    default:
        echo "xbgUtilitie: invalid option -- '{$argv[0]}'\r\n";
        return false;
}
