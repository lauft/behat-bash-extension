Feature: Working directory step definitions
    As a test author
    I want to change the working directory
    So that subsequent commands run in the right place

    Scenario: Change into a relative directory that already exists
        Given there is a directory "existing"
        When I am in directory "existing"
        Then I run "pwd" with ""
        And the output should match:
            """
            .*existing$
            """

    Scenario: Change into a relative directory that does not yet exist
        When I am in directory "var/sqs/myqueue/wait"
        Then I run "pwd" with ""
        And the output should match:
            """
            .*myqueue[\/]wait$
            """

    Scenario: Change into a nested directory auto-creates parents
        When I am in directory "a/b/c"
        Then I run "pwd" with ""
        And the output should match:
            """
            .*a[\/]b[\/]c$
            """
