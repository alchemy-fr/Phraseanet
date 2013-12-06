<?php

namespace Alchemy\Tests\Phrasea\Utilities;

use Alchemy\Phrasea\Utilities\ComposerSetup;
use Guzzle\Http\Client as Guzzle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

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

        $process = ProcessBuilder::create([$php, $target, '--version'])->getProcess();
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertSame(0, strpos($process->getOutput(), 'Composer version'));

        unlink($target);
    }
}
