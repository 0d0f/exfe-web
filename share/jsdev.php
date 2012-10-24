<?php
$jsdev = <<<EOD
<script>
  var e = document.createElement('div');
  e.style.width = '100%';
  e.style.position = 'absolute';
  e.style.top = 0;
  e.style.zIndex = 10000;
  e.style.borderTop = '6px solid #D32232';
  e.style.borderBottom = '5px solid {{JS_COLOR}}';
  document.body.appendChild(e);
</script>s
EOD;

echo str_replace('{{JS_COLOR}}', JS_COLOR, $jsdev);
