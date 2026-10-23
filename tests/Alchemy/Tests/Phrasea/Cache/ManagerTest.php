<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Exception\RuntimeException;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ManagerTest extends \PhraseanetTestCase
{
    private $file;
    private $compiler;

    public function setUp(): void
    {
        parent::setUp();
        $this->file = __DIR__ . '/tmp-file.php';
        $this->compiler = new Compiler();
        $this->clean();
    }

    public function tearDown(): void
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
        file_put_contents($this->file, $this->compiler->compile([]));
    }

    public function testFactoryCreateOne()
    {
        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $logger = $this->createMock('Monolog\Logger');
        $factory = $this->createMock('Alchemy\Phrasea\Cache\Factory');

        $compiler->expects($this->once())
            ->method('compile');

        $cache = $this->createStub('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = ['option', 'value'];

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
        file_put_contents($this->file, $this->compiler->compile(["custom-type" => "array"]));

        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $logger = $this->createMock('Monolog\Logger');
        $factory = $this->createMock('Alchemy\Phrasea\Cache\Factory');

        $compiler->expects($this->never())
            ->method('compile');

        $cache = $this->createStub('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = ['option', 'value'];

        $factory->expects($this->once())
            ->method('create')
            ->with($name, $values)
            ->will($this->returnValue($cache));

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }

    public function testNoCompilationIfNoChangeWithMultiple()
    {
        file_put_contents($this->file, $this->compiler->compile([
            "custom-type" => "array",
            "another-type" => "array",
            "yet-another-type" => "array",
        ]));

        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $logger = $this->createMock('Monolog\Logger');
        $factory = $this->createMock('Alchemy\Phrasea\Cache\Factory');

        $compiler->expects($this->never())
            ->method('compile');

        $cache = $this->createStub('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = ['option', 'value'];

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
        file_put_contents($this->file, $this->compiler->compile([
            "custom-type" => "unknown",
        ]));

        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $logger = $this->createMock('Monolog\Logger');
        $factory = $this->createMock('Alchemy\Phrasea\Cache\Factory');

        $compiler->expects($this->never())
            ->method('compile');

        $logger->expects($this->once())
            ->method('error');

        $cache = $this->createStub('Alchemy\Phrasea\Cache\Cache');

        $name = 'unknown';
        $values = ['option', 'value'];

        $calls = [];
        $factory->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function ($type, $options) use (&$calls, $cache) {
                $calls[] = [$type, $options];
                if (1 === count($calls)) {
                    throw new RuntimeException('Unknown cache type');
                }

                return $cache;
            });

        $manager = new Manager($compiler, $this->file, $logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame([[$name, $values], ['array', []]], $calls);
    }
}
