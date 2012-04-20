#!/bin/sh
sed -i '' -e "s/.*STATIC_CODE_TIMESTAMP.*/define\("STATIC_CODE_TIMESTAMP", `date +%s`\)\;/" config.php
echo 'done :)'
