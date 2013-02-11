<?php

class unitTestsTest extends PhraseanetPHPUnitAbstract
{

    public function testFiles()
    {
        $reserved = array(
            "BoilerPlate.php",
            "PhraseanetPHPUnitAbstract.php",
            "PhraseanetWebTestCaseAbstract.php",
            "PhraseanetPHPUnitAuthenticatedAbstract.php",
            "PhraseanetWebTestCaseAuthenticatedAbstract.php",
            "PhraseanetPHPUnitListener.php",
        );

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

            $this->assertRegExp('/[a-zA-Z0-9-_\.]+Test.php/', $file->getPathname(), 'Verify that all tests files names');
        }
    }
}
