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
    "apps/admin/fields/views",
    "apps/admin/fields/views/alert",
    "apps/admin/fields/views/modal",
    "apps/admin/fields/views/dcField",
    "apps/admin/fields/errors/error"
], function($, _, Backbone, i18n, ViewUtils, AlertView, ModalView, DcFieldView, Error) {
    // Add multiview methods
    var FieldEditView = Backbone.View.extend(_.extend({}, ViewUtils.MultiViews, {
        tagName: "div",
        className: "field-edit",
        initialize: function() {
            this.model.on("change", this._onModelChange, this);
        },
        updateModel: function(model) {
            // unbind event to previous model
            this.model.off("change");
            this.model = model;

            return this;
        },
        render: function() {
            var self = this;
            var template = _.template($("#edit_template").html(), {
                field: this.model.toJSON(),
                vocabularyTypes: AdminFieldApp.vocabularyCollection.toJSON(),
                modelErrors: AdminFieldApp.errorManager.getModelError(this.model)
            });

            this.$el.empty().html(template);

            this._assignView({
                ".dc-fields-subview" : new DcFieldView({
                    collection: AdminFieldApp.dcFieldsCollection,
                    field: this.model
                })
            });

            var completer = $("#tag", this.$el).autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.ajax({
                        url: "/admin/fields/tags/search",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.label,
                                    value: item.value
                                };
                            }));
                        }
                    });
                },
                close: function(e) {
                    self.tagFieldChangedAction(e);
                }
            });

            completer
                .val(this.model.get("tag"))
                .autocomplete("widget")
                .addClass("ui-autocomplete-admin-field");

            this.delegateEvents();

            return this;
        },
        events: {
            "click": "focusAction",
            "click .delete-field": "deleteAction",
            "keyup input#name": "nameFieldChangedAction",
            "keyup input#tbranch": "fieldChangedAction",
            "keyup input#tag": "tagFieldChangedAction",
            "change input[type=checkbox]": "fieldChangedAction",
            "change select": "selectionChangedAction"
        },
        focusAction: function() {
            var index = AdminFieldApp.fieldListView.collection.indexOf(this.model);
            if (index >= 0) {
                AdminFieldApp.fieldListView.itemViews[index].animate();
            }

            return this;
        },
        // on input name keyup check for errors
        nameFieldChangedAction: function(event) {
            var self = this;
            var fieldName = $(event.target);
            var fieldNameId = fieldName.attr("id");
            var fieldNameValue = fieldName.val();

            // check for empty or duplicate field name
            var notValid = "" === fieldNameValue || "undefined" !== typeof AdminFieldApp.fieldListView.collection.find(function(model) {
                return model.get("name").toLowerCase() === fieldNameValue.toLowerCase() && self.model.get("id") !== model.get("id");
            });

            if (notValid) {
                fieldName
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_name_exists"));
                // add error
                AdminFieldApp.errorManager.addModelFieldError(new Error(
                    self.model, fieldNameId, i18n.t("" === fieldNameValue ? "validation_blank" : "validation_name_exists")
                ));
            } else if (fieldName.closest(".control-group").hasClass("error")) {
                fieldName
                    .closest(".control-group")
                    .removeClass("error")
                    .find(".help-block")
                    .empty();
                // remove error
                AdminFieldApp.errorManager.removeModelFieldError(
                    self.model, fieldNameId
                );
            }

            if (!notValid) {
                this.fieldChangedAction(event);
            }

            return this;
        },
        selectionChangedAction: function(e) {
            var field = $(e.target);
            var data = {};
            data[field.attr("id")] = $("option:selected", field).val();
            this.model.set(data);

            return this;
        },
        fieldChangedAction: function(e) {
            var field = $(e.target);
            var fieldId = field.attr("id");
            var data = {};
            data[fieldId] = field.is(":checkbox") ? field.is(":checked") : field.val();
            this.model.set(data);

            return this;
        },
        tagFieldChangedAction: function(e) {
            var fieldTag = $(e.target);
            var fieldTagId = fieldTag.attr("id");
            var fieldTagValue = fieldTag.val();

            var notValid = "" !== fieldTagValue && false === /[a-z]+:[a-z0-9]+/i.test(fieldTagValue);
            // check for format tag
            if (notValid) {
                fieldTag
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_tag_invalid"));
                // add error
                AdminFieldApp.errorManager.addModelFieldError(new Error(
                    this.model, fieldTagId, i18n.t("validation_tag_invalid")
                ));
            } else if (fieldTag.closest(".control-group").hasClass("error")) {
                // remove error
                AdminFieldApp.errorManager.removeModelFieldError(
                    this.model, fieldTagId
                );

                fieldTag
                    .closest(".control-group")
                    .removeClass("error")
                    .find(".help-block")
                    .empty();
            }

            if (!notValid) {
                this.fieldChangedAction(e);
            }
        },
        deleteAction: function() {
            var self = this;
            var modalView = new ModalView({
                model: this.model,
                message: i18n.t("are_you_sure_delete", {
                    postProcess: "sprintf",
                    sprintf: [this.model.get("name")]
                })
            });

            // get collection index of previous and next model
            var previousIndex = AdminFieldApp.fieldListView.collection.previousIndex(this.model);
            var nextIndex = AdminFieldApp.fieldListView.collection.nextIndex(this.model);

            // get previous index if exists else next index - 1 as item is being deleted
            var index = previousIndex ? previousIndex : (nextIndex ? nextIndex - 1 : -1);

            modalView.render();
            modalView.on("modal:confirm", function() {
                self.model.destroy({
                    success: function(model, response) {
                        AdminFieldApp.fieldListView.collection.remove(self.model);
                        self._selectModelView(index);

                        new AlertView({alert: "info", message: i18n.t("deleted_success", {
                                postProcess: "sprintf",
                                sprintf: [model.get("name")]
                            })
                        }).render();
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        new AlertView({
                            alert: "error", message: '' !== xhr.responseText ? xhr.responseText : i18n.t("something_wrong")
                        }).render();
                    }
                });
            });

            return this;
        },
        _onModelChange: function() {
            AdminFieldApp.fieldListView.collection.remove(this.model, {silent: true});
            AdminFieldApp.fieldListView.collection.add(this.model);
            var index = AdminFieldApp.fieldListView.collection.indexOf(this.model);

            this._selectModelView(index);

            AdminFieldApp.saveView.updateStateButton();
        },
        // select temView by index in itemList
        _selectModelView: function(index) {
             // select previous or next itemview
            if (index >= 0) {
                AdminFieldApp.fieldListView.itemViews[index].select().animate();
            }
        }
    }));

    return FieldEditView;
});
