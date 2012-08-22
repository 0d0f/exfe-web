<?php

function addScript($scripts) {
    $mini = DEBUG ? '' : '.min';
    foreach ($scripts as $item) {
        echo "  <script src=\"/static/js/{$item[0]}/{$item[1]}/{$item[0]}{$mini}.js?" . STATIC_CODE_TIMESTAMP . "\"></script>\n";
    }
}


echo "\n";
addScript([
    ['common',        '0.0.1'],
    ['class',         '0.0.1'],
    ['emitter',       '0.0.1'],
    ['base',          '0.0.1'],
    ['bus',           '0.0.1'],
    ['rex',           '0.0.1'],
    ['util',          '0.0.1'],
    ['widget',        '0.0.1'],
    ['jquery',        '1.8.0'],
    ['moment',        '1.6.2'],
    ['store',         '1.3.3'],
    ['marked',        '0.2.5'],
    ['handlebars',    '1.0.0'],
    ['handlebarsext', '0.0.1'],
    ['jqfocusend',    '0.0.1'],
    ['jqoffset',      '0.0.1'],
]);

echo "  <script>\n";
include 'ftconfig.php';
echo "  </script>\n";

addScript([
    ['api',           '0.0.1'],
    ['dialog',        '0.0.1'],
    ['typeahead',     '0.0.1'],
    ['xidentity',     '0.0.1'],
    ['xdialog',       '0.0.1'],
    ['global',        '0.0.1'],
]);

// profile
addScript([
    ['filehtml5',     '0.0.1'],
    ['uploader',      '0.0.1'],
    ['profile',       '0.0.1'],
]);

// cross
addScript([
    ['placepanel',    '0.0.1'],
    ['user',          '0.0.1'],
]);
echo "  <script src=\"/static/_cross.js?" . STATIC_CODE_TIMESTAMP . "\"></script>\n";

// lightsaber
addScript([
    ['lightsaber',    '0.0.1'],
    ['middleware',    '0.0.1'],
    ['routes',        '0.0.1'],
    ['app',           '0.0.1'],
]);

if (DEBUG) {
  echo "<script>document.getElementById('app-menubar').style.border = '5px solid red';</script>";
}
