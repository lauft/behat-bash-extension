Feature: File step definitions
    As a test author
    I want to create and assert on files
    So that I can set up fixtures and verify output

    Scenario: Create a file with content
        Given a file named "config.yml" with:
            """
            key: value
            """
        Then file "config.yml" should exist
        And "config.yml" file should contain:
            """
            key: value
            """

    Scenario: Assert exact file content
        Given a file named "exact.txt" with:
            """
            hello world
            """
        Then there should be a file named "exact.txt" with:
            """
            hello world
            """

    Scenario: Assert a file does not exist
        Then file "absent.txt" should not exist

    Scenario: Create a file in a nested path
        Given a file named "nested/deep/file.txt" with:
            """
            nested content
            """
        Then file "nested/deep/file.txt" should exist
