define([
    "jquery",
    "underscore",
    "backbone",
    "i18n",
    "bootstrap",
    "apps/admin/fields/views/alert"
], function($, _, Backbone, i18n, bootstrap, AlertView) {
    var SaveView = Backbone.View.extend({
        initialize: function() {
            var self = this;
            this.previousAttributes = [];
            this.$overlay = null;

            AdminFieldApp.errorManager.on("add-error", function(errors) {
                self._disableSaveButton(true);
            });

            AdminFieldApp.errorManager.on("no-error", function() {
                self._disableSaveButton(false);
            });
        },
        events: {
            "click button.save-all" : "clickSaveAction"
        },
        clickSaveAction: function(event) {
            var self = this;

            if (this._isModelDesync()) {
                this._loadingState(true);
                AdminFieldApp.fieldsCollection.save({
                    success: function(response) {
                        // reset collection with new one
                        if (response.success) {
                            AdminFieldApp.fieldsCollection.reset(response.fields);
                        }

                        new AlertView({
                            alert: response.success ? "success" : "error",
                            message: response.messages.join("<br />")
                        }).render();
                    },
                    error: function(model, xhr, options) {
                        new AlertView({
                            alert: "error", message: i18n.t("something_wrong")
                        }).render();
                    }
                }).done(function() {
                    self._loadingState(false);
                });
            }

            return this;
        },
        render: function () {
            var template = _.template($("#save_template").html());
            this.$el.html(template);

            return this;
        },
        // check whether model has changed or not
        _isModelDesync: function () {
            return "undefined" !== typeof AdminFieldApp.fieldsCollection.find(function(model) {
                return !_.isEmpty(model.previousAttributes());
            });
        },
        // create a transparent overlay on top of the application
        _overlay: function(showOrHide) {
            if(showOrHide && !this.$overlay) {
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
        _loadingState: function(active) {
            if (active) {
                $(".save-block", AdminFieldApp.$top).addClass("loading");
                $(".block-alert", AdminFieldApp.$top).empty();
            } else {
                $(".save-block", AdminFieldApp.$top).removeClass("loading");
            }

            this._disableSaveButton(active);
            this._overlay(active);
        }
    });

    return SaveView;
});
