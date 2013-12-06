<?php

class unitTestsTest extends \PhraseanetTestCase
{

    public function testFiles()
    {
        $reserved = [
            "BoilerPlate.php",
            "PhraseanetTestCase.php",
            "PhraseanetWebTestCaseAbstract.php",
            "PhraseanetAuthenticatedTestCase.php",
            "PhraseanetWebTestCaseAuthenticatedAbstract.php",
            "PhraseanetPHPUnitListener.php",
        ];

        $testDir = __DIR__ . '/';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (strpos($file, '/.svn/') !== false)
                continue;
            if (substr($file->getFilename(), 0, 1) === '.')
                continue;
            if (substr($file->getFilename(), -4) !== '.php')
                continue;
            if (in_array($file->getFilename(), $reserved))
                continue;

            $this->assertRegExp('/[a-zA-Z0-9-_\.]+Test(Case)?.php/', $file->getPathname(), 'Verify that all tests files names');
        }
    }
}
