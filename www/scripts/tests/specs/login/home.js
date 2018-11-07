define([
    'chai',
    'fixtures',
    'jquery',
    'common/forms/views/form',
    'common/forms/views/input',
    'common/forms/views/error'
], function (chai, fixtures, $, FormView, InputView, ErrorView) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();
    // Note: fixture are loaded into scripts/tests/fixtures directory using
    // bin/developer phraseanet:regenerate-js-fixtures
    fixtures.path = 'fixtures';
    $("body").append(fixtures.read('home/login/index.html', 'home/login/templates.html'));

    describe("Login Home", function () {
        describe("Form View", function () {
            it("should initialize validator with proper rules", function () {
                var rules = [
                    {name: "hello", rules: "simple_rules"}
                ];
                var form = new FormView({
                    el: $('form[name=loginForm]'),
                    rules: rules
                });
                form.validator.getRules().should.equal(rules);
            });

            it("should initialize input views", function () {
                var form = new FormView({
                    el: $('form[name=loginForm]')
                });

                Object.keys(form.inputViews).length.should.equal(4);
            });

            it("should initialize errors", function () {
                var form = new FormView({
                    el: $('form[name=loginForm]')
                });

                assert.isTrue(_.isEmpty(form.validator.getErrors()));
            });

            it("should render errors on submit", function () {
                var form = new FormView({
                    el: $('form[name=loginForm]'),
                    rules: [
                        {
                            "name": "login",
                            "rules": "required",
                            "message": "something is wrong"
                        }
                    ]
                });

                form._onSubmit(document.createEvent("Event"));
                assert.isTrue(form.inputViews["login"].errorView.$el.html().indexOf("something is wrong") !== -1);
            });
        });

        describe("Input View", function () {
            it("should initialize error view", function () {
                var input = new InputView({
                    "name": "test",
                    "errorTemplate": "#field_errors"
                });
                expect(input.errorView).to.be.an.instanceof(ErrorView);
            });
        });

        describe("Error View", function () {
            it("should render errors", function () {
                var error = {
                    name: "test",
                    message: "Something is wrong"
                };

                var errorView = new ErrorView({
                    "name": "test",
                    "errors": [error],
                    "el": $(".error-view").first(),
                    "errorTemplate": "#field_errors"
                });

                errorView.render();

                assert.isTrue(errorView.$el.html().indexOf(error.message) !== -1);
            });

            it("should empty errors content if there are no errors", function () {
                var $el = $(".error-view").first();

                $el.html('previous error here');

                var errorView = new ErrorView({
                    "name": "test",
                    "errors": [],
                    "el": $el,
                    "errorTemplate": "#field_errors"
                });

                errorView.render();

                assert.isTrue(errorView.$el.html() === "");
            });
        });
    });
});
