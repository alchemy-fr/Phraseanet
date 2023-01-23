import webpackConfig from './webpack/webpack.karma.config';

// Karma configuration here
module.exports = function(config) {
    config.set({
        // list of files to exclude
        exclude: [],
        // list of files / patterns to load in the browser
        files: [
            './node_modules/jquery/dist/jquery.js',
            './node_modules/babel-polyfill/dist/polyfill.js',
            './node_modules/phantomjs-polyfill/bind-polyfill.js',
            './test/**/*.browser.js'
        ],
        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: [
            'sinon-chai',
            'sinon',
            'chai',
            'mocha'
        ], //use mocha and sinon-chai as framework

        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            // 'src/**/*.js': ['coverage'],
            'test/**/*.browser.js': ['webpack', 'sourcemap']
        },
        // test results reporter to use
		reporters: ['progress', 'mocha','coverage'],
        coverageReporter: {
            reporters: [{
                type: 'text'
            }, {
                type: 'lcovonly',
                subdir: '.'
            }, {
                type: 'html',
                dir: 'coverage/'
            }
            ]
        },
        webpack: webpackConfig,
        webpackMiddleware: {
            noInfo: true
        },
        plugins: [
            'karma-sinon-chai',
            'karma-sinon',
            'karma-chai',
            'karma-webpack',
            'karma-mocha',
            'karma-mocha-reporter',
            'karma-phantomjs-launcher',
            'karma-chrome-launcher',
            'karma-firefox-launcher',
            'karma-ie-launcher',
            'karma-coverage',
			'karma-sourcemap-loader'
        ],
        // Start these browsers, currently available:
        // - Chrome
        // - ChromeCanary
        // - Firefox
        // - Opera (has to be installed with `npm install karma-opera-launcher`)
        // - Safari (only Mac; has to be installed with `npm install karma-safari-launcher`)
        // - PhantomJS
        // - IE (only Windows; has to be installed with `npm install karma-ie-launcher`)
        browsers: ['PhantomJS'],
        browserDisconnectTimeout: 10000,
        browserDisconnectTolerance: 2,
        // concurrency level how many browser should be started simultaneously
        concurrency: 4,
        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 100000,
        browserNoActivityTimeout: 30000,
        // enable / disable colors in the output (reporters and logs)
        colors: true,
        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,
        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: false,
        // Continuous Integration mode
        // if true, Karma captures browsers, runs the tests and exits
        singleRun: true,
    });

    if (process.env.TRAVIS) {

        config.logLevel = config.LOG_DEBUG;
        // Karma (with socket.io 1.x) buffers by 50 and 50 tests can take a long time on IEs;-)
        config.browserNoActivityTimeout = 120000;

        // Debug logging into a file, that we print out at the end of the build.
        config.loggers.push({
            type: 'file',
            filename: 'logs/karma.log'
        });
    }
};