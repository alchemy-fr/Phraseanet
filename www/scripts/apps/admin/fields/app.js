define([
    'jquery',
    'underscore',
    'backbone',
    'apps/admin/fields/router'
], function($, _, Backbone, Router) {
    var initialize = function() {
        Router.initialize();
    };

    return {
        initialize: initialize
    };
});
