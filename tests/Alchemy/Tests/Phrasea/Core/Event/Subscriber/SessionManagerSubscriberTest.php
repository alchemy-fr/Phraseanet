<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\SessionManagerSubscriber;
use Alchemy\Phrasea\Model\Entities\Session;
use Symfony\Component\HttpKernel\HttpKernelBrowser as Client;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class SessionManagerSubscriberTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testEndSession()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 0,
            'lifetime' => 60475,
        ];

        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/prod', function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', '/prod');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertNotNUll($client->getResponse()->headers->get('x-phraseanet-end-session'));
        $this->assertNotNUll($client->getResponse()->headers->get('location'));
        $this->assertMatchesRegularExpression('#/login\?redirect=..(?:%2F|/)prod#', $client->getResponse()->headers->get('location'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testEndSessionXmlXhttpRequest()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 0,
            'lifetime' => 60475,
        ];

        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/prod', function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', '/prod', [], [], [
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',

        ]);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertNotNUll($client->getResponse()->headers->get('x-phraseanet-end-session'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testEndSessionAuthenticated()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->createStub('Alchemy\Phrasea\Authentication\Authenticator');
        $app['authentication']->method('isAuthenticated')->will($this->returnValue(true));

        $session = new Session();
        $session->setUpdated(new \DateTime());

        $app['orm.em'] = $this->createStub('Doctrine\ORM\EntityManager');
        $app['repo.sessions'] = $this->createStub('Doctrine\Common\Persistence\ObjectRepository');
        $app['repo.sessions']->expects($this->exactly(2))->method('find')->will($this->returnValue($session));
        $app['orm.em']->expects($this->exactly(4))->method('persist')->will($this->returnValue(null));
        $app['orm.em']->expects($this->exactly(2))->method('flush')->will($this->returnValue(null));

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 0,
            'lifetime' => 60475,
        ];
        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/prod', function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', '/prod');

        $this->assertTrue($client->getResponse()->isOK());

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testEndSessionAuthenticatedWithOutdatedIdle()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->createStub('Alchemy\Phrasea\Authentication\Authenticator');
        $app['authentication']->method('isAuthenticated')->will($this->returnValue(true));
        $app['authentication']->expects($this->once())->method('closeAccount')->will($this->returnValue(null));

        $session = new Session();
        $session->setUpdated(new \DateTime('-1 hour'));

        $app['orm.em'] = $this->createStub('Doctrine\ORM\EntityManager');
        $app['repo.sessions'] = $this->createStub('Doctrine\Common\Persistence\ObjectRepository');
        $app['repo.sessions']->expects($this->once())->method('find')->will($this->returnValue($session));
        $app['orm.em']->method('persist')->will($this->returnValue(null));
        $app['orm.em']->method('flush')->will($this->returnValue(null));

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 10,
            'lifetime' => 60475,
        ];
        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/prod', function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', '/prod');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertNotNUll($client->getResponse()->headers->get('x-phraseanet-end-session'));
        $this->assertNotNUll($client->getResponse()->headers->get('location'));
        $this->assertMatchesRegularExpression('#/login\?redirect=..(?:%2F|/)prod#', $client->getResponse()->headers->get('location'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testEndSessionAuthenticatedWithOutdatedIdleXmlHttpRequest()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->createStub('Alchemy\Phrasea\Authentication\Authenticator');
        $app['authentication']->method('isAuthenticated')->will($this->returnValue(true));
        $app['authentication']->expects($this->once())->method('closeAccount')->will($this->returnValue(null));

        $session = new Session();
        $session->setUpdated(new \DateTime('-1 hour'));

        $app['orm.em'] = $this->createStub('Doctrine\ORM\EntityManager');
        $app['repo.sessions'] = $this->createStub('Doctrine\Common\Persistence\ObjectRepository');
        $app['repo.sessions']->expects($this->once())->method('find')->will($this->returnValue($session));
        $app['orm.em']->method('persist')->will($this->returnValue(null));
        $app['orm.em']->method('flush')->will($this->returnValue(null));

        $bkp = $app['phraseanet.configuration']['session'];

        $app['phraseanet.configuration']['session'] = [
            'idle' => 10,
            'lifetime' => 60475,
        ];
        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/prod', function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', '/prod', [], [], [
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertNotNUll($client->getResponse()->headers->get('x-phraseanet-end-session'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testUndefinedModule()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));

        $app->get('/login', function () {
            return '';
        })->bind("homepage");

        $app->get('/undefined-module', function () {
            return 'undefined-module';
        });

        $client = new Client($app);
        $client->request('GET', '/undefined-module');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('undefined-module', $client->getResponse()->getContent());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenRouteProvider')]
    public function testForbiddenRoutes($route)
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->createStub('Alchemy\Phrasea\Authentication\Authenticator');
        $app['authentication']->expects($this->never())->method('isAuthenticated');

        $app['orm.em'] = $this->createStub('Doctrine\ORM\EntityManager');
        $app['orm.em']->expects($this->never())->method('flush');

        $app->get($route, function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', $route, [], [], [
            'HTTP_CONTENT-TYPE'     => 'application/json',
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public static function forbiddenRouteProvider()
    {
        return [
            ['/'],
            ['/login/'],
            ['/session/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notUserActivityRoutesProvider')]
    public function testNotUserActivityRoutes($route)
    {
        $this->markTestSkipped();
        return;

        // todo : rewrite this test because while not authenticated, the subscriber returns before testing special routes

        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->createStub('Alchemy\Phrasea\Authentication\Authenticator');
        $app['authentication']->expects($this->once())->method('isAuthenticated');

        $app['orm.em'] = $this->createStub('Doctrine\ORM\EntityManager');
        $app['orm.em']->expects($this->never())->method('flush');

        $app->get($route, function () {
            return '';
        });

        $client = new Client($app);
        $client->request('GET', $route, [], [], [
            'HTTP_CONTENT-TYPE'     => 'application/json',
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public static function notUserActivityRoutesProvider()
    {
        return [
            ['/user/'],
            ['/admin/task-manager/tasks/'],
            ['/admin/databox/17/informations/documents/']
        ];
    }
}
