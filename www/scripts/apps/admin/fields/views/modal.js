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
    "bootstrap"
], function ($, _, Backbone, i18n, bootstrap) {
    var ModalView = Backbone.View.extend({
        tagName: "div",
        className: "modal",
        events: {
            "click .confirm": "confirmAction"
        },
        template: _.template($("#modal_template").html()),
        initialize: function (options) {
            var self = this;
            // remove view when modal is closed
            this.$el.on("hidden", function () {
                self.remove();
            });

            if (options) {
                this.message = options.message;
            }
        },
        render: function () {
            this.$el.html(this.template({msg: this.message || ""})).modal();

            return this;
        },
        confirmAction: function () {
            this.trigger("modal:confirm");
            this.$el.modal("hide");
            this.remove();

            return this;
        }
    });

    return ModalView;
});
