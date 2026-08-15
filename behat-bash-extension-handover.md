# Handover: Move generic step definitions into behat-bash-extension

## Context

The SQS project's `FeatureContext` extends `Lauft\Behat\BashExtension\Context\BashContext`. After the initial integration, `FeatureContext` still contains many generic, non-SQS step definitions that belong in the extension. This document describes which methods to move, where to put them, and what to delete from `FeatureContext`.

The extension lives at `https://github.com/lauft/behat-bash-extension.git` and is required in `test/composer.json` as `lauft/behat-bash-extension: @dev`.

## Extension file layout

```
src/Behat/BashExtension/Context/
├── RawBashContext.php   # base class: process lifecycle, cwd, env, helpers
└── BashContext.php      # step definitions (extends RawBashContext)
```

`RawBashContext` already provides:
- `executeCommand($commandLine)` — runs a Process with working dir + env
- `getOutput()`, `getExitCode()`, `getExpectedOutput()`
- `assertExitCode()`, `assertNotExitCode()`
- `changeDirectory($path)`, `makeDirectory($path)`

`BashContext` already provides:
- `iChangeToDirectory` → `I am in directory "..."`
- `iCreateDirectory` → `there is a directory "..."`
- `iRunCommand` → `I run "..." with "..."`
- `itShouldExitWith` → `it should (fail|pass)`
- `itShouldExitWithOutput` → `it should (fail|pass) with:`
- `theOutputShouldContain` → `the output should contain:`
- `theOutputShouldMatch` → `the output should match:`

---

## Part 1: Methods already duplicated — DELETE from `FeatureContext`

These are redundant because `BashContext` already defines step definitions with the same regex. Behat currently picks the subclass's version; removing them eliminates shadowing.

### 1a. `FeatureContext::aDirectory()` — `test/features/bootstrap/FeatureContext.php:153`

```php
/**
 * @Given /^(?:there is )?a directory "([^"]*)"$/
 * @param $filename
 */
public function aDirectory($filename)
{
    mkdir($this->workingDir.DIRECTORY_SEPARATOR.$filename);
}
```

**Action:** Delete from `FeatureContext`. `BashContext::iCreateDirectory()` already matches `@Given /^there is a directory "([^"]*)"$/`.

Note: `BashContext::iCreateDirectory` calls `$this->makeDirectory($path)` which does a bare `mkdir($path)` without the working-dir prefix. Check whether `makeDirectory` in `RawBashContext` resolves relative paths against `$this->workingDir`. If it does not, fix `makeDirectory` to prepend `$this->workingDir` so relative paths work (the feature files use relative paths like `"lockfile"`).

### 1b. `FeatureContext::itShouldPassWith()` — `test/features/bootstrap/FeatureContext.php:268`

```php
/**
 * @Then /^it should (fail|pass) with:$/
 */
public function itShouldPassWith($success, PyStringNode $text)
{
    $this->itShouldExitWith($success);
    $this->theOutputShouldContain($text);
}
```

**Action:** Delete from `FeatureContext`. `BashContext::itShouldExitWithOutput()` already matches the same regex.

---

## Part 2: Generic methods to move into the extension

### Step definitions to add to `BashContext`

#### 2a. File creation

```php
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
```

#### 2b. File content assertion

```php
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
```

#### 2c. File existence

```php
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
```

#### 2d. File non-existence

```php
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
```

#### 2e. Directory existence

```php
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
```

#### 2f. Directory non-existence

```php
/**
 * @Given /^directory "([^"]*)" does not exist$/
 * @Then /^directory  "([^"]*)" should not exist$/
 *
 * @param string $path
 */
public function directoryShouldNotExist($path)
{
    Assert::assertFileDoesNotExist($this->workingDir . DIRECTORY_SEPARATOR . $path);
}
```

Note the double space in `directory  "([^"]*)"` — this is a pre-existing typo in the regex. Keep it as-is to avoid breaking existing feature files, or fix both the regex and any feature files that rely on it.

#### 2g. File content contains

```php
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
```

#### 2h. Environment variable setter

```php
/**
 * @When /^"([^"]*)" environment variable is set to:$/
 *
 * @param PyStringNode $value
 */
public function iSetEnvironmentVariable($name, PyStringNode $value)
{
    $this->processEnv[(string) $name] = (string) $value;
}
```

Note: the current `FeatureContext::iSetEnvironmentVariable` hardcodes `'BEHAT_PARAMS'` as the key. Generalize it to accept the env var name from the regex (the step text is `"BEHAT_PARAMS" environment variable is set to:`). The current regex `@When /^"BEHAT_PARAMS" environment variable is set to:/` captures nothing — change it to `@When /^"([^"]*)" environment variable is set to:/` and add the `$name` parameter.

#### 2i. Wait for background jobs

