define([
    "jquery",
    "underscore",
    "backbone"
], function($, _, Backbone) {
    return {
        MultiViews : {
            // bind a subview to a DOM element
            _assignView: function(selector, view) {
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
            }
        }
    };
});
