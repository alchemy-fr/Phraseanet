<?php

namespace Alchemy\Tests\Phrasea\Utilities;

use Alchemy\Phrasea\Utilities\ComposerSetup;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ComposerSetupTest extends \PhraseanetTestCase
{
    public function testSetup()
    {
        $target = __DIR__ . '/target-composer';

        if (is_file($target)) {
            unlink($target);
        }

        $setup = new ComposerSetup(new Guzzle());
        $setup->setup($target);

        $finder = new PhpExecutableFinder();
        $php = $finder->find();

        $process = new Process([$php, $target, '--version']);
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertSame(0, strpos($process->getOutput(), 'Composer version'));

        unlink($target);
    }
}
