<?php

namespace Alchemy\Tests\Phrasea\Application;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\RouteLoader;
use Prophecy\Argument;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Route;

class RouteLoaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterProviderWithInvalidClassFails()
    {
        $routeLoader = new RouteLoader();

        $routeLoader->registerProvider('test_invalid_class', '\Alchemy\Tests\Phrasea\Application\UndefinedClass');
    }

    public function testRegisteredProvidersAreMountedInApplication()
    {
        $application = $this->prophesize(Application::class);
        $application->offsetGet(Argument::any())
            ->shouldBeCalled();
        $application->mount(Argument::any(), Argument::type(ControllerProviderInterface::class))
            ->shouldBeCalled();
        $application->mount(Argument::exact('mount_prefix'), Argument::type(MockControllerProvider::class))
            ->shouldBeCalled();

        $routeLoader = new RouteLoader();
        $routeLoader->registerProvider('mount_prefix', MockControllerProvider::class);

        $routeLoader->bindRoutes($application->reveal());
    }
}

class MockControllerProvider implements ControllerProviderInterface
{
    public function connect(\Silex\Application $app)
    {
        return new ControllerCollection(new Route('/'));
    }
}
