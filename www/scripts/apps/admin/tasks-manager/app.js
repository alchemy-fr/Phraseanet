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
    "models/scheduler",
    "common/websockets/connection",
    "apps/admin/tasks-manager/views/scheduler",
    "apps/admin/tasks-manager/views/tasks",
    "apps/admin/tasks-manager/views/ping",
    "apps/admin/tasks-manager/views/refresh",
    "apps/admin/tasks-manager/collections/tasks"
], function ($, _, Backbone, Scheduler, WSConnection, SchedulerView, TasksView, PingView, RefreshView, TasksCollection) {
    var create = function() {
        window.TaskManagerApp = {
            $scope: $("#task-manager-app"),
            $tasksListView : $(".tasks-list-view", this.$scope),
            $schedulerView : $(".scheduler-view", this.$scope),
            $pingView : $(".ping-view", this.$scope),
            $refreshView : $(".refresh-view", this.$scope),
            eventAggregator: _.extend({}, Backbone.Events),
            wsuri: "ws://dev.phrasea.net:9090/websockets",
            wstopic: "http://phraseanet.com/topics/admin/task-manager"
        };

        TaskManagerApp.tasksCollection = new TasksCollection();
        TaskManagerApp.Scheduler = new Scheduler();
        TaskManagerApp.pingView = new PingView({el: TaskManagerApp.$pingView});
        TaskManagerApp.refreshView = new RefreshView({
            el: TaskManagerApp.$refreshView,
            pingView: TaskManagerApp.pingView,
            tasksCollection: TaskManagerApp.tasksCollection,
            scheduler: TaskManagerApp.Scheduler
        });
    }

    var load = function() {
        TaskManagerApp.refreshView.refreshAction();
        // fetch objects
        $.when.apply($, [
                TaskManagerApp.tasksCollection.fetch(),
                TaskManagerApp.Scheduler.fetch()
            ]).done(
            function () {
                // Init & render views
                TaskManagerApp.schedulerView = new SchedulerView({model: TaskManagerApp.Scheduler, el: TaskManagerApp.$schedulerView});
                TaskManagerApp.tasksView = new TasksView({collection: TaskManagerApp.tasksCollection, el: TaskManagerApp.$tasksListView});

                TaskManagerApp.tasksView.render();
                TaskManagerApp.schedulerView.render();

                // Sets connection to the web socket
                var ws = new WSConnection({url:TaskManagerApp.wsuri, topic: TaskManagerApp.wstopic, eventAggregator: TaskManagerApp.eventAggregator});
                ws.run();

                // On ticks re-render ping view, update tasks & scheduler model
                TaskManagerApp.eventAggregator.on("ws:manager-tick", function(response) {
                    var $this = this;
                    $this.pingView.render();
                    $this.Scheduler.set({"actual": "started", "process-id": response.message.manager["process-id"]});
                    _.each(response.message.jobs, function(data, id) {
                        var jobModel = $this.tasksCollection.get(id);
                        if ("undefined" !== jobModel) {
                            jobModel.set({"actual": data["status"], "process-id": data["process-id"]});
                        }
                    });
                });
            }
        );
    };

    var initialize = function () {
        create();
        var regexp = /task-manager/;
        $(document).ajaxComplete(function(event, request, settings) {
            if ("undefined" !== typeof settings && regexp.test(settings.url)) {
                TaskManagerApp.refreshView.loadState(false);
            }
        });

        $(document).ajaxStart(function(event, request, settings) {
            if ("undefined" !== typeof settings && regexp.test(settings.url)) {
                TaskManagerApp.refreshView.loadState(true);
            }
        });

        load();
    };

    return {
        initialize: initialize
    };
});
