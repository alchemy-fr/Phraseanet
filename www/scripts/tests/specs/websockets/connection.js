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
                    $this.wsConnection.setSession($this.session);
                };
                window.ab = {
                    connect: function(url, cbSuccess, cbError) {
                        cbSuccess($this.session);
                    }
                }
            });

            afterEach(function () {
                this.wsConnection.close();
            });

            it("should have a session", function () {
                this.wsConnection.connect();
                assert.ok(this.wsConnection.hasSession());
            });

            it("should retrieve the session", function () {
                this.wsConnection.connect();
                assert.equal(this.wsConnection.getSession().hello, this.session.hello);
            });

            it("should close the session", function () {
                this.wsConnection.connect();
                assert.ok(this.wsConnection.hasSession());
                this.wsConnection.close();
                assert.ok(!this.wsConnection.hasSession());
                assert.equal(this.wsConnection.getSession(), null);
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
                expect(this.wsConnection.getSession().subscribe.should.have.callCount(1)).to.be.ok;
            });

            it("should call session unsubscribe once", function () {
                this.wsConnection.connect();
                this.wsConnection.unsubscribe();
                expect(this.wsConnection.getSession().unsubscribe.should.have.callCount(1)).to.be.ok;
            });
        });
    });
});


