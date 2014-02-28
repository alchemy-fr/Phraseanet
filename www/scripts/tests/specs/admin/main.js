define([
    'chai',
    'fixtures',
    'jquery',
    'squire',
    'apps/admin/main/app',
    'apps/admin/main/views/leftPanel',
    'apps/admin/main/views/rightPanel'
], function (chai, fixtures, $, Squire, App, LeftPanel, RightPanel) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();

    fixtures.path = 'fixtures';
    $("body").append(fixtures.read('admin/main/left-panel.html'));
    $("right-view").append(fixtures.read('admin/main/right-panel.html'));

    App.create();

    describe("Admin main", function () {
        describe("Initialization", function () {
            it("should create a global variable", function () {
                should.exist(AdminApp);
            });
        });

        describe("Views", function () {
            describe("leftView", function () {
                it("should throw an exception if 'event manager' is missing", function () {
                    expect(function () {
                        new LeftPanel();
                    }).to.throw("You must set an event manager");

                    expect(function () {
                        new LeftPanel({eventManager: null});
                    }).to.throw("You must set the tree url");
                });

                it("should make ajax request if we reload", function () {
                    var xhr = sinon.useFakeXMLHttpRequest();
                    var requests = [];
                    xhr.onCreate = function (req) { requests.push(req); };

                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, treeUrl: "/admin/tree/"});
                    leftP.reloadTree();

                    assert.equal(requests.length, 1);
                    assert.equal(requests[0].url, "/admin/tree/");

                    xhr.restore();
                });

                it("should make ajax request if we click link", function () {
                    var xhr = sinon.useFakeXMLHttpRequest();
                    var requests = [];
                    xhr.onCreate = function (req) { requests.push(req); };

                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, el:$(".left-view"), treeUrl: "toto"});
                    leftP.$('a[target=right]:first').trigger("click");
                    assert.equal(requests.length, 1);
                    xhr.restore();
                });

                it("triggers right view", function () {
                    var trigger = false;
                    AdminApp.eventManager.on("panel:right:complete", function() {
                        trigger = true;
                    });
                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, el:$(".left-view"), treeUrl: "toto"});
                    var rightP = new RightPanel({eventManager : AdminApp.eventManager, el:$(".right-view")});
                    var server = sinon.fakeServer.create();
                    leftP.activeTree();
                    leftP.$('a[target=right]:first').trigger("click");
                    server.requests[0].respond(
                        200,
                        { "Content-Type": "application/json" },
                        ''
                    );

                    assert.ok(trigger);
                    server.restore();
                });
            });

            describe("rightView", function () {
                it("should throw an exception if 'event manager' is missing", function () {
                    expect(function () {
                        new RightPanel();
                    }).to.throw("You must set en event manager");
                });

                it("should make ajax request if we click link", function () {
                    var xhr = sinon.useFakeXMLHttpRequest();
                    var requests = [];
                    xhr.onCreate = function (req) { requests.push(req); };

                    var rightP = new RightPanel({eventManager : AdminApp.eventManager, el:$(".right-view")});
                    rightP.$('a:not(.no-ajax)').trigger("click");
                    assert.equal(requests.length, 1);
                    xhr.restore();
                });
            });
        });
    });
});
