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
    "bootstrap",
    "apps/admin/fields/views/alert"
], function ($, _, Backbone, i18n, bootstrap, AlertView) {
    var SaveView = Backbone.View.extend({
        template: _.template($("#save_template").html()),
        initialize: function () {
            var self = this;
            this.previousAttributes = [];
            this.$overlay = null;

            AdminFieldApp.errorManager.on("add-error", function (errors) {
                self._disableSaveButton(true);
            });

            AdminFieldApp.errorManager.on("no-error", function () {
                self._disableSaveButton(false);
            });
        },
        events: {
            "click button.save-all": "clickSaveAction"
        },
        clickSaveAction: function (event) {
            var self = this;

            if (this._isModelDesync()) {
                this._loadingState(true);
                $.when.apply($, _.map(AdminFieldApp.fieldsToDelete, function (m) {
                        return m.destroy({
                            success: function (model, response) {
                                AdminFieldApp.fieldsToDelete = _.filter(AdminFieldApp.fieldsToDelete, function (m) {
                                    return model.get("id") !== m.get("id");
                                });
                            },
                            error: function (xhr, textStatus, errorThrown) {
                                new AlertView({
                                    alert: "error", message: '' !== xhr.responseText ? xhr.responseText : i18n.t("something_wrong")
                                }).render();
                            }
                        });
                    })).done(
                    function () {
                        AdminFieldApp.fieldsCollection.save({
                            success: function (fields) {
                                // reset collection with new one
                                AdminFieldApp.fieldsCollection.reset(fields);

                                new AlertView({
                                    alert: "success",
                                    message: i18n.t("fields_save"),
                                    delay: 2000
                                }).render();
                            },
                            error: function (xhr, textStatus, errorThrown) {
                                new AlertView({
                                    alert: "error", message: '' !== xhr.responseText ? xhr.responseText : i18n.t("something_wrong")
                                }).render();
                            }
                        }).done(function () {
                                self._loadingState(false);
                            });
                    }
                );
            }

            return this;
        },
        render: function () {
            this.$el.html(this.template());
            this.updateStateButton();

            return this;
        },
        updateStateButton: function (disable) {
            var toDisable = !this._isModelDesync();
            if ("undefined" !== typeof disable) {
                toDisable = disable;
            }

            this._disableSaveButton(toDisable);
        },
        // check whether model has changed or not
        _isModelDesync: function () {
            var fieldToDelete = false;
            var fieldToUpdate = false;

            fieldToUpdate = "undefined" !== typeof AdminFieldApp.fieldsCollection.find(function (model) {
                return !_.isEmpty(model.previousAttributes());
            });

            fieldToDelete = AdminFieldApp.fieldsToDelete.length > 0;

            return fieldToUpdate || fieldToDelete;
        },
        // create a transparent overlay on top of the application
        _overlay: function (showOrHide) {
            if (showOrHide && !this.$overlay) {
                this.$overlay = $("<div>").addClass("overlay");
                AdminFieldApp.$bottom.append(this.$overlay);
            } else if (!showOrHide && this.$overlay) {
                this.$overlay.remove();
                this.$overlay = null;
            }
        },
        _disableSaveButton: function (active) {
            $("button.save-all", this.$el).attr("disabled", active);
        },
        // put application on loading state (add overlay, add spinner, disable global save button)
        _loadingState: function (active) {
            if (active) {
                $(".save-block", AdminFieldApp.$top).addClass("loading");
                $(".block-alert", AdminFieldApp.$top).empty();
            } else {
                $(".save-block", AdminFieldApp.$top).removeClass("loading");
            }

            this.updateStateButton();
            this._overlay(active);
        }
    });

    return SaveView;
});
