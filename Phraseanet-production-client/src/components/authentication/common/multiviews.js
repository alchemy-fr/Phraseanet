/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import _ from 'underscore';
export default {
    // bind a subview to a DOM element
    _assignView: function (selector, view) {
        var selectors;
        if (_.isObject(selector)) {
            selectors = selector;
        } else {
            selectors = {};
            selectors[selector] = view;
        }
        if (!selectors) return;
        _.each(selectors, function (view, selector) {
            view.setElement(this.$(selector)).render();
        }, this);
    }
};
