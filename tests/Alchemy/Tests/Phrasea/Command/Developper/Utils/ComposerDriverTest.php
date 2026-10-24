<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver;
use Symfony\Component\Process\PhpExecutableFinder;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ComposerDriverTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $driver = ComposerDriver::create();
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver', $driver);
        $this->assertEquals('composer', $driver->getName());
    }

    public function testCreateWithCustomBinary()
    {
        $finder = new PhpExecutableFinder();
        $php = $finder->find();

        $driver = ComposerDriver::create(['composer.binaries' => $php]);
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver', $driver);
        $this->assertEquals($php, $driver->getProcessBuilderFactory()->getBinary());
    }
}
