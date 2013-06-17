<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Symfony\Component\HttpFoundation\Request;

class DebuggerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        if (is_file(__DIR__ . '/Fixtures/configuration-debugger.php')) {
            unlink(__DIR__ . '/Fixtures/configuration-debugger.php');
        }
    }

    /**
     * @dataProvider provideIpsAndEnvironments
     */
    public function testIpsAndEnvironments($exceptionThrown, $env, $incomingIp, $authorized)
    {
        $app = new Application($env);
        unset($app['exception_handler']);

        $app['phraseanet.configuration.config-path'] = __DIR__ . '/Fixtures/configuration-debugger.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/Fixtures/configuration-debugger.php';

        if (is_file($app['phraseanet.configuration.config-compiled-path'])) {
            unlink($app['phraseanet.configuration.config-compiled-path']);
        }

        $app['phraseanet.configuration']['debugger'] = array('allowed-ips' => $authorized);
        $app['dispatcher']->addSubscriber(new DebuggerSubscriber($app));
        $app->get('/', function () {
            return 'success';
        });
        $app->boot();

        if ($exceptionThrown) {
            $this->setExpectedException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');
        }

        $app->handle(new Request(array(), array(), array(), array(), array(), array('REMOTE_ADDR' => $incomingIp)));
    }

    public function provideIpsAndEnvironments()
    {
        return array(
            array(false, Application::ENV_PROD, '127.0.0.1', array()),
            array(false, Application::ENV_PROD, '192.168.0.1', array()),
            array(false, Application::ENV_DEV, '127.0.0.1', array()),
            array(true, Application::ENV_DEV, '192.168.0.1', array()),
            array(false, Application::ENV_DEV, '192.168.0.1', array('192.168.0.1')),
            array(false, Application::ENV_TEST, '127.0.0.1', array()),
            array(false, Application::ENV_TEST, '192.168.0.1', array()),
        );
    }
}
