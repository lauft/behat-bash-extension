Feature: Directory step definitions
    As a test author
    I want to create and assert on directories
    So that I can set up and verify folder structures

    Scenario: Create a directory with a relative path
        Given there is a directory "lockfile"
        Then directory "lockfile" should exist

    Scenario: Assert a directory does not exist
        Then directory "missing" should not exist

    Scenario: Create a nested directory
        Given there is a directory "parent/child"
        Then directory "parent/child" should exist
