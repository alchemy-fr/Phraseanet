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
    "common/forms/views/error",
    "common/multiviews"
], function ($, _, Backbone, ErrorView, MultiViews) {
    var InputView = Backbone.View.extend(_.extend({}, MultiViews, {
        initialize: function (options) {
            options = options || {};

            if (false === "name" in options) {
                throw "Missing name attribute in input view";
            }

            if (false === "errorTemplate" in options) {
                throw "Missing errorTemplate attribute in input view";
            }

            this.name = options.name;

            this.errorView = new ErrorView({
                name: this.name,
                errorTemplate: options.errorTemplate,
                onRenderError: options.onRenderError || null
            });
        },
        render: function () {
            this._assignView({".error-view": this.errorView});
        },
        showErrors: function (errors) {
            this.render();

            this.errorView.renderErrors(errors);

            return this;
        },
        resetErrors: function () {
            this.errorView.reset();
        }
    }));

    return InputView;
});
