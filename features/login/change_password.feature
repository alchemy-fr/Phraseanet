Feature: Change password
    In order to login into the application if my password is not defined
    As an authorized user
    I need to be able to change my password

    Background:
        Given locale is "en_GB"

    Scenario: Submit change password form with valid credentials
        Given a user "john.doe@phraseanet.com" exists with a valid password token "token"
        And "john.doe@phraseanet.com" is not authenticathed
        And I am on "/login/change-password/token=token"
        When I fill in "password" with "password"
        And I fill in "password-confirm" with "password"
        And I press "submit-form"
        Then I should see "Your password has been updated"

    @javascript
    Scenario: Submit change password form with blank password
        Given a user "john.doe@phraseanet.com" exists with a valid password token "token"
        And "john.doe@phraseanet.com" is not authenticathed
        And I am on "/login/change-password/token=token"
        When I fill in "password" with ""
        And I press "submit-form"
        Then I should see "This field is required"

    @javascript
    Scenario: Submit change password form with blank passwordConfirm
        Given a user "john.doe@phraseanet.com" exists with a valid password token "token"
        And "john.doe@phraseanet.com" is not authenticathed
        And I am on "/login/change-password/token=token"
        When I fill in "password-confirm" with ""
        And I press "submit-form"
        Then I should see "This field is not valid"

    @javascript
    Scenario: Submit change password form with different password
        Given a user "john.doe@phraseanet.com" exists with a valid password token "token"
        And "john.doe@phraseanet.com" is not authenticathed
        And I am on "/login/change-password/token=token"
        When I fill in "password" with "password1"
        And I fill in "password-cnfirm" with "password2"
        And I press "submit-form"
        Then I should see "Password don't match"

    @javascript
    Scenario: Acces to change password pass with an invalid token
        Given a user "john.doe@phraseanet.com" exists
        And "john.doe@phraseanet.com" is not authenticated
        And I am on "/login/change-password/token=invalid"
        Then I should be "/login/"
        And I should see "You can't access to this page"

    @javascript
    Scenario: Acces to change password pass as an authenticated user
        Given a user "john.doe@phraseanet.com" exists
        And "john.doe@phraseanet.com" is authenticated
        And I am on "/login/change-password/"
        Then I should be "/prod/"
