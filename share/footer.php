<?php

include 'copyright.php';

echo "  <script>\n";
include 'ftconfig.php';
echo "  </script>\n";
echo "\n";

$frontConfigFile = 'static/package.json';
$frontConfigJson = file_get_contents($frontConfigFile);
$frontConfigData = json_decode($frontConfigJson);

if (!$frontConfigData) {
    header('location: /500');
    return;
}

if (JS_DEBUG) {
    foreach ($frontConfigData->desktop->dependencies as $script)  {
        addScript([[$script->name, $script->version]]);
    }
} else {
    $filename = preg_replace(
        '/{{version}}/',
        $frontConfigData->desktop->version,
        $frontConfigData->desktop->files->pro
    );
    rawAddScript($filename);
}

// Google Analytics
include 'google_analytics.php';

if (SITE_URL !== 'https://exfe.com') {
    require 'jsdev.php';
}
