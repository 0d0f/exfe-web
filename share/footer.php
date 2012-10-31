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
    ['class',         '0.0.1'],
    ['emitter',       '0.0.2'],
    ['base',          '0.0.2'],
    ['bus',           '0.0.2'],
    ['rex',           '0.0.2'],
    ['util',          '0.0.2'],
    ['widget',        '0.0.2'],
    ['jquery',        '1.8.2'],
    ['moment',        '1.6.2'],
    ['store',         '1.3.4'],
    ['marked',        '0.2.5'],
    ['handlebars',    '1.0.0'],
    ['handlebarsext', '0.0.1'],
    ['jqfocusend',    '0.0.2'],
    ['jqoffset',      '0.0.2'],
    ['jqdndsortable', '0.0.1'],
]);

echo "  <script>\n";
include 'ftconfig.php';
echo "  </script>\n";

addScript([
    ['eftime',        '0.0.3'],
    ['api',           '0.0.2'],
    ['dialog',        '0.0.3'],
    ['typeahead',     '0.0.2'],
    ['xidentity',     '0.0.1'],
    ['xdialog',       '0.0.3'],
    ['global',        '0.0.4'],
]);

// profile
addScript([
    ['filehtml5',     '0.0.1'],
    ['uploader',      '0.0.3'],
    ['profile',       '0.0.5'],
]);

// cross
addScript([
    ['placepanel',    '0.0.1'],
    ['user',          '0.0.6'],
]);
echo "  <script src=\"/static/_cross.js?" . STATIC_CODE_TIMESTAMP . "\"></script>\n";

// lightsaber
addScript([
    ['lightsaber',    '0.0.4'],
    ['middleware',    '0.0.6'],
    ['routes',        '0.0.7'],
    ['app',           '0.0.4'],
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
