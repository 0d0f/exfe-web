<?php
error_reporting(E_ALL ^ E_NOTICE);

$curDir = dirname(__FILE__);
require_once "{$curDir}/../common.php";
require_once "{$curDir}/../DataModel.php";


class xbgUtilitie extends DataModel {

    public function __construct() {
        // init
        global $curDir;
        $this->imagePath = "{$curDir}/../static/img/xbg";
    }


    public function update() {
        // init
        global $curDir;
        echo "Source folder: {$this->imagePath}\r\n\r\n";

        // del old images
        echo "Del old images...\r\n\r\n";
        $this->query("DELETE FROM `background`");

        // processing images
        echo "Processing images...\r\n";
        $ph  = opendir($this->imagePath);
        $num = 0;
        while (($file = readdir($ph))) {
            if ($file !== '.' && $file !== '..' && $file !== '.DS_Store') {
                if (!is_dir($fullpath = "{$this->imagePath}/{$file}")) {
                    echo ++$num . ": {$fullpath}\r\n";
                    $fileName  = preg_replace('/^.*\/([^\/]*)$/', '$1', $fullpath);
                    // registration
                    $this->query(
                        "INSERT INTO `background` SET `image` = '{$fileName}'"
                    );
                }
            }
        }
        echo "Total {$num} item(s) processed.\r\n\r\n";

        // return
        echo "😃 All done.\r\n";
        return true;
    }

}


$objXbgUtilitie = new xbgUtilitie;
array_shift($argv);

switch (strtolower($argv[0])) {
    case 'update':
        $objXbgUtilitie->update();
        break;
    default:
        echo "xbgUtilitie: invalid option -- '{$argv[0]}'\r\n";
        return false;
}
