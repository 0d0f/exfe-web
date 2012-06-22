PREFIX = .
CHECK = \033[32m✔\033[39m
JS_DIR = ${PREFIX}/js
CSS_DIR = ${PREFIX}/css
IMG_DIR = ${PREFIX}/img
LESS_DIR = ${PREFIX}/less

EXFE_LESS = ${LESS_DIR}/exfe/lib/exfe.less
EXFE_LESS_DEV = ${LESS_DIR}/exfe/lib/exfe_dev.less
EXFE_CSS = ${CSS_DIR}/exfe.css
EXFE_CSS_DEV = ${CSS_DIR}/exfe_dev.css
EXFE_MIN_CSS = ${CSS_DIR}/exfe.min.css
EXFE_MIN_CSS_DEV = ${CSS_DIR}/exfe_dev.min.css

STATIC = ${PREFIX}/../../static/1B
STATIC_IMG_DIR = ${STATIC}/img
STATIC_CSS_DIR = ${STATIC}/css
STATIC_JS_DIR = ${STATIC}/js

#
# BUILD
#

all: build

start:
	@echo -e '\r'
	@echo "Building Exfe JS/CSS ..."

build: start CSS

CSS:
	@recess --compile ${EXFE_LESS} > ${EXFE_CSS}
	@recess --compress ${EXFE_LESS} > ${EXFE_MIN_CSS}
	@recess --compile ${EXFE_LESS_DEV} > ${EXFE_CSS_DEV}
	@recess --compress ${EXFE_LESS_DEV} > ${EXFE_MIN_CSS_DEV}
	@echo "Compiling LESS with Recess...                            ${CHECK} Done"

sync:
	@cp ${EXFE_MIN_CSS} ${STATIC_CSS_DIR}
	@echo "Sync css files...                                        ${CHECK} Done"
	@cp -R ${JS_DIR}/* ${STATIC_JS_DIR}
	@echo "Sync js files...                                         ${CHECK} Done"
	@cp -R ${IMG_DIR}/* ${STATIC_IMG_DIR}
	@echo "Sync image files...                                      ${CHECK} Done"
#
# start static files server
#
#
up:
	@node ./tools/server.js > /dev/null &

down:
	@killall 'node'


#
# export project
#
#
export:
	@git archive master | bzip2 >source-tree.tar.bz2

.PHONY: build CSS up down export
