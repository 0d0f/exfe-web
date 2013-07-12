#!/usr/bin/env php
<?php
// by @leaskh
error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/xbgutilitie/libimage.php';

$objLibImage = new libImage;

function makeImgs($path, $file) {
    global $objLibImage;
    if (preg_match('/^original_.*$/i', $file)) {
        $source   = "{$path}/{$file}";
        echo "Checking 320 version of image : {$source} ";
        $pureName = preg_replace('/^original_(.*)\.[^\.]+$/i', '$1', $file);
        $target   = "{$path}/320_320_{$pureName}.jpg";
        if (file_exists($target)) {
            echo "[EXISTS]";
        } else {
            echo "[NOT EXISTS]";
            if (@$objLibImage->resizeImage($source, 320, 320, $target)) {
                echo ' [OK]';
            } else {
                echo ' [FAILED]';
            }
        }
        echo "\n";
    }
}

function checkImgs($path) {
    // echo "Checking folder: {$path}...\n";
    $files = array_diff(scandir($path), ['.', '..']);
    foreach ($files as $file) {
        $curPath = "{$path}/{$file}";
        is_dir($curPath) ? checkImgs($curPath) : makeImgs($path, $file);
    }
}

checkImgs('eimgs');
