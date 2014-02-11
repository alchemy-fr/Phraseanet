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
    "apps/admin/tasks-manager/views/scheduler",
    "apps/admin/tasks-manager/views/tasks",
    "apps/admin/tasks-manager/views/ping",
    "apps/admin/tasks-manager/collections/tasks"
], function ($, _, Backbone, Scheduler, SchedulerView, TasksView, PingView, TasksCollection) {
    var create = function() {
        window.TaskManagerApp = {
            $scope: $("#task-manager-app"),
            $tasksListView : $("#tasks-list-view", this.$scope),
            $schedulerView : $("#scheduler-view", this.$scope),
            $pingView : $("#pingTime", this.$scope)
        };

        TaskManagerApp.tasksCollection = new TasksCollection();
        TaskManagerApp.Scheduler = new Scheduler();

        TaskManagerApp.pingView = new PingView({
            el: TaskManagerApp.$pingView
        });
    }

    var load = function() {
        // fetch objects
        $.when.apply($, [
                TaskManagerApp.tasksCollection.fetch(),
                TaskManagerApp.Scheduler.fetch()
            ]).done(
            function () {
                TaskManagerApp.schedulerView = new SchedulerView({
                    model: TaskManagerApp.Scheduler,
                    el: TaskManagerApp.$schedulerView
                });
                TaskManagerApp.tasksView = new TasksView({
                    collection: TaskManagerApp.tasksCollection,
                    el: TaskManagerApp.$tasksListView
                });

                // render views
                TaskManagerApp.tasksView.render();
                TaskManagerApp.schedulerView.render();
            }
        );
    };

    var initialize = function () {
        create();
        load();
    };

    return {
        create: create,
        load: load,
        initialize: initialize
    };
});
