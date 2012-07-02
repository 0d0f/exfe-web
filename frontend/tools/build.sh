#!/bin/sh
ROOT="."
CSS="$ROOT/css"
LESS="$ROOT/less"
PAGE="page"

LESSS=('reset')

for f in $LESSS
do
  echo "$f"
  lessc "$LESS/$f.less" > "$CSS/$f.css"
done
