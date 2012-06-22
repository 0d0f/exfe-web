#!/bin/sh

case "`uname -s`" in
    'Darwin')
        sed -i '' -e "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" config.php
    ;;
    'FreeBSD')
        sed -i '' -e "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" config.php
    ;;
    *)
        sed -i "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" config.php
esac

echo 'done :)'
