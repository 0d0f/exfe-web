#NOT READY FOR Xstrap

BOOTSTRAP = ./docs/assets/css/bootstrap.css
BOOTSTRAP_LESS = ./less/bootstrap.less
BOOTSTRAP_RESPONSIVE = ./docs/assets/css/bootstrap-responsive.css
BOOTSTRAP_RESPONSIVE_LESS = ./less/responsive.less
LESS_COMPRESSOR ?= `which lessc`
WATCHR ?= `which watchr`

#
# BUILD DOCS
#

docs: bootstrap
	rm docs/assets/bootstrap.zip
	zip -r docs/assets/bootstrap.zip bootstrap
	rm -r bootstrap
	lessc ${BOOTSTRAP_LESS} > ${BOOTSTRAP}
	lessc ${BOOTSTRAP_RESPONSIVE_LESS} > ${BOOTSTRAP_RESPONSIVE}
	node docs/build
	cp img/* docs/assets/img/
	cp js/*.js docs/assets/js/
	cp js/tests/vendor/jquery.js docs/assets/js/

#
# BUILD SIMPLE BOOTSTRAP DIRECTORY
# lessc & uglifyjs are required
#

IMG:
	mkdir -p bootstrap/img
	cp img/* bootstrap/img/

JS:
	mkdir -p bootstrap/js

#
# compile css
#
CSS:
	mkdir -p bootstrap/css
	lessc ${BOOTSTRAP_LESS} > bootstrap/css/bootstrap.css
	lessc --compress ${BOOTSTRAP_LESS} > bootstrap/css/bootstrap.min.css
	lessc ${BOOTSTRAP_RESPONSIVE_LESS} > bootstrap/css/bootstrap-responsive.css
	lessc --compress ${BOOTSTRAP_RESPONSIVE_LESS} > bootstrap/css/bootstrap-responsive.min.css
	cp bootstrap/css/* css/

bootstrap: IMG JS CSS
	cat js/bootstrap-transition.js js/bootstrap-alert.js js/bootstrap-button.js js/bootstrap-carousel.js js/bootstrap-collapse.js js/bootstrap-dropdown.js js/bootstrap-modal.js js/bootstrap-tooltip.js js/bootstrap-popover.js js/bootstrap-scrollspy.js js/bootstrap-tab.js js/bootstrap-typeahead.js > js/bootstrap.js
	uglifyjs -nc js/bootstrap.js > js/bootstrap.min.tmp.js
	echo "/**\n* Bootstrap.js by @fat & @mdo\n* Copyright 2012 Twitter, Inc.\n* http://www.apache.org/licenses/LICENSE-2.0.txt\n*/" > js/copyright.js
	cat js/copyright.js js/bootstrap.min.tmp.js > js/bootstrap.min.js
	rm js/copyright.js js/bootstrap.min.tmp.js
	cp js/bootstrap.js bootstrap/js/bootstrap.js
	cp js/bootstrap.min.js bootstrap/js/bootstrap.min.js

#
# MAKE FOR GH-PAGES 4 FAT & MDO ONLY (O_O  )
#

gh-pages: docs
	rm -f ../bootstrap-gh-pages/assets/bootstrap.zip
	node docs/build production
	cp -r docs/* ../bootstrap-gh-pages

#
# WATCH LESS FILES
#

watch:
	echo "Watching less files..."; \
	watchr -e "watch('less/.*\.less') { system 'make' }"


#
# start static files server
#
up:
	@node server.js > /dev/null &

down:
	@killall 'node'


export:
	git archive master | bzip2 >source-tree.tar.bz2

upOO:
	@echo git pull odof
	@(cd odof; git pull;)

.PHONY: up down docs IMG JS CSS watch gh-pages export upOO
