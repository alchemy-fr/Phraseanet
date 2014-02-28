require.config({
    baseUrl: "../../scripts",
    paths: {
        specs: "tests/specs",
        chai: "../../tmp-assets/chai/chai",
        fixtures: "../../tmp-assets/js-fixtures/fixtures",
        jquery: "../assets/jquery/jquery",
        jqueryui: "../assets/jquery.ui/jquery-ui",
        backbone: "../assets/backbone-amd/backbone",
        i18n: "../assets/i18next/i18next.amd-1.6.3",
        bootstrap: "../assets/bootstrap/js/bootstrap.min",
        sinonchai: "../../tmp-assets/sinon-chai/lib/sinon-chai",
        squire: "../../tmp-assets/squire/src/Squire",
        "jquery.ui": "../assets/jquery.ui/jquery-ui",
        underscore: "../assets/underscore-amd/underscore",
        "jquery.ui.widget": "../assets/jquery-file-upload/jquery.ui.widget",
        "jquery.cookie": "../assets/jquery.cookie/jquery.cookie",
        "jquery.treeview": "../assets/jquery.treeview/jquery.treeview",
        "jquery.tooltip": "../assets/jquery.tooltip/jquery.tooltip",
        "blueimp.loadimage" : "../assets/blueimp-load-image/load-image",
        "jfu.iframe-transport": "../assets/jquery-file-upload/jquery.iframe-transport",
        "jfu.fileupload": "../assets/jquery-file-upload/jquery.fileupload",
    },
    shim: {
        jqueryui: {
            deps: ["jquery"]
        },
        "jquery.treeview": {
            deps: ['jquery', 'jquery.cookie'],
            exports: '$.fn.treeview'
        },
        bootstrap:{
            deps: ['jquery']
        },
        "jquery.cookie": {
            deps: ["jquery"],
            exports: '$.fn.cookie'
        },
        "jquery.tooltip": {
            deps: ["jquery"],
            exports: '$.fn.tooltip'
        },
        "jquery.ui": {
            deps: ["jquery"]
        },
        "jquery.ui.widget": {
            deps: ["jquery"]
        },
        "jfu.fileupload": {
            deps: ["jquery.ui.widget"]
        }
    }
});

mocha.setup({
    ui: "bdd",
    ignoreLeaks: true,
    globals: ['js-fixtures']
});

console = window.console || function () {
};

window.notrack = true;

var runMocha = function () {
    if (window.mochaPhantomJS) {
        mochaPhantomJS.run();
    } else {
        mocha.run();
    }
};
