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
    ['util',          '0.2.6'],
    ['widget',        '0.0.4'],
    ['jquery',        '1.8.2'],
    ['store',         '1.3.5'],
    ['marked',        '0.2.5'],
    ['handlebars',    '1.0.7'],
    ['handlebarsext', '0.0.1'],
    ['jqfocusend',    '0.0.2'],
    ['jqoffset',      '0.0.2'],
    ['jqmousewheel',  '3.1.3'],
    ['jqdndsortable', '0.0.2'],
    ['jqresize',      '0.0.1'],
    ['tween',         '10.0.0'],
]);

echo "  <script>\n";
include 'ftconfig.php';
echo "  </script>\n";

addScript([
    ['humantime',     '0.0.8'],
    ['api',           '0.0.9'],
    ['dialog',        '0.0.4'],
    ['typeahead',     '0.0.2'],
    ['panel',         '0.0.2'],
    ['xidentity',     '0.0.3'],
    ['xdialog',       '0.1.13'],
    ['datepanel',     '0.1.3'],
    ['mappanel',      '0.0.8'],
    ['global',        '0.0.5'],
    ['photox',        '0.0.1'],
    ['countrycodes',  '0.0.1'],
    ['phonepanel',    '0.0.1'],
    ['photoxwidget',  '0.0.1'],
    ['mnemosyne',     '0.0.1']
]);

// profile
addScript([
    ['filehtml5',     '0.0.1'],
    ['uploader',      '0.0.3'],
    ['profile',       '0.1.18'],
    ['user',          '0.0.8'],
]);

echo "  <script src=\"/static/_cross.js?" . STATIC_CODE_TIMESTAMP . "\"></script>\n";

// lightsaber
addScript([
    ['lightsaber',    '0.0.5'],
    ['middleware',    '0.0.10'],
    ['routes',        '0.3.12'],
    ['app',           '0.2.9'],
]);

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
