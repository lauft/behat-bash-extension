<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Lauft\Behat\BashExtension\Context\BashContext;

class FeatureContext extends BashContext
{
    /** @BeforeScenario */
    public function prepareTestFolders(BeforeScenarioScope $scope)
    {
        $this->workingDir = sys_get_temp_dir() . '/behat-bash-ext-' . getmypid();
        if (!is_dir($this->workingDir)) {
            mkdir($this->workingDir, 0777, true);
        }
        chdir($this->workingDir);
    }

    /** @AfterScenario */
    public function cleanTestFolders(AfterScenarioScope $scope)
    {
        if (is_dir($this->workingDir)) {
            $this->clearDirectory($this->workingDir);
            rmdir($this->workingDir);
        }
        chdir(sys_get_temp_dir());
    }

    private function clearDirectory(string $path): void
    {
        $entries = array_diff(scandir($path), ['.', '..']);
        foreach ($entries as $entry) {
            $full = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $this->clearDirectory($full);
                rmdir($full);
            } else {
                unlink($full);
            }
        }
    }
}
