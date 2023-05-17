const _root = __dirname + '/../';

module.exports = {

    // path helpers
    _app: 'app',
    minified: 'app.min.js',
    dev: 'app.js',
    eslintDir: _root + '.eslintrc',
    distDir: _root + 'dist',
    sourceDir: _root + 'src/',
    testDir: _root + 'tests',
    setupDir: _root + 'tests/setup/node.js',
    karmaConf: _root + 'config/karma.conf.js',
    // change this version when you change JS file for lazy loading
    assetFileVersion: 86
};
