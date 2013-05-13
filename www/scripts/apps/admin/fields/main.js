require.config({
    baseUrl: "/scripts",
    paths: {
        jquery: '../include/minify/f=include/jslibs/jquery-1.7.1',
        jqueryui: '../include/jslibs/jquery-ui-1.8.17/js/jquery-ui-1.8.17.custom.min',
        underscore: '../assets/underscore-amd/underscore',
        backbone: '../assets/backbone-amd/backbone',
        twig: '../assets/twig/twig',
        i18n: '../assets/i18n/i18next.amd',
        bootstrap: '../skins/html5/bootstrap/js/bootstrap.min'
    },
    shim: {
        twig: {
            exports: 'Twig'
        },
        bootstrap : ['jquery'],
        jqueryui: {
            deps: [ 'jquery' ]
        }
    }
});

require(['apps/admin/fields/app'], function(App) {
    App.initialize();
});
