Feature: Background job step definition
    As a test author
    I want to wait until a background job is running
    So that I can synchronize on async processes

    Scenario: Wait for a single running job
        When I run "sh" with "-c 'sleep 30 &'"
        Then job "sleep 30" is running

    Scenario: Wait for multiple running jobs
        When I run "sh" with "-c 'sleep 30 & sleep 30 &'"
        Then 2 jobs "sleep 30" are running
