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

use Alchemy\Phrasea\Plugin\PluginRepository;
use Alchemy\Tests\Phrasea\Plugin\Fixtures\BarPlugin;
use Alchemy\Tests\Phrasea\Plugin\Fixtures\FooPlugin;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class PluginRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp()
    {
        $this->root = vfsStream::setup('root');
    }

    public function testItFindsAllPackages()
    {
        vfsStream::create([
            'foo' => [
                '.git' => [],
                'composer.json' => json_encode([
                    'name' => 'phraseanet/plugin-Foo',
                    'extra' => [
                        'class' => FooPlugin::class,
                    ],
                ], JSON_PRETTY_PRINT),
            ],
            'bar' => [
                'composer.json' => json_encode([
                    'name' => 'phraseanet/plugin-BAR',
                    'extra' => [
                        'class' => BarPlugin::class,
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ], $this->root);

        $sut = new PluginRepository(vfsStream::url('root'));

        $plugins = iterator_to_array($sut->findAll());

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo', $plugins);
        $this->assertArrayHasKey('bar', $plugins);

        $this->assertEquals(FooPlugin::class, $plugins['foo']);
        $this->assertEquals(BarPlugin::class, $plugins['bar']);
    }

    public function testItFindsOnePackage()
    {
        vfsStream::create([
            'foo' => [
                '.git' => [],
                'composer.json' => json_encode([
                    'name' => 'phraseanet/plugin-Foo',
                    'extra' => [
                        'class' => FooPlugin::class,
                    ],
                ], JSON_PRETTY_PRINT),
            ],
            'bar' => [
                'composer.json' => json_encode([
                    'name' => 'phraseanet/plugin-BAR',
                    'extra' => [
                        'class' => BarPlugin::class,
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ], $this->root);

        $sut = new PluginRepository(vfsStream::url('root'));

        $fqcn = $sut->find('bar');

        $this->assertEquals(BarPlugin::class, $fqcn);
    }
}
