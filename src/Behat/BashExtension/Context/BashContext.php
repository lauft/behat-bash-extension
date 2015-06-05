<?php

namespace Lauft\Behat\BashExtension\Context;

/**
 * BashContext context for Behat BDD tool.
 * Provides bash base step definitions.
 */
class BashContext extends RawBashContext
{
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