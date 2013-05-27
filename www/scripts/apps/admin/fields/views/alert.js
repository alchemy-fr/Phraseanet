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
    "i18n",
    "bootstrap"
], function($, _, Backbone, i18n, bootstrap) {
    var AlertView = Backbone.View.extend({
        tagName: "div",
        className: "alert",
        initialize: function(options) {
            var self = this;

            if (options) {
                this.alert = options.alert || "info";
                this.message = options.message || "";
            }
            // remove view when alert is closed
            this.$el.bind("closed", function () {
                self.remove();
            });
        },
        render: function() {
            var template = _.template($("#alert_template").html(), {
                msg: this.message
            });

            this.$el.addClass("alert-" + this.alert).html(template).alert();

            $(".block-alert").empty().append(this.$el);

            return this;
        }
    });

    return AlertView;
});
