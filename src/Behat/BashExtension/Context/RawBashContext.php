<?php

namespace Lauft\Behat\BashExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Raw bash context for Behat BDD tool.
 */
class RawBashContext implements Context
{
    /** @var string */
    protected $rootDirectory;

    /** @var string */
    protected $workingDir;

    /** @var Process */
    protected $process;

    /** @var array<string, string> */
    protected array $processEnv = [];

    /**
     * @param string $rootDirectory
     */
    public function __construct($rootDirectory = DIRECTORY_SEPARATOR)
    {
        $this->rootDirectory = $rootDirectory;
        $this->workingDir = $rootDirectory;
    }

    /**
     * @param string $commandLine
     */
    protected function executeCommand($commandLine)
    {
        $this->process = Process::fromShellCommandLine($commandLine);
        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setEnv($this->processEnv ?? []);
        $this->process->start();
        $this->process->wait();
    }

    /**
     * @param int $exitCode
     */
    public function assertExitCode($exitCode)
    {
        Assert::assertSame($exitCode, $this->getExitCode());
    }

    /**
     * @param int $exitCode
     */
    public function assertNotExitCode($exitCode)
    {
        Assert::assertNotSame($exitCode, $this->getExitCode());
    }

    /**
     * @return int|null The exit status code, null if the Process is not terminated
     */
    protected function getExitCode()
    {
        return $this->process->getExitCode();
    }

    /**
     * @return string
     */
    protected function getOutput()
    {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        // Replace wrong warning message of HHVM
        $output = str_replace('Notice: Undefined index: ', 'Notice: Undefined offset: ', $output);

        return trim(preg_replace("/ +$/m", '', $output));
    }

    /**
     * @param PyStringNode $expectedText
     * @return mixed|string
     */
    protected function getExpectedOutput(PyStringNode $expectedText)
    {
        $text = strtr($expectedText, array('\'\'\'' => '"""', '%%TMP_DIR%%' => sys_get_temp_dir() . DIRECTORY_SEPARATOR));

        // windows path fix
        if ('/' !== DIRECTORY_SEPARATOR) {
            $text = preg_replace_callback(
                '/ features\/[^\n ]+/', function ($matches) {
                return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
            }, $text
            );
            $text = preg_replace_callback(
                '/\<span class\="path"\>features\/[^\<]+/', function ($matches) {
                return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
            }, $text
            );
            $text = preg_replace_callback(
                '/\+[fd] [^ ]+/', function ($matches) {
                return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
            }, $text
            );
        }

        return $text;
    }

    /**
     * @param $path
     */
    protected function changeDirectory($path)
    {
        if (strpos($path, DIRECTORY_SEPARATOR) === 0) {
            $path = $this->rootDirectory . $path;
        } else {
            $newWorkingDir = $this->workingDir . DIRECTORY_SEPARATOR . $path;
            if (!file_exists($newWorkingDir)) {
                mkdir($newWorkingDir, 0777, true);
            }
            $path = $newWorkingDir;
        }

        if (!chdir($path)) {
            throw new RuntimeException();
        }

        $this->workingDir = getcwd();
    }

    /**
     * @param $path
     */
    protected function makeDirectory($path)
    {
        if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
            $path = $this->workingDir . DIRECTORY_SEPARATOR . $path;
        }
        if (!mkdir($path, 0777, true)) {
            throw new RuntimeException('Failed to create directory: ' . $path);
        }
    }

    /**
     * @param string $filename
     * @param string $content
     */
    protected function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($filename, $content);
    }
}
