/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "i18n"
], function($, _, Backbone, i18n) {
    var FieldErrorView = Backbone.View.extend({
        initialize: function() {
            AdminFieldApp.errorManager.on("add-error", this.render, this);
            AdminFieldApp.errorManager.on("remove-error", this.render, this);
        },
        render: function() {
            var messages = [];
            var errors = AdminFieldApp.errorManager.all();

            _.each(_.groupBy(errors, function(error) {
                return error.model.get("name");
            }), function(groupedErrors) {
                _.each(groupedErrors, function(error) {
                    messages.push(i18n.t("field_error", {
                        postProcess: "sprintf",
                        sprintf: [error.model.get("name")]
                    }));
                });
            });

            var template = _.template($("#field_error_template").html(), {
                messages: messages
            });

            $(".block-alert").html(template);

            return this;
        }
    });

    return FieldErrorView;
});
