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
    "apps/login/home/views/error",
    "common/multiviews"
], function($, _, Backbone, ErrorView, MultiViews) {
    var InputView = Backbone.View.extend(_.extend({}, MultiViews, {
        initialize: function(options) {
            this.name = options.name;
            this.errorView = new ErrorView();
        },
        render: function () {
            this._assignView({".error-view" : this.errorView});
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
