<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Symfony\Component\HttpFoundation\Request;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DebuggerSubscriberTest extends \PhraseanetTestCase
{
    private $bkp = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->bkp = self::$DI['app']['conf']->get('debugger');
    }

    public function tearDown(): void
    {
        if(is_null($this->bkp)) {
            self::$DI['app']['conf']->remove('debugger');
        }
        else {
            self::$DI['app']['conf']->set('debugger', $this->bkp);
        }
        if (is_file(__DIR__ . '/Fixtures/configuration-debugger.php')) {
            unlink(__DIR__ . '/Fixtures/configuration-debugger.php');
        }
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIpsAndEnvironments')]
    public function testIpsAndEnvironments($exceptionThrown, $env, $incomingIp, $authorized)
    {
        $app = new Application($env);
        unset($app['exception_handler']);

        $app['phraseanet.configuration.config-path'] = __DIR__ . '/Fixtures/configuration-debugger.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/Fixtures/configuration-debugger.php';

        if (is_file($app['phraseanet.configuration.config-compiled-path'])) {
            unlink($app['phraseanet.configuration.config-compiled-path']);
        }

        $app['conf']->set(['debugger', 'allowed-ips'], $authorized);
        $app['dispatcher']->addSubscriber(new DebuggerSubscriber($app));

        $app->get('/', function () {
            return 'success';
        });

        $app->boot();

        if ($exceptionThrown) {
            $this->expectException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');
        }

        $app->handle(new Request([], [], [], [], [], ['REMOTE_ADDR' => $incomingIp]));
    }

    public static function provideIpsAndEnvironments()
    {
        return [
            [false, Application::ENV_PROD, '127.0.0.1', []],
            [true, Application::ENV_PROD, '192.168.0.1', []],
            [false, Application::ENV_DEV, '127.0.0.1', []],
            [true, Application::ENV_DEV, '192.168.0.1', []],
            [false, Application::ENV_DEV, '192.168.0.1', ['192.168.0.1']],
            [false, Application::ENV_TEST, '127.0.0.1', []],
            [true, Application::ENV_TEST, '192.168.0.1', []],
        ];
    }
}
