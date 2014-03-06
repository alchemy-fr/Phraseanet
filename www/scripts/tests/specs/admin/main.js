define([
    'chai',
    'fixtures',
    'jquery',
    'apps/admin/main/app',
    'apps/admin/main/views/leftPanel',
    'apps/admin/main/views/rightPanel'
], function (chai, fixtures, $, App, LeftPanel, RightPanel) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();

    fixtures.path = 'fixtures';
    $("body").append(fixtures.read('admin/main/left-panel.html'));

    App.create();

    describe("Admin main", function () {
        describe("Initialization", function () {
            it("should create a global variable", function () {
                should.exist(AdminApp);
            });
        });

        describe("Views", function () {
            beforeEach(function() {
                this.xhr = sinon.useFakeXMLHttpRequest();
                this.server = sinon.fakeServer.create();
            });
            afterEach(function() {
                this.xhr.restore();
                this.server.restore();
            });
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
                    var requests = [];
                    this.xhr.onCreate = function (req) { requests.push(req); };

                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, treeUrl: "/admin/tree/"});
                    leftP.reloadTree();

                    assert.equal(requests.length, 1);
                    assert.equal(requests[0].url, "/admin/tree/");
                });

                it("should make ajax request if we click link", function () {
                    var requests = [];
                    this.xhr.onCreate = function (req) { requests.push(req); };

                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, el:$(".left-view"), treeUrl: "toto"});
                    leftP.$('a[target=right]:first').trigger("click");
                    assert.ok(requests.length > 0);
                });

                it("triggers right view", function () {
                    var trigger = false;
                    AdminApp.eventManager.on("panel:right:complete", function() {
                        trigger = true;
                    });
                    var leftP = new LeftPanel({eventManager : AdminApp.eventManager, el:$(".left-view"), treeUrl: "toto"});
                    leftP.activeTree();
                    leftP.$('a[target=right]:first').trigger("click");
                    this.server.requests[0].respond(
                        200,
                        { "Content-Type": "application/json" },
                        ''
                    );

                    assert.ok(trigger);
                });
            });

            describe("rightView", function () {
                it("should throw an exception if 'event manager' is missing", function () {
                    expect(function () {
                        new RightPanel();
                    }).to.throw("You must set en event manager");
                });

                it("should make ajax request if we click link", function () {
                    var requests = [];
                    this.xhr.onCreate = function (req) { requests.push(req); };
                    $(".right-view").html('<a href="toto">test</a>');
                    var rightP = new RightPanel({eventManager : AdminApp.eventManager, el:$(".right-view")});
                    rightP.$('a').trigger('click');
                    assert.ok(requests.length > 0);
                });

                it("should make ajax request if we click form", function () {
                    var requests = [];
                    this.xhr.onCreate = function (req) { requests.push(req); };
                    $(".right-view").html('<form method="POST" action="toto"></form>');
                    var rightP = new RightPanel({eventManager : AdminApp.eventManager, el:$(".right-view")});
                    rightP.$('form:first').trigger('submit');
                    assert.ok(requests.length > 0);
                });
            });
        });
    });
});
