require.config({
    baseUrl: "/scripts",
    paths: {
        jquery: '../assets/jquery/jquery',
        underscore: '../assets/underscore-amd/underscore',
        backbone: '../assets/backbone-amd/backbone',
        twig: '../assets/twig/twig',
        i18n: '../assets/i18n/i18next.amd'
    },
    shim: {
        twig: {
            exports: 'Twig'
        }
    }
});

require(['apps/admin/fields/app'], function(App) {
    App.initialize();
});
