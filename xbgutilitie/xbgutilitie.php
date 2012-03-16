<?php
error_reporting(E_ALL ^ E_NOTICE);

$curDir = dirname(__FILE__);
require_once "{$curDir}/../common.php";
require_once "{$curDir}/../DataModel.php";
require_once "{$curDir}/libfilesystem.php";
require_once "{$curDir}/libimage.php";

class xbgUtilitie extends DataModel {

    protected $objLibFileSystem = null;

    protected $objLibImage      = null;

    protected $fmImagePath      = '';

    protected $toImagePath      = '';

    protected $toSpecification  = array(
        'web' => array('type' => 'jpg', 'width' => 880, 'height' => 300, 'quality' => 90),
        'ios' => array('type' => 'jpg', 'width' => 640, 'height' => 200, 'quality' => 90),
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
                        // resize
                        $dfImage = $this->objLibImage->resizeImage(
                            $fmFullpath, $sItem['width'], $sItem['height']
                        );
                        // mask
                        $mask    = "{$curDir}/res/{$sI}_mask.png";
                        $msImage = ImageCreateFromPNG($mask);
                        imagecopy($dfImage, $msImage, 0, 0, 0, 0,
                                  $sItem['width'], $sItem['height']);
                        // write
                        $toFullpath = "{$this->toImagePath}/{$toFileName}_{$sI}.jpg";
                        ImageJpeg($dfImage, $toFullpath, $sItem['quality']);
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
