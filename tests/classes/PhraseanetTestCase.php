<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Cache\Manager as CacheManager;
use Alchemy\Phrasea\CLI;
use Alchemy\Phrasea\Core\Database\SqlDbResetTool;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Tests\Tools\TranslatorMockTrait;
use Guzzle\Http\Client as Guzzle;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Silex\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Routing\RequestContext;

abstract class PhraseanetTestCase extends WebTestCase
{
    use TranslatorMockTrait;

    /**
     * Define some user agents
     */
    const USER_AGENT_FIREFOX8MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:8.0) Gecko/20100101 Firefox/8.0';
    const USER_AGENT_IE6 = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)';
    const USER_AGENT_IPHONE = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 2_1 like Mac OS X; fr-fr) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5F137 Safari/525.20';

    /** @var Pimple */
    protected static $DI;

    private static $recordsInitialized = false;
    private static $fixtureIds = [];
    /**
     * @var SqlResetLogger
     */
    private static $sqlResetUtil;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $connection;


    public function createApplication()
    {

    }

    public function getApplicationPath()
    {
        return '/lib/Alchemy/Phrasea/Application/Root.php';
    }

    public function setUp()
    {
        parent::setUp();

        self::$DI = new \Pimple();

        ini_set('memory_limit', '4096M');

        error_reporting(-1);

        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;

        self::$DI['fixtures'] = self::$DI->share(function () {
            return self::$fixtureIds;
        });

        self::$sqlResetUtil = new SqlResetLogger();

        $logger = self::$sqlResetUtil;

        if ($configuration->getSQLLogger()) {
            $logger = new \Doctrine\DBAL\Logging\LoggerChain();

            $logger->addLogger($configuration->getSQLLogger());
            $logger->addLogger(self::$sqlResetUtil);
        }

        $configuration->setSQLLogger($logger);
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return self::$DI['app'];
    }

    /**
     * @return databox
     */
    public function getFirstDatabox(Application $app)
    {
        $databoxes = $app->getDataboxes();

        return reset($databoxes);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return self::$DI['client'];
    }

    /**
     * @return record_adapter
     */
    public function getRecord1()
    {
        return self::$DI['record_1'];
    }

    /**
     * @return record_adapter
     */
    public function getRecordStory1()
    {
        return self::$DI['record_story_1'];
    }

    /**
     * @return collection
     */
    public function getCollection()
    {
        return self::$DI['collection'];
    }

    protected function loadCLI($environment = Application::ENV_TEST)
    {
        $cli = new CLI('cli test', null, $environment);
        $this->addMocks($cli);
        $this->addAppCacheFlush($cli);

        return $cli;
    }

    protected function loadApp($path = null, $environment = Application::ENV_TEST)
    {
        if (null !== $path) {
            $app = require __DIR__ . '/../../' . $path;
        } else {
            $app = new Application($environment);
        }

        $this->addAppCacheFlush($app);
        $this->loadDb($app);
        $this->addMocks($app);

        return $app;
    }

    protected function addAppCacheFlush(Application $app)
    {
        $app['phraseanet.cache-service'] = $app->share($app->extend('phraseanet.cache-service', function (CacheManager $cache) {
            $cache->flushAll();

            return $cache;
        }));
    }

    protected function loadDb($app)
    {
        SqlDbResetTool::loadDatabase($app['orm.em']->getConnection());
    }

    protected function addMocks(Application $app)
    {
        $app['form.csrf_provider'] = $app->share(function () {
            return new CsrfTestProvider();
        });

        $app['url_generator'] = $app->share($app->extend('url_generator', function ($generator, $app) {
            $host = parse_url($app['conf']->get('servername'), PHP_URL_HOST);

            $generator->setContext(new RequestContext('', 'GET', $host ?: $app['conf']->get('servername')));

            return $generator;
        }));

        $app['task-manager.notifier'] = $app->share($app->extend('task-manager.notifier', function (Notifier $notifier) {
            $notifier->setTimeout(0.0001);

            return $notifier;
        }));

        $app['translator'] = $this->createTranslatorMock();

        $app['phraseanet.SE.subscriber'] = new PhraseanetSeTestSubscriber();

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
        \thesaurus_xpath::purge();

        self::deleteResources();

        // close all connection
        self::$DI['app']['connection.pool.manager']->closeAll();

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
                $app->getAclForUser($user)->set_admin(true);
                $app->getAclForUser($user)->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app->getAclForUser($user)->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
                break;
            case self::$fixtureIds['user']['user_1']:
            case self::$fixtureIds['user']['user_2']:
            case self::$fixtureIds['user']['user_3']:
            case self::$fixtureIds['user']['test_phpunit_not_admin']:
            case self::$fixtureIds['user']['test_phpunit_alt1']:
            case self::$fixtureIds['user']['test_phpunit_alt2']:
            case self::$fixtureIds['user']['user_template']:
                self::giveRightsToUser($app, $user);
                $app->getAclForUser($user)->set_admin(false);
                $app->getAclForUser($user)->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app->getAclForUser($user)->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
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
    public static function giveRightsToUser(Application $app, User $user, $base_ids = null, $force = false)
    {
        $app->getAclForUser($user)->delete_data_from_cache(\ACL::CACHE_GLOBAL_RIGHTS);
        $app->getAclForUser($user)->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        $app->getAclForUser($user)->give_access_to_sbas(array_keys($app->getDataboxes()));

        foreach ($app->getDataboxes() as $databox) {
            $app->getAclForUser($user)->delete_data_from_cache(\ACL::CACHE_RIGHTS_SBAS);

            $rights = [
                'bas_manage'        => '1'
                , 'bas_modify_struct' => '1'
                , 'bas_modif_th'      => '1'
                , 'bas_chupub'        => '1'
            ];

            $app->getAclForUser($user)->update_rights_to_sbas($databox->get_sbas_id(), $rights);

            foreach ($databox->get_collections() as $collection) {
                if (null !== $base_ids && !in_array($collection->get_base_id(), (array) $base_ids, true)) {
                    continue;
                }

                $base_id = $collection->get_base_id();


                if ($app->getAclForUser($user)->has_access_to_base($base_id) && false === $force) {
                    continue;
                }

                $app->getAclForUser($user)->delete_data_from_cache(\ACL::CACHE_RIGHTS_BAS);
                $app->getAclForUser($user)->give_access_to_base([$base_id]);
                $app->getAclForUser($user)->update_rights_to_base($base_id, ['order_master' => true]);

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
                    , 'bas_modify_struct' => '1'
                ];

                $app->getAclForUser($user)->update_rights_to_base($collection->get_base_id(), $rights);
            }
        }
    }

    /**
     * Deletes previously created Resources.
     */
    private static function deleteResources()
    {
        if (!empty(self::$recordsInitialized)) {

            foreach (self::$recordsInitialized as $i) {
                self::$DI['record_' . $i]->delete();
            }

            self::$recordsInitialized = [];
        }
        // Clear fixtures
        self::$fixtureIds = [];
    }

    /**
     * Authenticates self::['user'] against application.
     *
     * @param Application $app
     * @param User        $user
     */
    protected function authenticate(Application $app, User $user = null)
    {
        /** @var User $user */
        $user = $user ?: self::$DI['user'];

        $app['session']->clear();
        $app['session']->set('usr_id', $user->getId());
        $session = new Session();
        $session->setUser($user);
        $session->setUserAgent('');
        $app['orm.em']->persist($session);
        $app['orm.em']->flush();

        $app['session']->set('session_id', $session->getId());

        $app->getAuthenticator()->reinitUser();
    }

    /**
     * Logout authenticated user from application.
     *
     * @param Application $app
     */
    protected function logout(Application $app)
    {
        $app['session']->clear();
        $app->getAuthenticator()->reinitUser();
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
        $app['orm.em']->remove($user);
        $app['orm.em']->flush();
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

    protected function assertDateNear($expected, $tested, $precision = 1)
    {
        $tested = $tested instanceof \DateTime ? $tested : new \DateTime($tested);
        $expected = $expected instanceof \DateTime ? $expected : new \DateTime($expected);

        $this->assertLessThanOrEqual($precision, abs($expected->format('U') - $tested->format('U')));
    }

    protected function createSearchEngineMock()
    {
        $mock = $this->getMock(SearchEngineInterface::class);
        $mock->expects($this->any())
            ->method('createSubscriber')
            ->will($this->returnValue($this->getMock('Symfony\Component\EventDispatcher\EventSubscriberInterface')));
        $mock->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue([]));

        return $mock;
    }
}
