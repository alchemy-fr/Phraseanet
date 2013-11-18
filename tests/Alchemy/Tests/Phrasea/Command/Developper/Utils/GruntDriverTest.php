<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\GruntDriver;
use Symfony\Component\Process\PhpExecutableFinder;

class GruntDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $driver = GruntDriver::create();
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\GruntDriver', $driver);
        $this->assertEquals('grunt', $driver->getName());
    }

    public function testCreateWithCustomBinary()
    {
        $finder = new PhpExecutableFinder();
        $php = $finder->find();

        $driver = GruntDriver::create(['grunt.binaries' => $php]);
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\GruntDriver', $driver);
        $this->assertEquals($php, $driver->getProcessBuilderFactory()->getBinary());
    }
}
