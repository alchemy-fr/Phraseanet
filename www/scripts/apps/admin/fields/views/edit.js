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
    "common/multiviews",
    "apps/admin/fields/views/alert",
    "apps/admin/fields/views/modal",
    "apps/admin/fields/views/dcField",
    "apps/admin/fields/errors/error"
], function ($, _, Backbone, i18n, MultiViews, AlertView, ModalView, DcFieldView, Error) {
    // Add multiview methods
    var FieldEditView = Backbone.View.extend(_.extend({}, MultiViews, {
        tagName: "div",
        className: "field-edit",
        template: _.template($("#edit_template").html()),
        initialize: function () {
            this.model.on("change", this._onModelChange, this);
        },
        updateModel: function (model) {
            // unbind event to previous model
            this.model.off("change");
            this.model = model;

            return this;
        },
        render: function () {
            var self = this;

            this.$el.empty().html(this.template({
                    lng: AdminFieldApp.lng(),
                    field: this.model.toJSON(),
                    vocabularyTypes: AdminFieldApp.vocabularyCollection.toJSON(),
                    modelErrors: AdminFieldApp.errorManager.getModelError(this.model),
                    languages: AdminFieldApp.languages
                }
            ));

            this._assignView({
                ".dc-fields-subview": new DcFieldView({
                    collection: AdminFieldApp.dcFieldsCollection,
                    field: this.model
                })
            });

            var completer = $("#tag", this.$el).autocomplete({
                minLength: 2,
                source: function (request, response) {
                    $.ajax({
                        url: "/admin/fields/tags/search",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response($.map(data, function (item) {
                                return {
                                    label: item.label,
                                    value: item.value
                                };
                            }));
                        }
                    });
                },
                close: function (e) {
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
            "click .delete-field": "deleteAction",
            "blur input#tbranch": "fieldChangedAction",
            "blur input#separator": "fieldChangedAction",
            "blur input#tag": "tagFieldChangedAction",
            "change select#vocabulary-type": "triggerControlledVocabulary",
            "keyup input.input-label": "labelChangedAction",
            "change input[type=checkbox]": "fieldChangedAction",
            "change select": "selectionChangedAction",
            "click .lng-label a": "_toggleLabels",
            "click input#multi": "multiClickdAction"
        },
        triggerControlledVocabulary: function (e) {
            if ($(e.target, this.$el).find("option:selected").val() === "") {
                this.model.set("vocabulary-type", false);
                this.render();
            } else if ($("input#vocabulary-restricted", this.$el).length === 0) {
                this.model.set("vocabulary-restricted", false);
                this.model.set("vocabulary-type", $(e.target, this.$el).find("option:selected").val());
                this.render();
            }
        },
        selectionChangedAction: function (e) {
            var field = $(e.target);
            var data = {};
            data[field.attr("id")] = $("option:selected", field).val();
            this.model.set(data);

            return this;
        },
        fieldChangedAction: function (e) {
            var field = $(e.target);
            var fieldId = field.attr("id");
            var data = {};
            data[fieldId] = field.is(":checkbox") ? field.is(":checked") : field.val();
            this.model.set(data);

            return this;
        },
        labelChangedAction: function (e) {
            var field = $(e.target);
            var fieldId = field.attr("id");
            var data = this.model.get("labels");

            data[fieldId.split("_").pop()] = field.val();

            this.model.set(data);

            return this;
        },
        multiClickdAction: function (e) {
            if($(e.target).is(":checked")) {
                $("#separatorZone").show();
            }
            else {
                $("#separatorZone").hide();
            }

            return this;
        },
        tagFieldChangedAction: function (e) {
            var $this = this;
            var fieldTag = $(e.target);
            var fieldTagId = fieldTag.attr("id");
            var fieldTagValue = fieldTag.val();

            var onFieldValid = function () {
                if (fieldTag.closest(".control-group").hasClass("error")) {
                    // remove error
                    AdminFieldApp.errorManager.removeModelFieldError(
                        $this.model, fieldTagId
                    );

                    fieldTag
                        .closest(".control-group")
                        .removeClass("error")
                        .find(".help-block")
                        .empty();
                } else {
                    $this.fieldChangedAction(e);
                }
            };

            if ("" !== fieldTagValue) {
                var jqxhr = $.get("/admin/fields/tags/" + fieldTagValue, onFieldValid).fail(function () {
                    fieldTag
                        .closest(".control-group")
                        .addClass("error")
                        .find(".help-block")
                        .empty()
                        .append(i18n.t("validation_tag_invalid"));
                    // add error
                    AdminFieldApp.errorManager.addModelFieldError(new Error(
                        $this.model, fieldTagId, i18n.t("validation_tag_invalid")
                    ));
                });
            } else {
                onFieldValid();
            }
        },
        deleteAction: function () {
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
            modalView.on("modal:confirm", function () {
                AdminFieldApp.fieldsToDelete.push(self.model);
                AdminFieldApp.fieldListView.collection.remove(self.model);
                // last item is deleted
                if (index < 0) {
                    self.remove();
                } else {
                    self._selectModelView(index);
                }
                // Enable state button, models is out of sync
                AdminFieldApp.saveView.updateStateButton(false);
            });

            return this;
        },
        _onModelChange: function () {
            AdminFieldApp.fieldListView.collection.remove(this.model, {silent: true});
            AdminFieldApp.fieldListView.collection.add(this.model, {silent: true});
            AdminFieldApp.saveView.updateStateButton();
        },
        // select temView by index in itemList
        _selectModelView: function (index) {
            // select previous or next itemview
            if (index >= 0) {
                AdminFieldApp.fieldListView.itemViews[index].select().animate().click();
            }
        },
        _toggleLabels: function (event) {
            event.preventDefault();
            var curLabel = $(event.target);
            $('.lng-label', this.$el).removeClass("select");
            curLabel.closest(".lng-label").addClass("select");
            $('.input-label', this.$el).hide();
            var href = curLabel.attr('href');

            $("#" + href.split("#").pop(), this.$el).show();
        }
    }));

    return FieldEditView;
});
