<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\GruntDriver;
use Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider;
use Symfony\Component\Process\PhpExecutableFinder;

class GruntDriverTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $app = self::$DI['app'];
        $app->register(new CLIDriversServiceProvider());
        $driver = GruntDriver::create(['grunt.binaries' => $app['driver.binary-finder']('grunt', 'grunt_binary')]);
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
