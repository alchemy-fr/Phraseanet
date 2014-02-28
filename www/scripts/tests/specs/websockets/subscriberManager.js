define([
    'chai',
    'sinonchai',
    'underscore',
    'squire'
], function(chai, sinonchai, _, Squire) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();
    chai.use(sinonchai);

    describe("SubscriberManager", function () {
        var i;
        beforeEach(function () {
            var $this = this;
            $this.connection = {};
            $this.connection.subscribe = sinon.spy();
            $this.connection.unsubscribe = sinon.spy();
            $this.injector = new Squire();
        });

        it("should call subscribe", function () {
            var $this = this;
            $this.injector.mock(
                ["common/websockets/connection"], $this.connection
            ).require(['common/websockets/subscriberManager'], function(manager) {
                manager.register('topic');
                expect($this.connection.subscribe.should.have.callCount(1)).to.be.ok;
                assert.ok(manager.hasCallbacks());
            });
        });

        it("should call unsubscribe", function () {
            var $this = this;
            $this.injector.mock(
                ["common/websockets/connection"], $this.connection
            ).require(['common/websockets/subscriberManager'], function(manager) {
                manager.register('topic');
                manager.unregister();
                expect($this.connection.unsubscribe.should.have.callCount(1)).to.be.ok;
                assert.ok(!manager.hasCallbacks());
            });
        });

        it("should add callbacks", function () {
            var $this = this;
            $this.injector.mock(
                    ["common/websockets/connection"], $this.connection
                ).require(['common/websockets/subscriberManager'], function(manager) {
                    assert.ok(!manager.hasCallbacks());
                    manager.pushCallback(function(){return null;});
                    assert.ok(manager.hasCallbacks());
                });
        });
    });
});


