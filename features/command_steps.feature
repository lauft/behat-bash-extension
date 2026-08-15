Feature: Command execution step definitions
    As a test author
    I want to run shell commands and assert on exit code and output
    So that I can verify tool behaviour

    Scenario: A passing command
        When I run "true" with ""
        Then it should pass

    Scenario: A failing command
        When I run "false" with ""
        Then it should fail

    Scenario: Assert output contains text
        When I run "echo" with "hello"
        Then it should pass
        And the output should contain:
            """
            hello
            """

    Scenario: Assert exact pass output
        When I run "echo" with "hi"
        Then it should pass with:
            """
            hi
            """

    Scenario: Assert output matches a regex
        When I run "echo" with "42"
        Then the output should match:
            """
            \d+
            """
