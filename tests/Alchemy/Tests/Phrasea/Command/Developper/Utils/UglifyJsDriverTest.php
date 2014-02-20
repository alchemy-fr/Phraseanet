<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver;
use Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider;
use Symfony\Component\Process\PhpExecutableFinder;

class UglifyJsDriverTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $app = self::$DI['app'];
        $app->register(new CLIDriversServiceProvider());
        $driver = UglifyJsDriver::create(['uglifyjs.binaries' => $app['driver.binary-finder']('uglifyjs', 'uglifyjs_binary')]);
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver', $driver);
        $this->assertEquals('uglifyjs', $driver->getName());
    }

    public function testCreateWithCustomBinary()
    {
        $finder = new PhpExecutableFinder();
        $php = $finder->find();

        $driver = UglifyJsDriver::create(['uglifyjs.binaries' => $php]);
        $this->assertInstanceOf('Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver', $driver);
        $this->assertEquals($php, $driver->getProcessBuilderFactory()->getBinary());
    }
}
