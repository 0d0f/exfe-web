<?php

$jsname = preg_replace(
    '/{{sha1}}/',
    $frontConfigData->mobile->sha1,
    JS_DEBUG ? $frontConfigData->mobile->files->dev : $frontConfigData->mobile->files->pro
);

$cssname = $frontConfigData->css->exfemobilemin;

echo "  <script>\n";
include 'ftconfig.php';
echo "window._ENV_.JSFILE = '${jsname}';\n";
echo "window._ENV_.CSSFILE = '${cssname}';\n";
echo 'window._ENV_.smith_id = ' . $this->getVar('smith_id') . ';' . "\n";
echo 'window._ENV_.exfee_id = ' . $this->getVar('exfee_id') . ';' . "\n";
echo "  </script>\n";
echo "<script src='/static/js/mobiledirector/0.0.1/mobiledirector" . (JS_DEBUG ? '' : ".min") . ".js'></script>";
echo "<script src='/static/js/" . $jsname . "'</script>";
echo "\n";

// Google Analytics
include 'google_analytics.php';

if (SITE_URL !== 'https://exfe.com') {
    require 'jsdev.php';
}
