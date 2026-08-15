Feature: Environment variable step definition
    As a test author
    I want to set environment variables for subsequent commands
    So that I can control command behaviour

    Scenario: Set and read an environment variable
        When "MY_TEST_VAR" environment variable is set to:
            """
            abc123
            """
        When I run "sh" with "-c 'printf $MY_TEST_VAR'"
        Then it should pass with:
            """
            abc123
            """
