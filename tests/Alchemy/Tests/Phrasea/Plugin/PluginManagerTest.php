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
use Alchemy\Phrasea\Plugin\Plugin;
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
    private $dir;
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
        $repository = [
            'foo' => [
                'name'     => 'Foo',
                'class'    => FooPlugin::class,
                'basePath' => vfsStream::url('root/foo'),
            ],
            'bar' => [
                'name'     => 'BAR',
                'class'    => Plugin::class,
                'basePath' => vfsStream::url('root/bar'),
            ],
        ];
        $this->repository->findAll()->willReturn($repository);
        $this->repository->find('foo')->willReturn($repository['foo']);
        $this->repository->find('bar')->willReturn($repository['bar']);

        $prophesized = $this->prophesize(ConfigurationInterface::class);
        $prophesized->getConfig()->willReturn([]);
        $prophesized->setConfig(Argument::any())->will(function ($args) {
            $this->getConfig()->willReturn($args[0]);
        });
        $this->propertyAccess = new PropertyAccess($prophesized->reveal());

        $this->dir = vfsStream::url('root');

        $this->sut = new PluginManager(
            $this->repository->reveal(),
            $this->propertyAccess,
            $this->dir
        );
    }

    public function testItEnablesPlugin()
    {
        $this->assertTrue($this->sut->enablePlugin('foo'));

        $expected = [
            'foo' => [
                'enabled' => true,
            ],
        ];
        $this->assertEquals($expected, $this->propertyAccess->get(['plugins']));
        $this->assertFileEquals(__DIR__ . '/Fixtures/testFooPluginEnabled.txt', vfsStream::url('root/plugins.php'));
    }

    public function testItDisablesPluginAndKeepConfiguration()
    {
        $this->sut->enablePlugin('foo');
        $this->sut->setPluginParameters('foo', ['test']);

        $this->assertTrue($this->sut->disablePlugin('foo'), 'Expects disablePlugin to return true while changing configuration');

        $expected = [
            'foo' => [
                'enabled'    => false,
                'parameters' => [
                    'test',
                ],
            ],
        ];
        $this->assertEquals($expected, $this->propertyAccess->get(['plugins']));
        $this->assertFileEquals(__DIR__ . '/Fixtures/testNoEnabled.txt', vfsStream::url('root/plugins.php'));
    }

    public function testItEnablesPluginWithConfiguration()
    {
        $this->sut->enablePlugin('foo');
        $this->sut->enablePlugin('bar');
        $this->sut->setPluginParameters('bar', ['test']);

        $expected = [
            'foo' => [
                'enabled' => true,
            ],
            'bar' => [
                'enabled'    => true,
                'parameters' => [
                    'test',
                ],
            ],
        ];
        $this->assertEquals($expected, $this->propertyAccess->get(['plugins']));
        $this->assertFileEquals(__DIR__ . '/Fixtures/testFooBarWithParamsEnabled.txt', vfsStream::url('root/plugins.php'));
    }
}
