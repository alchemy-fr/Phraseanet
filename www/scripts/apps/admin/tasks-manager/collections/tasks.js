/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "underscore",
    "backbone",
    "models/task"
], function (_, Backbone, TaskModel) {
    var TaskCollection = Backbone.Collection.extend({
        model: TaskModel,
        url: function () {
            return "/admin/task-manager/tasks";
        }
    });

    return TaskCollection;
});
