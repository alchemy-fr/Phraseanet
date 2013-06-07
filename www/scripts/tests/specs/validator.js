define([
    'chai',
    'common/validator'
], function(chai, Validator) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();
    describe("Validator", function(){
        describe("Validator rules", function(){

            beforeEach(function() {
                this.validator = new Validator([{
                    name: "required",
                    rules: "required"
                },{
                    name: "valid_email",
                    rules: "valid_email"
                },{
                    name: "equal",
                    rules: "equal[toto]"
                },{
                    name: "matches",
                    rules: "matches[to_match]"
                },{
                    name: "valid_emails",
                    rules: "valid_emails"
                },{
                    name: "min_length",
                    rules: "min_length[5]"
                },{
                    name: "max_length",
                    rules: "max_length[5]"
                },{
                    name: "exact_length",
                    rules: "exact_length[5]"
                },{
                    name: "greater_than",
                    rules: "greater_than[5]"
                },{
                    name: "less_than",
                    rules: "less_than[5]"
                },{
                    name: "alpha",
                    rules: "alpha"
                },{
                    name: "alpha_numeric",
                    rules: "alpha_numeric"
                },{
                    name: "alpha_dash",
                    rules: "alpha_dash"
                },{
                    name: "numeric",
                    rules: "numeric"
                },{
                    name: "integer",
                    rules: "integer"
                },{
                    name: "decimal",
                    rules: "decimal"
                },{
                    name: "is_natural",
                    rules: "is_natural"
                },{
                    name: "is_natural_no_zero",
                    rules: "is_natural_no_zero"
                },{
                    name: "valid_ip",
                    rules: "valid_ip"
                },{
                    name: "valid_url",
                    rules: "valid_url"
                }]);
            });

            it("should detect an error if field is required and value is blank", function() {
                this.validator.validate([{
                    name :"required",
                    value: ""
                }]);
                this.validator.getErrors().length.should.equal(1);
            });

            it("should detect an error if field is not a valid email", function() {
                this.validator.validate([{
                    name :"valid_email",
                    value: "email.not.va@"
                }]);
                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field is a valid email", function() {
                this.validator.validate([{
                    name :"valid_email",
                    value: "valid@email.com"
                }]);
                this.validator.getErrors().length.should.equal(0);

                this.validator.validate([{
                    name :"valid_email",
                    value: "valid+34@email.com"
                }]);
                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field is not a valid emails", function() {
                this.validator.validate([{
                    name :"valid_emails",
                    value: "valid@email.com, email.not.va@"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field is a valid emails", function() {
                this.validator.validate([{
                    name :"valid_emails",
                    value: "valid32@email.com, valid2@email.com"
                }]);
                this.validator.getErrors().length.should.equal(0);

                this.validator.validate([{
                    name :"valid_emails",
                    value: "valid@email.com"
                }]);
                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field is not equal to default string toto", function() {
                this.validator.validate([{
                    name :"equal",
                    value: "tata"
                }]);
                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field is equal to default string toto", function() {
                this.validator.validate([{
                    name :"equal",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'match 'is not equal to field value 'to_match'", function() {
                this.validator.validate([{
                    name :"matches",
                    value: "toto"
                }, {
                    name: "to_match",
                    value: "tata"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'match' is equal to field value 'to_match'", function() {
                this.validator.validate([{
                    name :"matches",
                    value: "toto"
                }, {
                    name: "to_match",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'min_length' is < to 5", function() {
                this.validator.validate([{
                    name :"min_length",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'min_length' is >= to 5", function() {
                this.validator.validate([{
                    name :"min_length",
                    value: "totos"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'max_length' is > to 5", function() {
                this.validator.validate([{
                    name :"max_length",
                    value: "tostos"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'max_length' is <= to 5", function() {
                this.validator.validate([{
                    name :"max_length",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'greater_than' is < to 5", function() {
                this.validator.validate([{
                    name :"greater_than",
                    value: "3"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'greater_than' is > to 5", function() {
                this.validator.validate([{
                    name :"greater_than",
                    value: "6"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'less_than' is > to 5", function() {
                this.validator.validate([{
                    name :"less_than",
                    value: "6"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'less_than' is <= to 5", function() {
                this.validator.validate([{
                    name :"less_than",
                    value: "3"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'exact_length' is = to 5", function() {
                this.validator.validate([{
                    name :"exact_length",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'exact_length' is = to 5", function() {
                this.validator.validate([{
                    name :"exact_length",
                    value: "totos"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'alpha' is not alpha", function() {
                this.validator.validate([{
                    name :"alpha",
                    value: "toto12"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'alpha' is alpha", function() {
                this.validator.validate([{
                    name :"alpha",
                    value: "totos"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'alpha_numeric' is not alpha numeric", function() {
                this.validator.validate([{
                    name :"alpha_numeric",
                    value: "toto#"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'alpha_numeric' is alpha numeric", function() {
                this.validator.validate([{
                    name :"alpha_numeric",
                    value: "totos12"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'numeric' is not numeric", function() {
                this.validator.validate([{
                    name :"numeric",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'numeric' is numeric", function() {
                this.validator.validate([{
                    name :"numeric",
                    value: "123"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'integer' is not integer", function() {
                this.validator.validate([{
                    name :"integer",
                    value: "3.44"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'integer' is integer", function() {
                this.validator.validate([{
                    name :"integer",
                    value: "123"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'decimal' is not decimal", function() {
                this.validator.validate([{
                    name :"decimal",
                    value: "23a"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'decimal' is decimal", function() {
                this.validator.validate([{
                    name :"decimal",
                    value: "1.23"
                }]);

                this.validator.getErrors().length.should.equal(0);

                this.validator.validate([{
                    name :"decimal",
                    value: "123"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'natural' is not natural", function() {
                this.validator.validate([{
                    name :"is_natural",
                    value: "-2"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'natural' is natural", function() {
                this.validator.validate([{
                    name :"is_natural",
                    value: "0"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'is_natural_no_zero' is not a natural no zero", function() {
                this.validator.validate([{
                    name :"is_natural_no_zero",
                    value: "0"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'is_natural_no_zero' is a natural no zero", function() {
                this.validator.validate([{
                    name :"is_natural_no_zero",
                    value: "1"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'valid_ip' is not a valid ip", function() {
                this.validator.validate([{
                    name :"valid_ip",
                    value: "12.8.1.187.2"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_ip",
                    value: "coucou"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_ip",
                    value: "1234.12.12"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_ip",
                    value: "0.0"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'valid_ip' a valid ip", function() {
                this.validator.validate([{
                    name :"valid_ip",
                    value: "127.0.0.1"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });

            it("should detect an error if field value 'valid_url' is not a valid http url", function() {
                this.validator.validate([{
                    name :"valid_url",
                    value: "toto"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_url",
                    value: "toto.123s"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_url",
                    value: "http:/#toto.com"
                }]);

                this.validator.getErrors().length.should.equal(1);

                this.validator.validate([{
                    name :"valid_url",
                    value: "htp:/toto.com"
                }]);

                this.validator.getErrors().length.should.equal(1);
            });

            it("should not detect an error if field value 'valid_url' is a valid http url", function() {
                this.validator.validate([{
                    name :"valid_url",
                    value: "http://valid.url.com"
                }]);

                this.validator.getErrors().length.should.equal(0);
                this.validator.validate([{
                    name :"valid_url",
                    value: "https://valid.url.com"
                }]);

                this.validator.getErrors().length.should.equal(0);

                this.validator.getErrors().length.should.equal(0);

                this.validator.validate([{
                    name :"valid_url",
                    value: "http://valid.url.com/?test=3"
                }]);

                this.validator.getErrors().length.should.equal(0);

                this.validator.validate([{
                    name :"valid_url",
                    value: "http://valid.url.com/?test=3#salut"
                }]);

                this.validator.getErrors().length.should.equal(0);
            });
        });
    });
});