```php
/**
 * @Given /^job "([^"]*)" is running$/
 * @Given /^(\d+) jobs "([^"]*)" are running$/
 *
 * @param string $cmd
 * @param int    $cnt
 * @param int    $timeout
 */
public function iWaitForJobToRun($cmd, $cnt = 1, $timeout = 60)
{
    $process = Process::fromShellCommandLine(
        'while [ ' . $cnt . ' -gt $(pgrep -f ' . $cmd . ' | wc -l) ] ; do sleep 1 ; done'
    );
    $process->setWorkingDirectory($this->workingDir);
    $process->setTimeout($timeout);
    $process->run();
    Assert::assertEquals(0, $process->getExitCode());
}
```

Note the parameter order: the two `@Given` forms have different capture group orders. The single-job form `job "sqstesttask" is running` has one capture group (`$cmd`). The multi-job form `3 jobs "sqstesttask" are running` has two (`$cnt`, `$cmd`). Behat passes captures positionally, so:
- Single form passes `$cmd` only → `$cnt` defaults to 1. This works.
- Multi form passes `$cnt` then `$cmd`. This works.

Verify the `$timeout` parameter: Behat won't pass it, so it defaults to 60. This matches the current behavior.

**Required fix:** `setWorkingDirectory($this->workingDir)` must be set (see Part 4). The current `FeatureContext` version was patched to include this.

### Helper to add to `RawBashContext`

#### 2j. `createFile()`

```php
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
```

This is used by `aFileNamedWith()` (2a). Add it to `RawBashContext` alongside `makeDirectory()`.

---

## Part 3: Methods to DELETE from `FeatureContext` after moving

After the step definitions above are added to the extension and released, delete the following from `test/features/bootstrap/FeatureContext.php`:

| Method | Lines (current) | Replaced by |
|--------|-----------------|-------------|
| `aFileNamedWith()` | 143–147 | `BashContext::aFileNamedWith` |
| `aDirectory()` | 153–156 | already in `BashContext::iCreateDirectory` (duplicate) |
| `iWaitForJobToRun()` | 162–169 | `BashContext::iWaitForJobToRun` |
| `thereShouldBeAFileNamedWith()` | 180–193 | `BashContext::thereShouldBeAFileNamedWith` |
| `fileShouldExist()` | 203–206 | `BashContext::fileShouldExist` |
| `fileShouldNotExist()` | 216–219 | `BashContext::fileShouldNotExist` |
| `directoryShouldExist()` | 229–233 | `BashContext::directoryShouldExist` |
| `directoryShouldNotExist()` | 243–246 | `BashContext::directoryShouldNotExist` |
| `iSetEnvironmentVariable()` | 255–258 | `BashContext::iSetEnvironmentVariable` |
| `itShouldPassWith()` | 268–272 | already in `BashContext::itShouldExitWithOutput` (duplicate) |
| `fileShouldContain()` | 282–294 | `BashContext::fileShouldContain` |
| `createFile()` | 296–304 | `RawBashContext::createFile` |
| `moveToNewPath()` | 306–314 | dead code (replaced by `RawBashContext::changeDirectory`) |

### What stays in `FeatureContext`

Only SQS-specific lifecycle and build machinery:

- `cleanTestFolders()` — `@BeforeSuite`, clears and installs sqs
- `afterSuite()` — `@AfterSuite`, cleans up
- `prepareTestFolders()` — `@BeforeScenario`, sets working dir + env + `@chdir`
- `beforeFeature()` — `@BeforeFeature`, cleans var dir
- `beforeScenario()` — `@BeforeScenario`, kills sqs processes + cleans var dir
- `afterScenario()` — `@AfterScenario`, kills sqs processes + cleans var dir
- `installSqsAt()` — builds and installs sqs binaries
- `clearDirectory()` — recursive directory deletion helper

---

## Part 4: Bugfixes needed in `RawBashContext` before the move

### 4a. `makeDirectory()` doesn't resolve relative paths

Current `RawBashContext::makeDirectory()` (line 142):

```php
protected function makeDirectory($path)
{
    if (!mkdir($path)) {
        throw new RuntimeException();
    }
}
```

Feature files use relative paths (e.g. `there is a directory "lockfile"`). This will `mkdir("lockfile")` in PHP's cwd, not in `$this->workingDir`. Fix:

```php
protected function makeDirectory($path)
{
    if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
        $path = $this->workingDir . DIRECTORY_SEPARATOR . $path;
    }
    if (!mkdir($path, 0777, true)) {
        throw new RuntimeException('Failed to create directory: ' . $path);
    }
}
```

### 4b. `changeDirectory()` doesn't create missing directories

Current `changeDirectory()` (line 124) calls `chdir($path)` which fails if the directory doesn't exist. The old `FeatureContext::moveToNewPath()` created it first:

