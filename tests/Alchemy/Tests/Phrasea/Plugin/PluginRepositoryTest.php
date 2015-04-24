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

use Alchemy\Phrasea\Plugin\Plugin;
use Alchemy\Phrasea\Plugin\PluginRepository;
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
                ], JSON_PRETTY_PRINT),
            ],
        ], $this->root);

        $sut = new PluginRepository(vfsStream::url('root'));

        $iterator = $sut->findAll();
        $this->assertInstanceOf('Iterator', $iterator);

        $expected = [
            'bar' => [
                'name'     => 'BAR',
                'class'    => Plugin::class,
                'basePath' => vfsStream::url('root/bar'),
            ],
            'foo' => [
                'name'     => 'Foo',
                'class'    => FooPlugin::class,
                'basePath' => vfsStream::url('root/foo'),
            ]
        ];

        $this->assertEquals($expected, iterator_to_array($iterator));
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
                ], JSON_PRETTY_PRINT),
            ],
        ], $this->root);

        $sut = new PluginRepository(vfsStream::url('root'));

        $expected = [
            'name'     => 'BAR',
            'class'    => Plugin::class,
            'basePath' => vfsStream::url('root/bar'),
        ];

        $this->assertEquals($expected, $sut->find('bar'));
    }
}
