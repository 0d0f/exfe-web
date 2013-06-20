<?php

$frontConfigFile = 'static/package.json';
$frontConfigJson = file_get_contents($frontConfigFile);
$frontConfigData = json_decode($frontConfigJson);

if (!$frontConfigData) {
    header('location: /500');
    return;
}

$filename = preg_replace(
    '/{{version}}/',
    $frontConfigData->mobile->version,
    JS_DEBUG ? $frontConfigData->mobile->files->dev : $frontConfigData->mobile->files->pro
);

echo "  <script>\n";
include 'ftconfig.php';
echo "window._ENV_.JSFILE = '${filename}'";
echo "  </script>\n";
echo "<script src='/static/js/mobiledirector/0.0.1/mobiledirector" . (JS_DEBUG ? '' : ".min") . ".js' async></script>";
echo "\n";

// Google Analytics
include 'google_analytics.php';

if (SITE_URL !== 'https://exfe.com') {
    require 'jsdev.php';
}
