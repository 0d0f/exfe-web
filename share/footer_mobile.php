<?php

function addScript($scripts) {
    $min = JS_DEBUG ? '' : '.min';
    foreach ($scripts as $item) {
        echo "  <script src=\"/static/js/{$item[0]}/{$item[1]}/{$item[0]}{$min}.js?" . STATIC_CODE_TIMESTAMP . "\"></script>\n";
    }
}

echo "\n";
addScript([
    ['common',        '0.0.3'],
    ['zepto',         '1.0.0'],
    ['store',         '1.3.5'],
    ['humantime',     '0.0.6'],
]);

echo "  <script>\n";
include 'ftconfig.php';
echo " var sms_token = " . json_encode($this->getVar('sms_token')) . ";\n";
echo "  </script>\n";

addScript([
    ['mobile',        '0.0.1'],
]);

// Google Analytics
if (SITE_URL === 'https://exfe.com') {
echo <<<EOT
<script type="text/javascript">
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
