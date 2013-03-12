Feature: Register
    In order to register myself into the application
    As a non authenticated user
    I need to be able to submit a register form

Scenario: Give access to register page
    Given user registration is enable
    And I am not authenticated
    And I am on "/login/"
    Then I should see an "a#link-register" element

Scenario: Revoke access to register page
    Given user registration is disable
    And I am not authenticated
    And I am on "/login/register/"
    Then I should see "Registration is not available"

Scenario: Register form is displayed
    Given user registration is enable
    And I am not authenticated
    And I am on "/login/register/"
    Then I should see an "a#register-classic" element
    When I follow "a#register-classic"
    Then I should see "firstName"
    And I should see "lastName"
    And I should see "email"
    And I should see "job"
    And I should see "company"
