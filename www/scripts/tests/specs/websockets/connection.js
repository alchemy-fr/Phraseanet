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

                this.wsConnection = connection.getInstance();
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
                this.wsConnection.connect();
                ab.connect = sinon.spy();
                this.wsConnection.connect();
                this.wsConnection.connect();
                this.wsConnection.connect();
                expect(ab.connect.should.have.callCount(0)).to.be.ok;
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


