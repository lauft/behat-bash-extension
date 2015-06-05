<?php

namespace Lauft\Behat\BashExtension\Context;

/**
 * BashContext context for Behat BDD tool.
 * Provides bash base step definitions.
 */
class BashContext extends RawBashContext
{
    /** @var string */
    protected $rootDirectory;

    /** @var string */
    protected $workingDir;

    /** @var Process */
    protected $process;

    /**
     * @param string $rootDirectory
     */
    public function __construct($rootDirectory = DIRECTORY_SEPARATOR)
    {
        $this->rootDirectory = $rootDirectory;
        $this->workingDir = $rootDirectory;
        $this->process = new Process(null);
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

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine($command.' '.$arguments);
        $this->process->start();
        $this->process->wait();
    }
}