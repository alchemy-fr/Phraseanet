require.config({
    baseUrl: "../../scripts",
    paths: {
        specs: 'tests/specs',
        chai: '../assets/chai/chai'
    },
    shim : {
        shai: {
            exports: "chai"
        }
    }
});

mocha.setup({
    ui: 'bdd',
    ignoreLeaks: true
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
