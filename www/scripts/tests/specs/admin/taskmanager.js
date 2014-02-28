define([
    'chai',
    'fixtures',
    'jquery',
    'apps/admin/tasks-manager/app',
    'models/task',
    'apps/admin/tasks-manager/collections/tasks',
    'apps/admin/tasks-manager/views/ping',
    'apps/admin/tasks-manager/views/task',
    'apps/admin/tasks-manager/views/tasks'
], function (chai, fixtures, $, App, TaskModel, TaskCollection, PingView, TaskView, TasksView) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();

    // Note: fixture are loaded into scripts/tests/fixtures directory using
    // bin/developer phraseanet:regenerate-js-fixtures
    fixtures.path = 'fixtures';
    $("body").append(fixtures.read('admin/task-manager/templates.html', 'admin/task-manager/index.html'));

    App.create();

    describe("Admin task manager", function () {
        describe("Initialization", function () {
            it("should create a global variable", function () {
                should.exist(TaskManagerApp);
            });
        });

        describe("Views", function () {
            describe("TaskView", function () {
                beforeEach(function () {
                    this.view = new TaskView({
                        model: new TaskModel({
                            "name":"Task", "configuration":"start", "actual": "stopped", "id":1, "urls" : []
                        })
                    });
                });

                it("render() should return the view object", function () {
                    this.view.render().should.equal(this.view);
                    this.view.renderId().should.equal(this.view);
                    this.view.renderConfiguration().should.equal(this.view);
                    this.view.renderActual().should.equal(this.view);
                    this.view.renderPid().should.equal(this.view);
                    this.view.renderName().should.equal(this.view);
                });

                it("should render as a TR element", function () {
                    this.view.render().el.nodeName.should.equal("TR");
                });
            });

            describe("Empty Tasks item views", function () {
                beforeEach(function () {
                    this.collection = new TaskCollection([]);
                    this.view = new TasksView({
                        collection: this.collection,
                        el: TaskManagerApp.$tasksListView
                    });
                });

                it("should include list items for all models in collection", function () {
                    this.view.render();
                    this.view.$el.find("tr").length.should.equal(0);
                });
            });

            describe("Tasks Item Views", function () {
                beforeEach(function () {
                    this.collection = new TaskCollection([
                        {"name" : "task", "actual":"stopped", "configuration": "start", "urls" : []},
                        {"name" : "task2", "actual":"stopped", "configuration": "start", "urls" : []}
                    ]);

                    this.view = new TasksView({
                        collection: this.collection,
                        el: TaskManagerApp.$tasksListView
                    });
                });

                it("render() should return the view object", function () {
                    this.view.render().should.equal(this.view);
                });

                it("should include list items for all models in collection", function () {
                    this.view.render();
                    this.view.$el.find("tr").length.should.equal(2);
                });
            });

            describe("Ping View", function () {
                beforeEach(function () {
                    this.view = new PingView();
                });

                it("render() should return the view object", function () {
                    this.view.render().should.equal(this.view);
                });
            });
        });
    });
});
