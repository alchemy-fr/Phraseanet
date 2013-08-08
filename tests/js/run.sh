#!/bin/bash
BASEDIR=$(dirname $0)
ROOTDIR="$BASEDIR/../.."

PHANTOMJS_BIN=""
MOCHA_PHANTOMJS_BIN=""

if type "phantomjs" > /dev/null; then
    PHANTOMJS_BIN="phantomjs"
elif type "$ROOTDIR/node_modules/.bin/phantomjs" > /dev/null; then
    PHANTOMJS_BIN="$ROOTDIR/node_modules/.bin/phantomjs"
fi

if type "mocha-phantomjs" > /dev/null; then
    MOCHA_PHANTOMJS_BIN="mocha-phantomjs"
elif type "$ROOTDIR/node_modules/.bin/mocha-phantomjs" > /dev/null; then
    MOCHA_PHANTOMJS_BIN="$ROOTDIR/node_modules/.bin/mocha-phantomjs"
fi

if [ -z "$PHANTOMJS_BIN" ]; then
    echo "phantomjs is required to run JS tests, see https://npmjs.org/package/phantomjs"
    exit 1
fi

if [ -z "$MOCHA_PHANTOMJS_BIN" ]; then
    echo "mocha-phantomjs is required to run JS tests, see https://npmjs.org/package/mocha-phantomjs"
    exit 1
fi

# run qunit tests
$PHANTOMJS_BIN "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Upload.js.html"
$PHANTOMJS_BIN "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Edit.js.html"
$PHANTOMJS_BIN "$ROOTDIR/www/assets/qunit/addons/phantomjs/runner.js" "$ROOTDIR/www/include/js/tests/jquery.Selection.js.html"

# run backbone tests
$MOCHA_PHANTOMJS_BIN "$ROOTDIR/www/scripts/tests/index.html"


