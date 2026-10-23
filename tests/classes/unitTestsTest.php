<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class unitTestsTest extends \PhraseanetTestCase
{
    public function testFiles()
    {
        $reserved = [
            "BoilerPlate.php",
            "PhraseanetTestCase.php",
            "PhraseanetWebTestCase.php",
            "PhraseanetAuthenticatedTestCase.php",
            "PhraseanetAuthenticatedWebTestCase.php",
            "PhraseanetSeTestSubscriber.php",
            "PDOMock.php",
            "CsrfTestProvider.php",
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

            $this->assertMatchesRegularExpression('/[a-zA-Z0-9-_\.]+Test(Case)?.php/', $file->getPathname(), 'Verify that all tests files names');
        }
    }
}
