define([
    'jquery',
    'underscore',
    'backbone',
    'i18n',
    'apps/admin/fields/collections/fields',
    'apps/admin/fields/collections/vocabularies',
    'apps/admin/fields/collections/dcFields',
    'apps/admin/fields/views/list'
], function($, _, Backbone, i18n, FieldsCollection, VocabulariesCollection, DcFieldsCollection, FieldListView) {
    var initialize = function() {
        window.AdminFieldApp = {};

        window.AdminFieldApp.sbas_id = $('input[name=current_sbas_id]').val();

        var fieldsCollection = new FieldsCollection(null, {
            sbas_id : window.AdminFieldApp.sbas_id
        });

        var vocabulariesCollection = new VocabulariesCollection();
        var dcFieldsCollection = new DcFieldsCollection();

        // load strings synchronously
        i18n.init({ resGetPath: '/admin/fields/language.json', getAsync: false });

        var requests = [
            fieldsCollection.fetch(),
            vocabulariesCollection.fetch(),
            dcFieldsCollection.fetch()
        ];

        $.when.apply($, requests).done(
            function() {
                window.AdminFieldApp.vocabularyCollection = vocabulariesCollection;
                window.AdminFieldApp.dcFieldsCollection = dcFieldsCollection;

                window.AdminFieldApp.fieldListView = new FieldListView({
                    collection: fieldsCollection,
                    el: $('.left-block')[0]
                });

                window.AdminFieldApp.fieldListView.render();
                // click on first item list
                _.first(window.AdminFieldApp.fieldListView.itemViews).clickAction().animate();
            }
        );
    };

    return {
        initialize: initialize
    };
});
