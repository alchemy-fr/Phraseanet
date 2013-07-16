<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver;
use Symfony\Component\Process\PhpExecutableFinder;

class ComposerDriverTest extends \PHPUnit_Framework_TestCase
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

        $driver = ComposerDriver::create(array('composer.binaries' => $php));
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver', $driver);
        $this->assertEquals($php, $driver->getProcessBuilderFactory()->getBinary());
    }
}
