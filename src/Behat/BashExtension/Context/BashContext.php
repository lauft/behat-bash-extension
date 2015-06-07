<?php

namespace Lauft\Behat\BashExtension\Context;

use PHPUnit_Framework_Assert;
use Behat\Gherkin\Node\PyStringNode;

/**
 * BashContext context for Behat BDD tool.
 * Provides bash base step definitions.
 */
class BashContext extends RawBashContext
{
    /**
     * @Given /^I am in directory "([^"]*)"$/
     * @When /^I change to directory "([^"]*)"$/
     *
     * @param string $path
     */
    public function iChangeToDirectory($path)
    {
        $this->changeDirectory($path);
    }

    /**
     * @Given /^there is a directory "([^"]*)"$/
     * @When /^I create directory "([^"]*)"$/
     *
     * @param string $path
     */
    public function iCreateDirectory($path)
    {
        $this->makeDirectory($path);
    }

    /**
     * @When /^I run "([^"]*)"(?: with "([^"]*)")?$/
     *
     * @param string $command
     * @param string $arguments
     */
    public function iRunCommand($command, $arguments = '')
    {
        $arguments = strtr($arguments, array('\'' => '"'));
        $this->executeCommand($command.' '.$arguments);
    }

    /**
     * Checks whether previously ran command passed|failed with provided output.
     *
     * @Then /^it should (fail|pass) with:/
     *
     * @param   string       $success "fail" or "pass"
     * @param   PyStringNode $text    PyString text instance
     */
    public function itShouldExitWithOutput($success, PyStringNode $text)
    {
        $this->itShouldExitWith($success);
        $this->theOutputShouldContain($text);
    }

    /**
     * Checks whether previously ran command failed|passed.
     *
     * @Then /^it should (fail|pass)$/
     *
     * @param   string $result "fail" or "pass"
     */
    public function itShouldExitWith($result)
    {
        if ('fail' === $result) {
            $this->assertNotExitCode(0);

        } else {
            $this->assertExitCode(0);
        }
    }

    /**
     * Checks whether last command output contains provided string.
     *
     * @Then the output should contain:
     *
     * @param   PyStringNode $text PyString text instance
     */
    public function theOutputShouldContain(PyStringNode $text)
    {
        PHPUnit_Framework_Assert::assertContains($this->getExpectedOutput($text), $this->getOutput());
    }

    /**
     * @Then the output should match:
     *
     * @param PyStringNode $regexp
     */
    public function theOutputShouldMatch(PyStringNode $regexp)
    {
        PHPUnit_Framework_Assert::assertRegExp('/^'.$regexp.'$/', $this->getOutput());
    }
}