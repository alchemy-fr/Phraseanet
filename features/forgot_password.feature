Feature: Forgot password
  In order to log in into the application
  As a non authenticated user
  I need to be able to get a new password if I forget my own password

Scenario: Give access to forgot password page
    Given I am not authenticated
    When I am on "/login/"
    Then I should see "Forgot password?"
    When I follow "Forgot password?"
    Then I should be on "/login/forgot-password/"

@javascript
Scenario: Submit forgot password form with blank email
  Given I am not authenticated
  And I am on "/login/forgot-password/"
  When I fill in "email" with ""
  And I press "submit-form"
  Then I should see "This field is required"

@javascript
Scenario: Submit forgot password form with invalid email
  Given I am not authenticated
  And I am on "/login/forgot-password/"
  When I fill in "email" with "invalid_email@"
  And I press "submit-form"
  Then I should see "This field is not valid"

@javascript
Scenario: Submit forgot password form with unknown user
    Given a user "jane.doe@phraseanet.com" does not exist
    And I am not authenticated
    And I am on "/login/forgot-password/"
    When I fill in "email" with "jane.doe@phraseanet.com"
    And I press "submit-form"
    Then I should see "Unknown user"

@javascript
Scenario: Submit forgot password form with an existing user
    Given a user "john.doe@phraseanet.com" exists
    And I am not authenticated
    When I am on "/login/forgot-password/"
    When I fill in "email" with "john.doe@phraseanet.com"
    And I press "submit-form"
    Then I should see "An email has been sent to you"
