/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import _ from 'underscore';
import Backbone from 'backbone';
import ErrorView from './error';
import MultiViews from './../../multiviews';
var InputView = Backbone.View.extend(_.extend({}, MultiViews, {
    initialize: function (options) {
        options = options || {};
        if (options.name === undefined) {
            throw 'Missing name attribute in input view';
        }

        if (options.errorTemplate === undefined) {
            throw 'Missing errorTemplate attribute in input view';
        }

        this.name = options.name;

        this.errorView = new ErrorView({
            name: this.name,
            errorTemplate: options.errorTemplate,
            onRenderError: options.onRenderError || null
        });
    },
    render: function () {
        this._assignView({'.error-view': this.errorView});
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

export default InputView;
