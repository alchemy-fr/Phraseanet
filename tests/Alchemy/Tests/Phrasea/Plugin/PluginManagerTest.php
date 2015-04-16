<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Plugin\PluginManager;
use Alchemy\Phrasea\Plugin\PluginRepository;
use Alchemy\Tests\Phrasea\Plugin\Fixtures\BarPlugin;
use Alchemy\Tests\Phrasea\Plugin\Fixtures\FooPlugin;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Prophecy\Argument;

class PluginManagerTest extends \PHPUnit_Framework_TestCase
{
    private $repository;
    private $filename;
    /** @var PropertyAccess */
    private $propertyAccess;
    /** @var vfsStreamDirectory */
    private $root;
    /** @var PluginManager */
    private $sut;

    protected function setUp()
    {
        $this->root = vfsStream::setup('root');

        $this->repository = $this->prophesize(PluginRepository::class);
        $this->repository->findAll()->willReturn(['foo' => FooPlugin::class, 'bar' => BarPlugin::class]);
        $this->repository->find('foo')->willReturn(FooPlugin::class);
        $this->repository->find('bar')->willReturn(BarPlugin::class);

        $prophesized = $this->prophesize(ConfigurationInterface::class);
        $prophesized->getConfig()->willReturn([]);
        $prophesized->setConfig(Argument::any())->will(function ($args) {
            $this->getConfig()->willReturn($args[0]);
        });
        $this->propertyAccess = new PropertyAccess($prophesized->reveal());

        $this->filename = vfsStream::url('root/plugins.php');

        $this->sut = new PluginManager(
            $this->repository->reveal(),
            $this->propertyAccess,
            $this->filename
        );
    }

    public function testItEnablesPlugin()
    {
        $this->assertTrue($this->sut->enablePlugin('foo'));

        $this->assertEquals([
            'foo' => [
                'enabled' => true,
            ],
        ], $this->propertyAccess->get(['plugins']));

        $this->assertFileEquals(__DIR__ . '/Fixtures/testFooPluginEnabled.txt', $this->filename);
    }

    public function testItDisablesPluginAndKeepConfiguration()
    {
        $this->sut->enablePlugin('foo');
        $this->sut->setPluginParameters('foo', ['test']);

        $this->assertTrue($this->sut->disablePlugin('foo'), 'Expects disablePlugin to return true while changing configuration');

        $this->assertEquals([
            'foo' => [
                'enabled' => false,
                'parameters' => [
                    'test',
                ],
            ],
        ], $this->propertyAccess->get(['plugins']));

        $this->assertFileEquals(__DIR__ . '/Fixtures/testNoEnabled.txt', $this->filename);
    }

    public function testItEnablesPluginWithConfiguration()
    {
        $this->sut->enablePlugin('foo');
        $this->sut->enablePlugin('bar');
        $this->sut->setPluginParameters('bar', ['test']);

        $this->assertEquals([
            'foo' => [
                'enabled' => true,
            ],
            'bar' => [
                'enabled' => true,
                'parameters' => [
                    'test',
                ],
            ],
        ], $this->propertyAccess->get(['plugins']));

        $this->assertFileEquals(__DIR__ . '/Fixtures/testFooBarWithParamsEnabled.txt', $this->filename);
    }
}
