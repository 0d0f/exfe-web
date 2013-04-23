#!/bin/sh

ROOT_DIR=$(git rev-parse --show-toplevel)

case "`uname -s`" in
    'Darwin')
        sed -i '' -e "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" $ROOT_DIR/config.php
    ;;
    'FreeBSD')
        sed -i '' -e "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" $ROOT_DIR/config.php
    ;;
    *)
        sed -i "s/.*STATIC_CODE_TIMESTAMP.*/define\(\"STATIC_CODE_TIMESTAMP\", `date +%s`\)\;/" $ROOT_DIR/config.php
esac

unset ROOT_DIR

echo '😃  done!';
