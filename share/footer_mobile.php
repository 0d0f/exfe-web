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
echo "<script src='/static/js/mobiledirector/0.0.1/mobiledirector.min.js' async></script>";
echo "\n";

// Google Analytics
if (SITE_URL === 'https://exfe.com') {
echo <<<EOT
<script>
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-31794223-2']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
EOT;
}

if (SITE_URL !== 'https://exfe.com') {
    require 'jsdev.php';
}
