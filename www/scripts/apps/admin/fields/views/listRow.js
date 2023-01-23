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
    "apps/admin/fields/views/edit"
], function ($, _, Backbone, FieldEditView) {
    var FieldListRowView = Backbone.View.extend({
        tagName: "li",
        className: "field-row",
        template: _.template($("#list_row_template").html()),
        initialize: function () {
            // destroy view is model is deleted
            this.model.on("change", this.onChange, this);
            this.model.on("destroy", this.remove, this);
        },
        events: {
            "click .trigger-click": "clickAction",
            "drop": "dropAction"
        },
        clickAction: function (e) {
            this.select();
            // first click create edit view else update model"s view
            if (typeof AdminFieldApp.fieldEditView === "undefined") {
                AdminFieldApp.fieldEditView = new FieldEditView({
                    el: AdminFieldApp.$rightBlock,
                    model: this.model
                });
            } else {
                AdminFieldApp.fieldEditView.updateModel(this.model).initialize();
            }

            AdminFieldApp.fieldEditView.render();

            return this;
        },
        dropAction: function (event, ui) {
            this.$el.trigger("update-sort", [this.model, ui]);

            return this;
        },
        onChange: function () {
            if (this.model.hasChanged("tag")) {
                this.render();
            }
        },
        render: function () {
            this.$el.empty().html(this.template({
                    id: this.model.get("id") || "",
                    position: this.model.get("sorter"),
                    name: this.model.get("name"),
                    tag: this.model.get("tag")
                }
            ));

            // highlight view if edit view model match current view model
            if (AdminFieldApp.fieldEditView
                && AdminFieldApp.fieldEditView.model.get("id") === this.model.get("id")) {
                this.select();
            }

            return this;
        },
        // set selected class to current view
        select: function () {
            $("li", this.$el.closest("ul")).removeClass("selected");
            this.$el.addClass("selected");

            return this;
        },
        click: function () {
            this.$el.find('.trigger-click').first().trigger('click');
            return this;
        },
        // scroll to current view in item list
        animate: function (top) {
            top = top || null;

            if (null === top) {
                top = $(".field-row").index(this.$el) * this.$el.height();
            }

            this.$el.closest("div").scrollTop(top);

            return this;
        },
        // add error class to item
        error: function (errored) {
            if (errored) {
                this.$el.addClass("error");
            } else {
                this.$el.removeClass("error");
            }

            return this;
        }
    });

    return FieldListRowView;
});
