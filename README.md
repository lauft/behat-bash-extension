# Behat BashExtension

A [Behat](https://behat.org) extension that provides step definitions for testing shell commands and filesystem state.
It runs commands, captures stdout/stderr, and asserts on exit codes, output, files, and directories.

## Installation

```bash
composer require --dev lauft/behat-bash-extension
```

## Setup

Register the context in your `behat.yml`:

```yaml
default:
    suites:
        default:
            paths:
                - features
            contexts:
                - Lauft\Behat\BashExtension\Context\BashContext
```

For project-specific setup/teardown, extend `BashContext` in your own `FeatureContext`:

```php
use Lauft\Behat\BashExtension\Context\BashContext;

class FeatureContext extends BashContext
{
    /** @BeforeScenario */
    public function prepareTestFolders()
    {
        $this->workingDir = sys_get_temp_dir() . '/my-project-tests';
        if (!is_dir($this->workingDir)) {
            mkdir($this->workingDir, 0777, true);
        }
        chdir($this->workingDir);
    }
}
```

## Available steps

### Working directory

| Step                                  | Description                                                                                                                      |
|---------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|
| `Given I am in directory "<path>"`    | Change the working directory. Relative paths are resolved against the current working dir; missing directories are auto-created. |
| `When I change to directory "<path>"` | Alias of the above.                                                                                                              |

### Directories

| Step                                       | Description                                                                       |
|--------------------------------------------|-----------------------------------------------------------------------------------|
| `Given there is a directory "<path>"`      | Create a directory (recursively). Relative paths resolve against the working dir. |
| `When I create directory "<path>"`         | Alias of the above.                                                               |
| `Then directory "<path>" should exist`     | Assert a directory exists.                                                        |
| `Then directory "<path>" should not exist` | Assert a directory does not exist.                                                |

### Files

| Step                                               | Description                                                                                  |
|----------------------------------------------------|----------------------------------------------------------------------------------------------|
| `Given a file named "<path>" with:`                | Create a file with the given `PyStringNode` content. Missing parent directories are created. |
| `Given there is a file named "<path>" with:`       | Alias of the above.                                                                          |
| `Then there should be a file named "<path>" with:` | Assert a file exists with the exact given content.                                           |
| `Then file "<path>" should exist`                  | Assert a file exists.                                                                        |
| `Then file "<path>" should not exist`              | Assert a file does not exist.                                                                |
| `Then "<path>" file should contain:`               | Assert a file's trimmed content equals the given text.                                       |

### Command execution

| Step                                        | Description                                                                                    |
|---------------------------------------------|------------------------------------------------------------------------------------------------|
| `When I run "<command>" with "<arguments>"` | Run a shell command with arguments. Single quotes in arguments are converted to double quotes. |
| `When I run "<command>"`                    | Run a shell command with no arguments.                                                         |
| `Then it should pass`                       | Assert the previous command exited with code 0.                                                |
| `Then it should fail`                       | Assert the previous command exited with a non-zero code.                                       |
| `Then it should pass with:`                 | Assert the command passed and its output contains the given text.                              |
| `Then it should fail with:`                 | Assert the command failed and its output contains the given text.                              |
| `Then the output should contain:`           | Assert the command output contains the given text.                                             |
| `Then the output should match:`             | Assert the command output matches the given regular expression (anchored with `^...$`).        |

### Environment variables

| Step                                            | Description                                          |
|-------------------------------------------------|------------------------------------------------------|
| `When "<NAME>" environment variable is set to:` | Set an environment variable for subsequent commands. |

### Background jobs

| Step                                 | Description                                                       |
|--------------------------------------|-------------------------------------------------------------------|
| `Given job "<cmd>" is running`       | Wait until at least one process matching `<cmd>` is running.      |
| `Given <n> jobs "<cmd>" are running` | Wait until at least `<n>` processes matching `<cmd>` are running. |

## Working directory resolution

Steps that take a relative path resolve it against the context's `$workingDir` property, not PHP's actual cwd. `I am in directory` updates `$workingDir` (and calls `chdir`), so subsequent relative paths resolve from the new location. Set `$workingDir` in a `@BeforeScenario` hook to isolate each scenario.

## Content placeholders

In `PyStringNode` content, use `'''` for a literal `"""` and `%%TMP_DIR%%` for the system temp directory (with trailing separator).

## Testing the extension

The extension tests itself with Behat. Clone the repo and run:

```bash
make test
```

This installs dependencies and runs the feature suite under `features/`.

## Requirements

- PHP >= 8.2
- Behat ^3.14
- symfony/process ^7.0
- PHPUnit ^10.0 || ^11.0 (for assertion APIs)

## License

MIT
