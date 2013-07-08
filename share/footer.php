<?php

echo "  <script>\n";
include 'ftconfig.php';
echo "  </script>\n";
echo "\n";

if (JS_DEBUG) {
    foreach ($frontConfigData->desktop->dependencies as $script)  {
        addScript([[$script->name, $script->version]]);
    }
} else {
    $filename = preg_replace(
        '/{{sha1}}/',
        $frontConfigData->desktop->sha1,
        $frontConfigData->desktop->files->pro
    );
    rawAddScript($filename);
}

echo "<script src='/static/js/exroutex/0.1.64/exroutex.js'></script>";

// Google Analytics
include 'google_analytics.php';

if (SITE_URL !== 'https://exfe.com') {
    require 'jsdev.php';
}
