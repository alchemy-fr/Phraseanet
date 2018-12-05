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
    "backbone"
], function (_, Backbone) {
    var TaskModel = Backbone.Model.extend({
        sync: function(method, model, options) {
            var options = options || {};
            options.Accept = 'application/json';
            return Backbone.sync(method, model, options);
        },
        urlRoot: function () {
            return "/admin/task-manager/" + this.get("id");
        },
        defaults: {
            "id" : null,
            "name" : null,
            "configuration": null,
            "actual": null,
            "process-id": null
        }
    });

    // Return the model for the module
    return TaskModel;
});
