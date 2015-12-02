/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "apps/admin/tasks-manager/views/task"
], function ($, _, Backbone, TaskView) {
    var TasksView = Backbone.View.extend({
        initialize: function() {
            this._taskViews = [];
            this._rendered = false;

            this.collection.bind('add', this._addOne, this);
            this.collection.bind('remove', this._removeOne, this);
        },
        render: function () {
            var $this = this;
            $this.$el.empty();
            $this.collection.each(function(model) {
                $this._appendDom($this._createView(model));
            });
            $this._rendered = true;
            $('.dropdown-toggle').dropdown();
            return $this;
        },
        _addOne: function (task) {
            var view = this._createView(task);

            if (this._rendered) {
                this._appendDom(view);
            }
            $('.dropdown-toggle').dropdown();
        },
        _createView: function (task) {
            var view = new TaskView({ model: task });
            this._taskViews.push(view);
            return view;
        },
        _deleteView: function (task) {
            var view = _(this._taskViews).select(function(taskView) {
                return taskView.model === task;
            })[0];
            this._taskViews = _(this._taskViews).without(view);
            return view;
        },
        _removeOne: function (task) {
            var view = this._deleteView(task);

            if (this._rendered) {
                this._removeDom(view);
            }
        },
        _appendDom: function(view) {
            this.$el.append(view.render().el);
        },
        _removeDom: function(view) {
            view.$el.remove();
        }
    });

    return TasksView;
});
