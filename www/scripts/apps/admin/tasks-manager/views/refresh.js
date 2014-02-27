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
    "backbone"
], function ($, _, Backbone) {
    var RefreshView = Backbone.View.extend({
        initialize: function(options) {
            if (false === ("pingView" in options)) {
                throw "You must set the ping view"
            }
            this.pingView = options.pingView;
            if (false === ("scheduler" in options)) {
                throw "You must set the scheduler model"
            }
            this.scheduler = options.scheduler;
            if (false === ("tasksCollection" in options)) {
                throw "You must set the tasks collection model"
            }
            this.tasksCollection = options.tasksCollection;

            this.refreshUrl = this.$el.data('refresh-url');
        },
        events: {
            "click .btn-refresh": "refreshAction"
        },
        refreshAction: function(event) {
            var $this = this;
            $.ajax({
                dataType: "json",
                url: $this.refreshUrl,
                success: function(response) {
                    $this.pingView.render();
                    $this.scheduler.set({
                        "actual": response.manager["actual"],
                        "process-id": response.manager["process-id"],
                        "configuration": response.manager["configuration"]
                    });
                    _.each(response.tasks, function(data, id) {
                        var jobModel = $this.tasksCollection.get(id);
                        if ("undefined" !== typeof jobModel) {
                            jobModel.set({
                                "actual": data["actual"],
                                "process-id": data["process-id"],
                                "configuration": data["configuration"]
                            });
                        }
                    });
                }
            });
        },
        loadState: function(state) {
            if (state) {
                $("#spinner", this.$el).addClass('icon-spinner icon-spin');
            } else {
                $("#spinner", this.$el).removeClass('icon-spinner icon-spin');
            }
        }
    });

    return RefreshView;
});
