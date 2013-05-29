Feature: Locale
    In order to use the application with my native language
    As a user
    I need to be able to change the application locale

    Background:
        Given locale is "en_GB"

    Scenario: Change the application language to german
        Given I am not authenticated
        And locale is "en_GB"
        And I am on "/login/"
        When I press "language-button"
        And I click "a[data-country-code='de_DE']"
        Then I should see "Benutzername"

    Scenario: Change the application language to french
        Given I am not authenticated
        And locale is "en_GB"
        And I am on "/login/"
        When I press "language-button"
        And  I click "a[data-country-code='fr_FR']"
        Then I should see "Connection"

    Scenario: Change the application language to english
        Given I am not authenticated
        And locale is "fr_FR"
        And I am on "/login/"
        When I press "language-button"
        And  I click "a[data-country-code='en_GB']"
        Then I should see "Login"
