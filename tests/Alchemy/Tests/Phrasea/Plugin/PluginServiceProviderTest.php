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

use Alchemy\Phrasea\Plugin\PluginServiceProvider;
use Prophecy\Argument;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\ServiceProviderInterface;

class PluginServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PluginServiceProvider */
    private $sut;

    protected function setUp()
    {
        $this->sut = new \Alchemy\Phrasea\Plugin\PluginServiceProvider();
    }

    public function testItImplementsServiceProviderInterface()
    {
        $this->assertInstanceOf(ServiceProviderInterface::class, $this->sut);
    }

    public function testItAddsPluginAssetFunctionToTwigAndShareTwig()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider());

        $app->register($this->sut);

        $twig = $app['twig'];

        $this->assertInstanceOf('Twig_Environment', $twig);
        $this->assertSame($twig, $app['twig']);
        $this->assertArrayHasKey('plugin_asset', $twig->getFunctions());
    }
}
