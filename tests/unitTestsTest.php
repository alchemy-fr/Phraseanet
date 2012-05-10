<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

class unitTestsTest extends PhraseanetPHPUnitAbstract
{

    public function testFiles()
    {
        $testDir = __DIR__ . '/';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (strpos($file, '/.svn/') !== false)
                continue;
            if (substr($file->getFilename(), 0, 1) === '.')
                continue;
            if (substr($file->getFilename(), -4) !== '.php')
                continue;
            if (substr($file->getFilename(), -9) === 'class.php')
                continue;
            if ($file->getFilename() === "BoilerPlate.php")
                continue;

            $this->assertRegExp('/[a-zA-Z0-9-_\.]+Test.php/', $file->getPathname(), 'Verify that all tests files names');
        }
    }
}
