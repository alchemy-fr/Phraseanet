require.config({
    baseUrl: "../../scripts",
    paths: {
        specs: "tests/specs",
        chai: "../assets/chai/chai",
        fixtures: "../assets/js-fixtures/fixtures",
        sinon: "../assets/sinon/lib/sinon",
        sinonSpy: "../assets/sinon/lib/sinon/spy",
        sinonMock: "../assets/sinon/lib/sinon/mock",
        sinonStub: "../assets/sinon/lib/sinon/stub",
        app: "apps/admin/fields/app",
        jquery: "../assets/jquery/jquery",
        jqueryui: "../include/jslibs/jquery-ui-1.10.3",
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone",
        twig: "../assets/twig/twig",
        i18n: "../assets/i18next/release/i18next.amd-1.6.2.min",
        bootstrap: "../skins/build/bootstrap/js/bootstrap.min"
    },
    shim: {
        bootstrap: ["jquery"],
        jqueryui: {
            deps: ["jquery"]
        },
        sinonSpy: {
            deps: ["sinon"],
            exports: "sinon"
        },
        sinonMock: {
            deps: ["sinon", "sinonSpy", "sinonStub"],
            exports: "sinon"
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
