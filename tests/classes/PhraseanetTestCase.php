<?php

use Alchemy\Phrasea\CLI;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\User;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RequestContext;
use Alchemy\Tests\Tools\TranslatorMockTrait;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\TaskManager\Notifier;
use Guzzle\Http\Client as Guzzle;

abstract class PhraseanetTestCase extends WebTestCase
{
    use TranslatorMockTrait;

    /**
     * Define some user agents
     */
    const USER_AGENT_FIREFOX8MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:8.0) Gecko/20100101 Firefox/8.0';
    const USER_AGENT_IE6 = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)';
    const USER_AGENT_IPHONE = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 2_1 like Mac OS X; fr-fr) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5F137 Safari/525.20';

    public $app;

    /**
     * @var \Pimple
     */
    protected static $DI;

    private static $recordsInitialized = false;

    /**
     * Tell if tables were updated with new schemas
     * @var boolean
     */
    private static $booted;
    private static $testCaseBooted;

    private static $fixtureIds = [];

    private function initializeSqliteDB($path = '/tmp/db.sqlite')
    {
        if (is_file($path)) {
            unlink($path);
        }
        copy(__DIR__ . '/../db-ref.sqlite', $path);
    }

    public function createApplication()
    {

    }

    /**
     * Delete all ressources created during the test
     */
    public function __destruct()
    {
        self::deleteResources();
    }

    public function setUp()
    {
        parent::setUp();

        if (null !== self::$DI) {
            unset(self::$DI['app']['dbal.provider']);
        }

        self::$DI = new \Pimple();

        ini_set('memory_limit', '4096M');

        error_reporting(-1);
        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;

        self::$DI['app'] = self::$DI->share(function ($DI) {
            return $this->loadApp('/lib/Alchemy/Phrasea/Application/Root.php');
        });

        self::$DI['cli'] = self::$DI->share(function ($DI) {
            return $this->loadCLI();
        });

        self::$DI['local-guzzle'] = self::$DI->share(function ($DI) {
            return new Guzzle(self::$DI['app']['conf']->get('servername'));
        });

        self::$DI['client'] = self::$DI->share(function ($DI) {
            return new Client($DI['app'], []);
        });

        self::$DI['user'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['test_phpunit']);
        });

        self::$DI['user_1'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['user_1']);
        });

        self::$DI['user_2'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['user_2']);
        });

        self::$DI['user_3'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['user_3']);
        });

        self::$DI['user_guest'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['user_guest']);
        });

        self::$DI['user_notAdmin'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['test_phpunit_not_admin']);
        });

        self::$DI['user_alt1'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['test_phpunit_alt1']);
        });

        self::$DI['user_alt2'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['test_phpunit_alt2']);
        });

        self::$DI['user_template'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.users']->find(self::$fixtureIds['user']['user_template']);
        });

        self::$DI['registration_1'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.registrations']->find(self::$fixtureIds['registrations']['registration_1']);
        });
        self::$DI['registration_2'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.registrations']->find(self::$fixtureIds['registrations']['registration_2']);
        });
        self::$DI['registration_3'] = self::$DI->share(function ($DI) {
            return $DI['app']['repo.registrations']->find(self::$fixtureIds['registrations']['registration_3']);
        });

        self::$DI['oauth2-app-user'] = self::$DI->share(function ($DI) {
            return new \API_OAuth2_Application($DI['app'], self::$fixtureIds['oauth']['user']);
        });

        self::$DI['oauth2-app-user_notAdmin'] = self::$DI->share(function ($DI) {
            return new \API_OAuth2_Application($DI['app'], self::$fixtureIds['oauth']['user_notAdmin']);
        });

        self::$DI['logger'] = self::$DI->share(function () {
            $logger = new Logger('tests');
            $logger->pushHandler(new NullHandler());

            return $logger;
        });

        self::$DI['collection'] = self::$DI->share(function ($DI) {
            return collection::get_from_base_id($DI['app'], self::$fixtureIds['collection']['coll']);
        });

        self::$DI['collection_no_access'] = self::$DI->share(function ($DI) {
            return collection::get_from_base_id($DI['app'], self::$fixtureIds['collection']['coll_no_access']);
        });

        self::$DI['collection_no_access_by_status'] = self::$DI->share(function ($DI) {
            return collection::get_from_base_id($DI['app'], self::$fixtureIds['collection']['coll_no_status']);
        });

        if (!self::$booted) {
            if (!self::$DI['app']['phraseanet.configuration-tester']->isInstalled()) {
                echo "\033[0;31mPhraseanet is not set up\033[0;37m\n";
                exit(1);
            }

            self::$fixtureIds = array_merge(self::$fixtureIds, json_decode(file_get_contents(__DIR__ .'/../fixtures.json'), true));

            self::resetUsersRights(self::$DI['app'], self::$DI['user']);
            self::resetUsersRights(self::$DI['app'], self::$DI['user_notAdmin']);

            self::$booted = true;
        }

        self::$DI['lazaret_1'] = self::$DI->share(function ($DI) {
            return $DI['app']['EM']->find('Phraseanet:LazaretFile', self::$fixtureIds['lazaret']['lazaret_1']);
        });

        foreach (range(1, 7) as $i) {
            self::$DI['record_' . $i] = self::$DI->share(function ($DI) use ($i) {
                return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], self::$fixtureIds['record']['record_'.$i]);
            });
        }

        foreach (range(1, 3) as $i) {
            self::$DI['record_story_' . $i] = self::$DI->share(function ($DI) use ($i) {
                return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], self::$fixtureIds['record']['record_story_'.$i]);
            });
        }

        self::$DI['record_no_access_resolver'] = self::$DI->protect(function () {
            $id = 'no_access';

            if (isset(self::$fixtureIds['records'][$id])) {
                return self::$fixtureIds['records'][$id];
            }

            self::$recordsInitialized[] = $id;
            $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), self::$DI['collection_no_access']);
            $record = record_adapter::createFromFile($file, self::$DI['app']);
            self::$DI['app']['subdef.generator']->generateSubdefs($record);
            self::$fixtureIds['records'][$id] = $record->get_record_id();

            return self::$fixtureIds['records'][$id];
        });

        self::$DI['record_no_access_by_status_resolver'] = self::$DI->protect(function () {
            $id = 'no_access_by_status';

            if (isset(self::$fixtureIds['records'][$id])) {
                return self::$fixtureIds['records'][$id];
            }

            self::$recordsInitialized[] = $id;
            $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), self::$DI['collection_no_access_by_status']);
            $record = record_adapter::createFromFile($file, self::$DI['app']);
            self::$DI['app']['subdef.generator']->generateSubdefs($record);
            self::$fixtureIds['records'][$id] = $record->get_record_id();

            return self::$fixtureIds['records'][$id];
        });

        self::$DI['record_no_access'] = self::$DI->share(function ($DI) {
            return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], $DI['record_no_access_resolver']());
        });

        self::$DI['record_no_access_by_status'] = self::$DI->share(function ($DI) {
            return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], $DI['record_no_access_by_status_resolver']());
        });

        if (!self::$testCaseBooted) {
            $this->bootTestCase();
        }
        self::$testCaseBooted = true;
    }

    public static function tearDownAfterClass()
    {
        self::$testCaseBooted = false;
        gc_collect_cycles();
        parent::tearDownAfterClass();
    }

    protected function bootTestCase()
    {

    }

    protected function loadCLI($environment = Application::ENV_TEST)
    {
        $cli = new CLI('cli test', null, $environment);
        $this->addMocks($cli);

        return $cli;
    }

    protected function loadApp($path = null, $environment = Application::ENV_TEST)
    {
        if (null !== $path) {
            $app = require __DIR__ . '/../../' . $path;
        } else {
            $app = new Application($environment);
        }

        $this->addMocks($app);

        return $app;
    }

    protected function addMocks(Application $app)
    {
        $app['debug'] = true;

        $app['form.csrf_provider'] = $app->share(function () {
            return new CsrfTestProvider();
        });

        $app['url_generator'] = $app->share($app->extend('url_generator', function ($generator, $app) {
            $host = parse_url($app['conf']->get('servername'), PHP_URL_HOST);
            $generator->setContext(new RequestContext('', 'GET', $host));

            return $generator;
        }));

        $app['task-manager.notifier'] = $app->share($app->extend('task-manager.notifier', function (Notifier $notifier) {
            $notifier->setTimeout(0.0001);

            return $notifier;
        }));

        $app['translator'] = $this->createTranslatorMock();

        $app['phraseanet.SE.subscriber'] = $this->getMock('Symfony\Component\EventDispatcher\EventSubscriberInterface');
        $app['phraseanet.SE.subscriber']::staticExpects($this->any())
            ->method('getSubscribedEvents')
            ->will($this->returnValue([]));

        $app['EM'] = $app->share($app->extend('EM', function ($em) {
            $this->initializeSqliteDB();

            return $em;
        }));

        $app['browser'] = $app->share($app->extend('browser', function ($browser) {
            $browser->setUserAgent(self::USER_AGENT_FIREFOX8MAC);

            return $browser;
        }));

        $app['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $app['notification.deliverer']->expects($this->any())
            ->method('deliver')
            ->will($this->returnCallback(function () {
                $this->fail('Notification deliverer must be mocked');
            }));
    }

    public function tearDown()
    {
        ACLProvider::purge();
        \collection::purge();
        \databox::purge();
        \caption_field::purge();
        \caption_Field_Value::purge();
        \databox_field::purge();
        \databox_status::purge();
        \thesaurus_xpath::purge();

        /**
         * Kris Wallsmith pro-tip
         * @see http://kriswallsmith.net/post/18029585104/faster-phpunit
         */
        $refl = new ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_') && 0 !== strpos($prop->getDeclaringClass()->getName(), 'Phraseanet')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
        $refl = null;

        parent::tearDown();

        //In case some executed script modify 'max_execution_time' ini var
        //Initialize set_time_limit(0) which is the default value for PHP CLI

        set_time_limit(0);
    }

    protected function assertForbiddenResponse(Response $response)
    {
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'Sorry, you do have access to the page you are looking for'));
    }

    protected function assertBadResponse(Response $response)
    {
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'bad request'));
    }

    protected function assertNotFoundResponse(Response $response)
    {
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'Sorry, the page you are looking for could not be found'));
    }

    protected function assertDateAtom($date)
    {
        return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
    }

    protected function set_user_agent($user_agent, Application $app)
    {
        $app['browser']->setUserAgent($user_agent);
        $app->register(new \Silex\Provider\TwigServiceProvider());
        $app->setupTwig();
        self::$DI['client'] = self::$DI->share(function ($DI) use ($app) {
            return new Client($app, []);
        });
    }

    /**
     * Calls a URI as XMLHTTP request.
     *
     * @param string $method     The request method
     * @param string $uri        The URI to fetch
     * @param array  $parameters The Request parameters
     * @param string $httpAccept Contents of the Accept header
     *
     * @return Crawler
     */
    protected function XMLHTTPRequest($method, $uri, array $parameters = [], $httpAccept = 'application/json')
    {
        return self::$DI['client']->request($method, $uri, $parameters, [], [
            'HTTP_ACCEPT'           => $httpAccept,
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    protected static function resetUsersRights(Application $app, User $user)
    {
        switch ($user->getId()) {
            case self::$fixtureIds['user']['test_phpunit']:
                self::giveRightsToUser($app, $user);
                $app['acl']->get($user)->set_admin(true);
                $app['acl']->get(self::$DI['user'])->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
                break;
            case self::$fixtureIds['user']['user_1']:
            case self::$fixtureIds['user']['user_2']:
            case self::$fixtureIds['user']['user_3']:
            case self::$fixtureIds['user']['test_phpunit_not_admin']:
            case self::$fixtureIds['user']['test_phpunit_alt1']:
            case self::$fixtureIds['user']['test_phpunit_alt2']:
            case self::$fixtureIds['user']['user_template']:
                self::giveRightsToUser($app, $user);
                $app['acl']->get($user)->set_admin(false);
                $app['acl']->get(self::$DI['user'])->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('User %s not found', $user->getLogin()));
        }
    }

    /**
     * Gives Bases Rights to User.
     *
     * @param User $user
     */
    public static function giveRightsToUser(Application $app, User $user, $base_ids = null)
    {
        $app['acl']->get($user)->give_access_to_sbas(array_keys($app['phraseanet.appbox']->get_databoxes()));

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            $rights = [
                'bas_manage'        => '1'
                , 'bas_modify_struct' => '1'
                , 'bas_modif_th'      => '1'
                , 'bas_chupub'        => '1'
            ];

            $app['acl']->get($user)->update_rights_to_sbas($databox->get_sbas_id(), $rights);

            foreach ($databox->get_collections() as $collection) {
                if (null !== $base_ids && !in_array($collection->get_base_id(), (array) $base_ids, true)) {
                    continue;
                }

                $base_id = $collection->get_base_id();

                $app['acl']->get($user)->give_access_to_base([$base_id]);
                $app['acl']->get($user)->update_rights_to_base($base_id, ['order_master' => true]);

                $rights = [
                    'canputinalbum'     => '1'
                    , 'candwnldhd'        => '1'
                    , 'candwnldsubdef'    => '1'
                    , 'nowatermark'       => '1'
                    , 'candwnldpreview'   => '1'
                    , 'cancmd'            => '1'
                    , 'canadmin'          => '1'
                    , 'canreport'         => '1'
                    , 'canpush'           => '1'
                    , 'creationdate'      => '1'
                    , 'canaddrecord'      => '1'
                    , 'canmodifrecord'    => '1'
                    , 'candeleterecord'   => '1'
                    , 'chgstatus'         => '1'
                    , 'imgtools'          => '1'
                    , 'manage'            => '1'
                    , 'modify_struct'     => '1'
                    , 'manage'            => '1'
                    , 'bas_modify_struct' => '1'
                ];

                $app['acl']->get($user)->update_rights_to_base($collection->get_base_id(), $rights);
            }
        }
    }

    /**
     * Deletes previously created Resources.
     */
    private static function deleteResources()
    {
        if (self::$recordsInitialized !== false) {
            foreach (self::$recordsInitialized as $i) {
                self::$DI['record_' . $i]->delete();
            }

            self::$recordsInitialized = [];
        }

        $duration = PhraseanetPHPUnitListener::getDurationByTest();
        $tests = [];

        foreach ($duration as $name => $data) {
            $tests[$name . '(total : '.$data['time'].' and '.$data['executions'].' executions)'] = $data['time'] / $data['executions'];
        }

        asort($tests);

        $csvData = PhraseanetPHPUnitListener::getCsv();
        if (count($csvData) > 0) {
            foreach ($csvData as $data) {
                file_put_contents(__DIR__ . '/../../report.csv', "\"".implode('","', array_map(function ($value) {
                    return str_replace('"', '""', $value);
                }, $data))."\"\n", FILE_APPEND);
            }
        }

        PhraseanetPHPUnitListener::resetDuration();

        return;
    }

    /**
     * Authenticates self::['user'] against application.
     *
     * @param Application $app
     * @param User        $user
     */
    protected function authenticate(Application $app, $user = null)
    {
        $user = $user ?: self::$DI['user'];

        $app['session']->clear();
        $app['session']->set('usr_id', self::$DI['user']->getId());
        $session = new Session();
        $session->setUser(self::$DI['user']);
        $session->setUserAgent('');
        self::$DI['app']['EM']->persist($session);
        self::$DI['app']['EM']->flush();

        $app['session']->set('session_id', $session->getId());

        self::$DI['app']['authentication']->reinitUser();
    }

    /**
     * Logout authenticated user from application.
     *
     * @param Application $app
     */
    protected function logout(Application $app)
    {
        $app['session']->clear();
        self::$DI['app']['authentication']->reinitUser();
    }

    protected function assertXMLHTTPBadJsonResponse(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue(is_array($data));
        $this->assertFalse($data['success']);
    }

    protected function mockNotificationDeliverer($expectedMail, $qty = 1, $receipt = null)
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['app']['notification.deliverer']->expects($this->exactly($qty))
            ->method('deliver')
            ->with($this->isInstanceOf($expectedMail), $this->equalTo($receipt));
    }

    protected function mockNotificationsDeliverer(array &$expectedMails)
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['notification.deliverer']->expects($this->any())
            ->method('deliver')
            ->will($this->returnCallback(function ($email, $receipt) use (&$expectedMails) {
                $this->assertTrue(isset($expectedMails[get_class($email)]));
                $expectedMails[get_class($email)]++;
            }));
    }

    protected function mockUserNotificationSettings($notificationName, $returnValue = true)
    {
        if (false === self::$DI['app']['settings'] instanceof \PHPUnit_Framework_MockObject_MockObject) {
            self::$DI['app']['settings'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\DisplaySettingService')
                ->disableOriginalConstructor()
                ->getMock();
        }

        self::$DI['app']['settings']
            ->expects($this->any())
            ->method('getUserNotificationSetting')
            ->with(
                $this->isInstanceOf('Alchemy\Phrasea\Model\Entities\User'),
                $this->equalTo($notificationName)
            )
            ->will($this->returnValue($returnValue));

        self::$DI['app']['setting'] = 'toto';
    }

    public function createGeneratorMock()
    {
        return $this->getMockBuilder('Randomlib\Generator')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function createAppboxMock()
    {
        return $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function createUserMock()
    {
        return $this->getMock('Alchemy\Phrasea\Model\Entities\User');
    }

    public function removeUser(Application $app, User $user)
    {
        $app['EM']->remove($user);
        $app['EM']->flush();
    }

    protected function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    protected function createMonologMock()
    {
        return $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityRepositoryMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
