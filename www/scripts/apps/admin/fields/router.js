define([
    'jquery',
    'underscore',
    'backbone',
    'models/admin/field',
    'apps/admin/fields/views/edit',
    'apps/admin/fields/views/list',
    'apps/admin/fields/collections/fields'
], function($, _, Backbone, FieldModel, FieldEditView, FieldListView, FieldsCollection) {
    var AppRouter = Backbone.Router.extend({
        routes: {
            'field/:id': "getField",
            'fields': 'showFields',
            '*actions': 'defaultAction'
        }
    });

    var initialize = function() {
        var app_router = new AppRouter();

        app_router.on('route:getField', function (id) {
            var field = new FieldModel({id: id});

            field.fetch().done(function() {
                var fieldEditView = new FieldEditView({
                    el: $('.right-block')[0],
                    model: field
                });

                fieldEditView.render();
            });
        });

        app_router.on('route:showFields', function() {
            var fieldsCollection = new FieldsCollection();
            fieldsCollection.fetch().done(function() {
                var fieldListView = new FieldListView({
                    collection: fieldsCollection,
                    el: $('ul#collection-fields')[0]
                });

                fieldListView.render();
            });
        });

        app_router.on('route:defaultAction', function(actions) {
            console.log('No route:', actions);
        });

        Backbone.history.start();

        // Show fields on start up
        app_router.navigate('fields', { trigger: true });
    };

    return {
        initialize: initialize
    };
});
