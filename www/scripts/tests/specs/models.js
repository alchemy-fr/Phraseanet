define([
    'chai',
    'models/field',
    'models/dcField',
    'models/vocabulary'
], function (chai, Field, DcField, Vocabulary) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();
    describe("Models", function () {
        describe("Field Model", function () {
            describe("Initialization", function () {
                var sbasId = 1;

                beforeEach(function () {
                    this.field = new Field({
                        "sbas-id": sbasId
                    });
                });

                it("should throw an exception if 'sbas-id' is missing", function () {
                    expect(function () {
                        new Field();
                    }).to.throw("You must set a sbas id");
                });

                it("should set model url according to provided 'sbas-id'", function () {
                    this.field.urlRoot().should.equal("/admin/fields/" + sbasId + "/fields");
                });

                it("should default business property to 'false'", function () {
                    this.field.get('business').should.be.false;
                });

                it("should default type property to 'string'", function () {
                    this.field.get('type').should.equal("string");
                });

                it("should default thumbtitle property to '0'", function () {
                    this.field.get('thumbtitle').should.equal("0");
                });

                it("should default tbranch property to 'empty'", function () {
                    this.field.get('tbranch').should.equal("");
                });

                it("should default separator property to 'empty'", function () {
                    this.field.get('separator').should.equal("");
                });

                it("should default required property to 'false'", function () {
                    this.field.get('required').should.be.false;
                });

                it("should default readonly property to 'false'", function () {
                    this.field.get('readonly').should.be.false;
                });

                it("should default multi property to 'false'", function () {
                    this.field.get('multi').should.be.false;
                });

                it("should default vocabulary-restricted property to 'false'", function () {
                    this.field.get('vocabulary-restricted').should.be.false;
                });

                it("should default vocabulary-restricted property to 'false'", function () {
                    this.field.get('vocabulary-restricted').should.be.false;
                });

                it("should default report property to 'true'", function () {
                    this.field.get('report').should.be.true;
                });

                it("should default indexable property to 'true'", function () {
                    this.field.get('indexable').should.be.true;
                });

                it("should default dces-element property to 'null'", function () {
                    expect(this.field.get('dces-element')).to.be.null;
                });

                it("should default vocabulary-type property to 'null'", function () {
                    expect(this.field.get('vocabulary-type')).to.be.null;
                });
            });
        });

        describe("DcField Model", function () {
            describe("Initialization", function () {
                beforeEach(function () {
                    this.dcField = new DcField();
                });

                it("should set proper model url", function () {
                    this.dcField.urlRoot().should.equal("/admin/fields/dc-fields");
                });
            });
        });

        describe("DcField Model", function () {
            describe("Initialization", function () {
                beforeEach(function () {
                    this.vocabulary = new Vocabulary();
                });

                it("should set proper model url", function () {
                    this.vocabulary.urlRoot().should.equal("/admin/fields/vocabularies");
                });
            });
        });
    });
});


