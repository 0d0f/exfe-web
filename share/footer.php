<?php

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
