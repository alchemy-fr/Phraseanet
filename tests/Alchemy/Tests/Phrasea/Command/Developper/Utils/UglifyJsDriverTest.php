<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver;
use Symfony\Component\Process\PhpExecutableFinder;

class UglifyJsDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $driver = UglifyJsDriver::create();
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
