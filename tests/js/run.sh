#!/bin/bash
BASEDIR=$(dirname $0)
ROOTDIR="$BASEDIR/../.."

# run qunit tests
phantomjs "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Upload.js.html"
phantomjs "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Edit.js.html"
phantomjs "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Selection.js.html"

# run backbone tests
mocha-phantomjs "$ROOTDIR/www/scripts/tests/index.html"

# run angular tests
sh "$BASEDIR/scripts/run.sh"

