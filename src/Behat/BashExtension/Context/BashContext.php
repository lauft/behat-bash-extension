<?php

namespace Lauft\Behat\BashExtension\Context;

use PHPUnit\Framework\Assert;
use Behat\Gherkin\Node\PyStringNode;
use Exception;
use Symfony\Component\Process\Process;

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
        Assert::assertStringContainsString($this->getExpectedOutput($text), $this->getOutput());
    }

    /**
     * @Then the output should match:
     *
     * @param PyStringNode $regexp
     */
    public function theOutputShouldMatch(PyStringNode $regexp)
    {
        Assert::assertMatchesRegularExpression('/^'.$regexp.'$/', $this->getOutput());
    }

    /**
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     *
     * @param string       $filename
     * @param PyStringNode $content
     */
    public function aFileNamedWith($filename, PyStringNode $content)
    {
        $content = strtr((string) $content, array("'''" => '"""'));
        $this->createFile($this->workingDir . '/' . $filename, $content);
    }

    /**
     * @Given /^there should be a file named "([^"]*)" with:$/
     *
     * @param string       $filename
     * @param PyStringNode $expectedContent
     */
    public function thereShouldBeAFileNamedWith($filename, PyStringNode $expectedContent)
    {
        $expectedContent = strtr((string) $expectedContent, array("'''" => '"""'));
        $path = $this->workingDir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($path)) {
            throw new Exception('invalid path "' . $path . '"');
        }
        $content = file_get_contents($path);
        Assert::assertEquals($expectedContent, $content);
    }

    /**
     * @Given /^file "([^"]*)" exists$/
     * @Then /^file "([^"]*)" should exist$/
     *
     * @param string $path
     */
    public function fileShouldExist($path)
    {
        Assert::assertFileExists($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @Given /^file "([^"]*)" does not exist$/
     * @Then /^file "([^"]*)" should not exist$/
     *
     * @param string $path
     */
    public function fileShouldNotExist($path)
    {
        Assert::assertFileDoesNotExist($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @Given /^directory "([^"]*)" exists$/
     * @Then /^directory "([^"]*)" should exist$/
     *
     * @param string $path
     */
    public function directoryShouldExist($path)
    {
        Assert::assertFileExists($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @Given /^directory "([^"]*)" does not exist$/
     * @Then /^directory "([^"]*)" should not exist$/
     *
     * @param string $path
     */
    public function directoryShouldNotExist($path)
    {
        Assert::assertFileDoesNotExist($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @Then /^"([^"]*)" file should contain:$/
     *
     * @param string       $path
     * @param PyStringNode $text
     */
    public function fileShouldContain($path, PyStringNode $text)
    {
        $path = $this->workingDir . '/' . $path;
        Assert::assertFileExists($path);
        $fileContent = trim(file_get_contents($path));
        if ("\n" !== PHP_EOL) {
            $fileContent = str_replace(PHP_EOL, "\n", $fileContent);
        }
        Assert::assertEquals($this->getExpectedOutput($text), $fileContent);
    }

    /**
     * @When /^"([^"]*)" environment variable is set to:$/
     *
     * @param string       $name
     * @param PyStringNode $value
     */
    public function iSetEnvironmentVariable($name, PyStringNode $value)
    {
        $this->processEnv[(string) $name] = (string) $value;
    }

    /**
     * @Given /^job "([^"]*)" is running$/
     * @Given /^(\d+) jobs "([^"]*)" are running$/
     *
     * @param string|int $param1
     * @param string|int $param2
     * @param int        $param3
     */
    public function iWaitForJobToRun($param1, $param2 = 1, $param3 = 60)
    {
        if (is_numeric($param1)) {
            $cnt = (int) $param1;
            $cmd = $param2;
            $timeout = $param3;
        } else {
            $cmd = $param1;
            $cnt = (int) $param2;
            $timeout = $param3;
        }

        $process = Process::fromShellCommandLine(
            'while [ ' . $cnt . ' -gt $(pgrep -f ' . escapeshellarg($cmd) . ' | wc -l) ] ; do sleep 1 ; done'
        );
        $process->setWorkingDirectory($this->workingDir);
        $process->setTimeout($timeout);
        $process->run();
        Assert::assertEquals(0, $process->getExitCode());
    }
}