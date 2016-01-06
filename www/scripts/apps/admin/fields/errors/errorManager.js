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
    "apps/admin/fields/errors/errorModel"
], function ($, _, Backbone, ErrorModel) {

    var ErrorManager = function () {
        this.errors = {};
        _.extend(this, Backbone.Events);
    };

    ErrorManager.prototype = {
        addModelError: function (model) {
            return this.errors[model.get("id")] = new ErrorModel(model.get("id"));
        },
        getModelError: function (model) {
            if (this.containsModelError(model)) {
                return this.errors[model.get("id")];
            }

            return null;
        },
        removeModelError: function (model) {
            if (this.containsModelError(model)) {
                delete this.errors[model.get("id")];
            }
        },
        containsModelError: function (model) {
            return "undefined" !== typeof this.errors[model.get("id")];
        },
        addModelFieldError: function (error) {
            if (!error instanceof Error) {
                throw "Item must be an error object";
            }

            var model = error.model;
            var fieldId = error.fieldId;

            if (!this.containsModelError(model)) {
                this.addModelError(model);
            }

            this.getModelError(model).add(fieldId, error);

            this.trigger("add-error", error);

            return this;
        },
        removeModelFieldError: function (model, fieldId) {
            var modelError = this.getModelError(model);

            if (modelError) {
                modelError.remove(fieldId);
                this.trigger("remove-error", model, fieldId);

                if (modelError.count() === 0) {
                    this.removeModelError(model);

                    if (!this.hasErrors()) {
                        this.trigger("no-error");
                    }
                }
            }
        },
        clearModelFieldErrors: function (model, fieldId) {
            var modelError = this.getModelError(model);

            if (modelError) {
                modelError.clear();
                this.removeModelError(model);
            }

            if (!this.hasErrors()) {
                this.trigger("no-error");
            }
        },
        containsModelFieldError: function (model, fieldId) {
            var modelError = this.getModelError(model);

            if (modelError) {
                return modelError.has(fieldId);
            }

            return false;
        },
        getModelFieldError: function (model, fieldId) {
            var modelError = this.getModelError(model);

            if (modelError) {
                return modelError.get(fieldId);
            }

            return null;
        },
        clearAll: function () {
            this.errors = {};
            this.trigger("no-error");
        },
        hasErrors: function () {
            return !_.isEmpty(this.errors);
        },
        count: function () {
            var count = 0;
            for (var k in this.errors) {
                if (this.errors.hasOwnProperty(k)) {
                    ++count;
                }
            }
            return count;
        },
        all: function () {
            var errors = [];
            _.each(this.errors, function (modelErrors) {
                _.each(modelErrors.all(), function (error) {
                    errors.push(error);
                });
            });

            return errors;
        }
    };

    return ErrorManager;
});
