<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Exception\RuntimeException;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    private $file;

    public function setUp()
    {
        parent::setUp();
        $this->file = __DIR__ . '/tmp-file.php';
        $this->compiler = new Compiler();
        $this->clean();
    }

    public function tearDown()
    {
        $this->clean();
        parent::tearDown();
    }

    private function clean()
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    private function createEmptyRegistry()
    {
        file_put_contents($this->file, $this->compiler->compile(array()));
    }

    public function testFactoryCreateOne()
    {
        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $compiler->expects($this->once())
            ->method('compile');

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = array('option', 'value');

        $factory->expects($this->once())
            ->method('create')
            ->with($name, $values)
            ->will($this->returnValue($cache));

        $this->createEmptyRegistry();

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }

    public function testNoCompilationIfNoChange()
    {
        file_put_contents($this->file, $this->compiler->compile(array("custom-type" => "array")));

        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $compiler->expects($this->never())
            ->method('compile');

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = array('option', 'value');

        $factory->expects($this->once())
            ->method('create')
            ->with($name, $values)
            ->will($this->returnValue($cache));

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }

    public function testNoCompilationIfNoChangeWithMultiple()
    {
        file_put_contents($this->file, $this->compiler->compile(array(
            "custom-type" => "array",
            "another-type" => "array",
            "yet-another-type" => "array",
        )));

        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $compiler->expects($this->never())
            ->method('compile');

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = array('option', 'value');

        $factory->expects($this->exactly(3))
            ->method('create')
            ->with($name, $values)
            ->will($this->returnValue($cache));

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('another-type', $name, $values));
        $this->assertSame($cache, $manager->factory('yet-another-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('another-type', $name, $values));
        $this->assertSame($cache, $manager->factory('yet-another-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('another-type', $name, $values));
        $this->assertSame($cache, $manager->factory('yet-another-type', $name, $values));
    }

    public function testUnknownCacheReturnsArrayCacheAndLogs()
    {
        file_put_contents($this->file, $this->compiler->compile(array(
            "custom-type" => "unknown",
        )));

        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $compiler->expects($this->never())
            ->method('compile');

        $logger->expects($this->once())
            ->method('error');

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'unknown';
        $values = array('option', 'value');

        $factory->expects($this->at(0))
            ->method('create')
            ->with($name, $values)
            ->will($this->throwException(new RuntimeException('Unknown cache type')));

        $factory->expects($this->at(1))
            ->method('create')
            ->with('array', array())
            ->will($this->returnValue($cache));

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }
}
