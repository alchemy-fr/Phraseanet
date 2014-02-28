define([
    'chai',
    'sinonchai',
    'underscore',
    'common/websockets/connection'
], function (chai, sinonchai, _, connection) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();
    chai.use(sinonchai);

    describe("Connection", function () {
        describe("Functionnal", function () {
            beforeEach(function () {
                this.session = {"hello":"session"};
                this.session.close = sinon.spy();
                this.session.subscribe = sinon.spy();
                this.session.unsubscribe = sinon.spy();

                this.wsConnection = connection;
                var $this = this;
                var cbSuccess = function (session) {
                    activeSession = $this.session;
                };
                window.ab = {
                    connect: function(url, cbSuccess, cbError) {
                        cbSuccess($this.session);
                    }
                }
            });

            afterEach(function () {
                if (this.wsConnection.isConnected()) {
                    this.wsConnection.close();
                }
            });

            it("should have a session", function () {
                this.wsConnection.connect();
                assert.ok(this.wsConnection.isConnected());
            });

            it("should close the session", function () {
                this.wsConnection.connect();
                assert.ok(this.wsConnection.isConnected());
                this.wsConnection.close();
                assert.ok(!this.wsConnection.isConnected());
            });

            it("should warn if you close the session and you are not connected", function () {
                var throws = false;
                try {
                    this.wsConnection.close();
                } catch (e) {
                    throws = true;
                }

                assert.ok(throws);
            });

            it("should not connect anymore after first connect", function () {
                var throws = false;
                this.wsConnection.connect();
                try {
                    this.wsConnection.connect();
                } catch (e) {
                    throws = true;
                }

                assert.ok(throws);
            });

            it("should call session subscribe once", function () {
                this.wsConnection.connect();
                this.wsConnection.subscribe();
                expect(this.session.subscribe.should.have.callCount(1)).to.be.ok;
            });

            it("should call session unsubscribe once", function () {
                this.wsConnection.connect();
                this.wsConnection.unsubscribe();
                expect(this.session.unsubscribe.should.have.callCount(1)).to.be.ok;
            });
        });
    });
});
