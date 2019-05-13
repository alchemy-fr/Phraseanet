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
    "i18n"
], function ($, _, Backbone, i18n, bootstrap) {
    var DcFieldsView = Backbone.View.extend({
        tagName: "div",
        className: "input-append",
        template: _.template($("#dc_fields_template").html()),
        initialize: function (options) {
            this.field = options.field;
        },
        render: function () {
            this.$el.html(this.template({
                    dces_elements: this.collection.toJSON(),
                    field: this.field.toJSON()
                })
            );

            var index = $("#dces-element", this.$el)[0].selectedIndex - 1;
            if (index > 0) {
                $(".dces-help-block", AdminFieldApp.$rightBlock).html(
                    this.collection.at(index).get("definition")
                );
            }

            return this;
        }
    });

    return DcFieldsView;
});
