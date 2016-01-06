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
    "bootstrap",
    "common/forms/validator",
    "common/forms/views/input"
], function ($, _, Backbone, bootstrap, Validator, InputView) {
    var Form = Backbone.View.extend({
        events: {
            "submit": "_onSubmit"
        },
        initialize: function (options) {
            options = options || {};

            var self = this;

            this.errorTemplate = options.errorTemplate || "#field_errors";
            this.onRenderError = options.onRenderError || null;
            // get a new validator defined rules
            this.validator = new Validator(options.rules || []);

            this.inputViews = {};

            // creates input views for each input
            _.each(this.$el.serializeArray(), function (input) {
                self._addInputView(input);
            });
        },
        _onSubmit: function (event) {
            var self = this;

            // reset previous errors in the view
            this._resetInputErrors();

            // validate new values
            this.validator.validate(this.$el.serializeArray());

            if (this.validator.hasErrors()) {
                // cancel submit
                event.preventDefault();
                // group errors by input
                _.each(_.groupBy(this.validator.getErrors(), function (error) {
                    return error.name;
                }), function (errors, name) {
                    // show new errors in the views
                    if (name in self.inputViews) {
                        self.inputViews[name].showErrors(errors);
                    } else {
                        // Because serialize array do not serialize non checked input
                        // We must create view for errored input name on the fly
                        var input = {"name": name};

                        self._addInputView(input);
                        self.inputViews[name].showErrors(errors);
                    }
                });
            }
        },
        _resetInputErrors: function () {
            _.each(this.inputViews, function (view) {
                view.resetErrors();
            });
        },
        _addInputView: function (input) {
            var name = input.name;

            this.inputViews[name] = new InputView({
                name: name,
                el: $("input[name='" + name + "'], select[name='" + name + "'], textarea[name='" + name + "']", this.$el).first().closest("div"),
                errorTemplate: this.errorTemplate,
                onRenderError: this.onRenderError
            });

            return this;
        }
    });

    return Form;
});
