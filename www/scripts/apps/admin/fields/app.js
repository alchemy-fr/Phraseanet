/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "i18n",
    "apps/admin/fields/collections/fields",
    "apps/admin/fields/collections/vocabularies",
    "apps/admin/fields/collections/dcFields",
    "apps/admin/fields/views/list",
    "apps/admin/fields/views/save",
    "apps/admin/fields/views/fieldError",
    "apps/admin/fields/errors/errorManager"
], function ($, _, Backbone, i18n, FieldsCollection, VocabulariesCollection, DcFieldsCollection, FieldListView, SaveView, FieldErrorView, ErrorManager) {
    var create = function () {
        window.AdminFieldApp = {
            $window: $(window),
            $scope: $("#admin-field-app"),
            $top: $(".row-top", this.$scope),
            $bottom: $(".row-bottom", this.$scope),
            $leftBlock: $(".left-block", this.$bottom),
            $rightBlock: $(".right-block", this.$bottom),
            fieldsToDelete: [],
            lng: function () {
                return typeof p4 === "undefined" ? "en" : (p4.lng || "en");
            },
            resizeListBlock: function () {
                var listBlock = $(".list-block", AdminFieldApp.$leftBlock);
                listBlock.height(AdminFieldApp.$window.height() - listBlock.offset().top - 10);
            },
            resize: function () {
                AdminFieldApp.resizeListBlock();
                AdminFieldApp.$rightBlock.height(AdminFieldApp.$window.height() - AdminFieldApp.$rightBlock.offset().top - 10);
            }
        };

        // bind resize
        AdminFieldApp.$window.bind("resize", AdminFieldApp.resize);

        // current sbas id
        AdminFieldApp.sbas_id = $("input[name=current_sbas_id]", AdminFieldApp.scope).val();

        // global errors
        AdminFieldApp.errorManager = new ErrorManager();
        _.extend(AdminFieldApp.errorManager, Backbone.Events);

        // initiliaze collections
        AdminFieldApp.fieldsCollection = new FieldsCollection(null, {
            sbas_id: AdminFieldApp.sbas_id
        });
        AdminFieldApp.vocabularyCollection = new VocabulariesCollection();
        AdminFieldApp.dcFieldsCollection = new DcFieldsCollection();
    };

    var load = function() {
        // load strings
        i18n.init({resGetPath: "/admin/fields/language.json"});

        // load all collections
        $.when.apply($, [
                AdminFieldApp.fieldsCollection.fetch(),
                AdminFieldApp.vocabularyCollection.fetch(),
                AdminFieldApp.dcFieldsCollection.fetch(),
                $.ajax({
                    url: '/available-languages',
                    success: function (languages) {
                        AdminFieldApp.languages = languages;
                    }
                })
            ]).done(
            function () {
                // register views
                AdminFieldApp.saveView = new SaveView({
                    el: $(".save-block", AdminFieldApp.scope)
                });
                AdminFieldApp.fieldErrorView = new FieldErrorView();
                AdminFieldApp.fieldListView = new FieldListView({
                    collection: AdminFieldApp.fieldsCollection,
                    el: AdminFieldApp.$leftBlock
                });
                // render views
                AdminFieldApp.saveView.render();
                AdminFieldApp.fieldListView.render();

                // show bottom
                AdminFieldApp.$bottom.removeClass("hidden");

                AdminFieldApp.$window.trigger("resize");

                // click on first item list
                if (AdminFieldApp.fieldListView.itemViews.length > 0) {
                    _.first(AdminFieldApp.fieldListView.itemViews).clickAction().animate();
                }
            }
        );
    };

    var initialize = function () {
        create();
        load();
    };

    return {
        create: create,
        load: load,
        initialize: initialize
    };
});
