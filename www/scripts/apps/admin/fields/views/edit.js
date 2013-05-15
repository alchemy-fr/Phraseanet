define([
    "jquery",
    "underscore",
    "backbone",
    "i18n",
    "apps/admin/fields/views/alert",
    "apps/admin/fields/views/modal",
    "apps/admin/fields/views/dcField",
    "apps/admin/fields/errors/error"
], function($, _, Backbone, i18n, AlertView, ModalView, DcFieldView, Error) {
    var FieldEditView = Backbone.View.extend({
        tagName: "div",
        className: "field-edit",
        initialize: function() {
            this.model.on("change", this._onModelChange, this);

            this.dcFieldsSubView = new DcFieldView({
                collection: AdminFieldApp.dcFieldsCollection,
                field: this.model
            });
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

            this._assign({
                ".dc-fields-subview" : this.dcFieldsSubView
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
                    var fieldTag = $(e.target);
                    var fieldTagId = fieldTag.attr("id");
                    var fieldTagValue = fieldTag.val();

                    // check for format tag
                    if ("" !== fieldTagValue && false === /[a-z]+:[a-z0-9]+/i.test(fieldTagValue)) {
                        fieldTag
                            .closest(".control-group")
                            .addClass("error")
                            .find(".help-block")
                            .empty()
                            .append(i18n.t("validation_tag_invalid"));
                        // add error
                        AdminFieldApp.errorManager.addModelFieldError(new Error(
                            self.model, fieldTagId, i18n.t("validation_tag_invalid")
                        ));
                    } else if (fieldTag.closest(".control-group").hasClass("error")) {
                        // remove error
                        AdminFieldApp.errorManager.removeModelFieldError(
                            self.model, fieldTagId
                        );

                        fieldTag
                            .closest(".control-group")
                            .removeClass("error")
                            .find(".help-block")
                            .empty();
                    }

                    var data = {};
                    data[fieldTagId] = fieldTagValue;
                    self.model.set(data);
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
            "keyup #name": "changeNameAction",
            "focusout input[type=text]": "fieldChangedAction",
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
        changeNameAction: function(event) {
            var self = this;
            var fieldName = $(event.target);
            var fieldNameId = fieldName.attr("id");
            var fieldNameValue = fieldName.val();

            // check for duplicate field name
            if ("" === fieldNameValue || "undefined" !== typeof AdminFieldApp.fieldListView.collection.find(function(model) {
                return model.get("name").toLowerCase() === fieldNameValue.toLowerCase() && self.model.get("id") !== model.get("id");
            })) {
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
        },
        selectionChangedAction: function(e) {
            var field = $(e.currentTarget);
            var data = {};
            data[field.attr("id")] = $("option:selected", field).val();
            this.model.set(data);

            return this;
        },
        fieldChangedAction: function(e) {
            var field = $(e.currentTarget);
            var fieldId = field.attr("id");
            var data = {};
            data[fieldId] = field.is(":checkbox") ? field.is(":checked") : field.val();
            this.model.set(data);

            return this;
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
                    error: function(model, xhr) {
                        new AlertView({
                            alert: "error", message: i18n.t("something_wrong")
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

            this.render();
        },
        // bind a subview to a DOM element
        _assign: function(selector, view) {
            var selectors;
            if (_.isObject(selector)) {
                selectors = selector;
            } else {
                selectors = {};
                selectors[selector] = view;
            }
            if (!selectors) return;
            _.each(selectors, function(view, selector) {
                view.setElement(this.$(selector)).render();
            }, this);
        },
        // select temView by index in itemList
        _selectModelView: function(index) {
             // select previous or next itemview
            if (index >= 0) {
                AdminFieldApp.fieldListView.itemViews[index].clickAction().animate();
            }
        }
    });

    return FieldEditView;
});