```php
$newWorkingDir = $this->workingDir .'/' . $path;
if (!file_exists($newWorkingDir)) {
    mkdir($newWorkingDir, 0777, true);
}
```

Some feature files do `I am in directory "var/sqs/myqueue/wait"` where the subdir may not yet exist. Decide whether `changeDirectory` should auto-create, or whether feature files should always `there is a directory "..."` first. The current SQS features rely on auto-creation (e.g. `sqs-add.feature:24` changes to `var/sqs/myqueue/wait` without creating it first, because `sqs add` created it as a side effect). So either:
- Keep auto-create in `changeDirectory`, or
- Ensure `sqs init`/`sqs add` creates the dirs before the step runs.

Safest: add auto-create to `changeDirectory` for the relative-path case.

---

## Part 5: Import cleanup in `FeatureContext`

After the move, remove these now-unused imports from `FeatureContext`:

- `use Behat\Gherkin\Node\PyStringNode;` — no longer used (all PyStringNode-handling steps moved out)
- `use Symfony\Component\Process\Process;` — no longer used (`iWaitForJobToRun` moved out)
- `use PHPUnit\Framework\Assert;` — check whether any remaining SQS-specific method uses it. After the move, the lifecycle hooks don't use `Assert`, so it can be removed.

Keep:
- `use Behat\Behat\Hook\Scope\BeforeFeatureScope;`
- `use Behat\Behat\Hook\Scope\BeforeScenarioScope;`
- `use Behat\Behat\Hook\Scope\AfterScenarioScope;`
- `use Lauft\Behat\BashExtension\Context\BashContext;`

---

## Part 6: Step regex audit

All step regexes used in feature files, with the method that should own them after the move:

| Regex | Feature files using it | Owner after move |
|-------|----------------------|------------------|
| `I am in directory "..."` | add, close, config, get, init, kill, remove, set, start, stop, guard | `BashContext::iChangeToDirectory` (already there) |
| `I run "..." with "..."` | all | `BashContext::iRunCommand` (already there) |
| `it should (fail\|pass)` | all | `BashContext::itShouldExitWith` (already there) |
| `it should (fail\|pass) with:` | config | `BashContext::itShouldExitWithOutput` (already there) |
| `the output should contain:` | all | `BashContext::theOutputShouldContain` (already there) |
| `there is a directory "..."` | — (not in features, but defined) | `BashContext::iCreateDirectory` (already there) |
| `a file named "..." with:` | config | `BashContext::aFileNamedWith` (new) |
| `there should be a file named "..." with:` | config | `BashContext::thereShouldBeAFileNamedWith` (new) |
| `file "..." should exist` | add, close, init, remove, start, stop | `BashContext::fileShouldExist` (new) |
| `file "..." should not exist` | close, remove | `BashContext::fileShouldNotExist` (new) |
| `directory "..." should exist` | guard | `BashContext::directoryShouldExist` (new) |
| `directory "..." should not exist` | — (not in features) | `BashContext::directoryShouldNotExist` (new) |
| `"...": file should contain:` | set, start, stop | `BashContext::fileShouldContain` (new) |
| `"...": environment variable is set to:` | — (not in current features) | `BashContext::iSetEnvironmentVariable` (new) |
| `job "..." is running` | — (not in current features) | `BashContext::iWaitForJobToRun` (new) |
| `:cnt jobs "..." are running` | kill | `BashContext::iWaitForJobToRun` (new) |

---

## Part 7: Order of operations

1. **In the extension repo** (`behat-bash-extension`):
   - Fix `RawBashContext::makeDirectory()` to resolve relative paths (Part 4a).
   - Add `RawBashContext::createFile()` (Part 2j).
   - Optionally fix `changeDirectory()` to auto-create missing dirs (Part 4b).
   - Add all new step definitions to `BashContext` (Parts 2a–2i).
   - Add `use Symfony\Component\Process\Process;` and `use Exception;` to `BashContext` if needed.
   - Run the extension's own tests (if any exist).
   - Commit and push, or tag a new release.

2. **In the SQS repo** (`sqs`):
   - Update `test/composer.json` to require the new extension version.
   - Run `composer update lauft/behat-bash-extension`.
   - Delete the methods listed in Part 3 from `FeatureContext`.
   - Remove unused imports (Part 5).
   - Run `cd test && TMPDIR=/tmp/sqstest timeout 180 bin/behat --no-colors`.
   - All 72 scenarios / 444 steps should still pass.

---

## Part 8: Verification command

After applying both sides:

```bash
cd /Users/alatar/Projects/sqs
pkill -9 -f sqstesttask; pkill -9 -f sqsrunner; pkill -9 -f sqsdispatch
cd test && TMPDIR=/tmp/sqstest timeout 180 bin/behat --no-colors
# expected: 72 scenarios (72 passed), 444 steps (444 passed)
```
