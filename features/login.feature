Feature: Login
    In order to log in into the application
    As a non authenticated user
    I need to be able to submit a login form

    Background:
        Given locale is "en_GB"

    Scenario: Login form is displayed
        Given I am not authenticated
        When I am on "/login/"
        Then I should see "login"
        And I should see "password"

    @javascript
    Scenario: Submit login form with valid credentials
        Given a user "john.doe@phraseanet.com" exists with "johndoe" as password
        And "john.doe@phraseanet.com" is not authenticated
        When I am on "/login/"
        And I fill in "login" with "john.doe@phraseanet.com"
        And I fill in "password" with "johndoe"
        And I press "submit-form"
        Then I should be on "/prod/"

    @javascript
    Scenario: Submit login form with invalid credentials
        Given a user "john.doe@phraseanet.com" exists with "johndoe" as password
        And "john.doe@phraseanet.com" is not authenticated
        When I am on "/login/"
        And I fill in "login" with "john.doe@phraseanet.com"
        And I fill in "password" with "no-valid"
        And I press "submit-form"
        Then I should see "Authentication error"

    @javascript
    Scenario: Submit login form with blank login
        Given I am not authenticated
        And I am on "/login/"
        When I fill in "login" with ""
        And I fill in "password" with "johndoe"
        And I press "submit-form"
        Then I should see "This field is required"

    @javascript
    Scenario: Submit login form with blank password
        Given I am not authenticated
        And I am on "/login/"
        When I fill in "login" with "john.doe@mail.com"
        And I fill in "password" with ""
        And I press "submit-form"
        Then I should see "This field is required"

    @javascript
    Scenario: Submit login form with invalid credentials 3 times
        Given I am not authenticated
        And captcha system is enable
        And I am on "/login/"
        When I fill in "login" with "john.doe@mail.com"
        And I fill in "password" with "invalid-credential"
        And I press "submit-form"
        And I fill in "login" with "john.doe@mail.com"
        And I fill in "password" with "invalid-credential"
        And I press "submit-form"
        And I fill in "login" with "john.doe@mail.com"
        And I fill in "password" with "invalid-credential"
        And I press "submit-form"
        Then I should see an "input#recaptcha_response_field" element

    @javascript
    Scenario: Access to login as an authenticated user
        Given a user "john.doe@phraseanet.com" exists
        And "john.doe@phraseanet.com" is authenticated
        When I am on "/login/"
        Then I should be on "/prod/"
