Feature: Guest access
    In order to access to the application
    As a a guest
    I need to be able to log in

    Background:
        Given locale is "en_GB"

    Scenario: Give access to guests
        Given user guest access is enable
        And I am not authenticated
        And I am on "/login/"
        Then I should see an "a#guest-link" element
        When I follow "guest-link"
        Then I should be on "/client/"

    Scenario: Disable guest access
        Given user guest access is disable
        And I am not authenticated
        And I am on "/login/"
        Then I should not see an "a#guest-link" element
