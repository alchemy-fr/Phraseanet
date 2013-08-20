require.config({
    baseUrl: "../../scripts",
    paths: {
        specs: "tests/specs",
        chai: "../assets/chai/chai",
        fixtures: "../assets/js-fixtures/fixtures",
        app: "apps/admin/fields/app",
        jquery: "../assets/jquery/jquery",
        jqueryui: "../assets/jquery.ui/jquery-ui",
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone",
        i18n: "../assets/i18next/i18next.amd-1.6.3",
        bootstrap: "../assets/bootstrap/js/bootstrap.min"
    },
    shim: {
        bootstrap : ["jquery"],
        jqueryui: {
            deps: ["jquery"]
        }
    }
});

mocha.setup({
    ui: "bdd",
    ignoreLeaks: true,
    globals: ['js-fixtures']
});

console = window.console || function() {};

window.notrack = true;

var runMocha = function() {
    if (window.mochaPhantomJS) {
        mochaPhantomJS.run();
    } else {
        mocha.run();
    }
};
