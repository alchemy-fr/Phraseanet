<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\SessionManagerSubscriber;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Session;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
        $this->assertRegExp('#/login\?redirect=..(?:%2F|/)prod#', $client->getResponse()->headers->get('location'));

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
        $app['authentication'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->getMock();
        $app['authentication']->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $session = new Session();
        $session->setUpdated(new \DateTime());

        $app['orm.em'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $app['repo.sessions'] = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')->getMock();
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
        $app['authentication'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->getMock();
        $app['authentication']->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $app['authentication']->expects($this->once())->method('closeAccount')->will($this->returnValue(null));

        $session = new Session();
        $session->setUpdated(new \DateTime('-1 hour'));

        $app['orm.em'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $app['repo.sessions'] = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')->getMock();
        $app['repo.sessions']->expects($this->once())->method('find')->will($this->returnValue($session));
        $app['orm.em']->expects($this->any())->method('persist')->will($this->returnValue(null));
        $app['orm.em']->expects($this->any())->method('flush')->will($this->returnValue(null));

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
        $this->assertRegExp('#/login\?redirect=..(?:%2F|/)prod#', $client->getResponse()->headers->get('location'));

        $app['phraseanet.configuration']['session'] = $bkp;
    }

    public function testEndSessionAuthenticatedWithOutdatedIdleXmlHttpRequest()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->getMock();
        $app['authentication']->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $app['authentication']->expects($this->once())->method('closeAccount')->will($this->returnValue(null));

        $session = new Session();
        $session->setUpdated(new \DateTime('-1 hour'));

        $app['orm.em'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $app['repo.sessions'] = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')->getMock();
        $app['repo.sessions']->expects($this->once())->method('find')->will($this->returnValue($session));
        $app['orm.em']->expects($this->any())->method('persist')->will($this->returnValue(null));
        $app['orm.em']->expects($this->any())->method('flush')->will($this->returnValue(null));

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

    /**
     * @dataProvider forbiddenRouteProvider
     */
    public function testForbiddenRoutes($route)
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new SessionManagerSubscriber($app));
        $app['authentication'] = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->getMock();
        $app['authentication']->expects($this->never())->method('isAuthenticated');

        $app['orm.em'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $app['orm.em']->expects($this->never())->method('flush');

        $app->get('/login', function () {
            return '';
        })->bind("homepage");

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

    public function forbiddenRouteProvider()
    {
        return [
            ['/admin/databox/17/informations/documents/'],
            ['/admin/task-manager/tasks/'],
        ];
    }
}
