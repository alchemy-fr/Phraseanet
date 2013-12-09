<?php

use Alchemy\Phrasea\CLI;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Alchemy\Phrasea\Model\Entities\FeedToken;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Entities\ValidationSession;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Entities\UsrListOwner;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListEntry;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Silex\WebTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\Routing\RequestContext;

use Alchemy\Tests\Tools\TranslatorMockTrait;

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

        self::$DI = new \Pimple();

        ini_set('memory_limit', '4096M');

        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;

        self::$DI['app'] = self::$DI->share(function ($DI) {
            return $this->loadApp('/lib/Alchemy/Phrasea/Application/Root.php');
        });

        self::$DI['cli'] = self::$DI->share(function ($DI) {
            return $this->loadCLI();
        });

        self::$DI['client'] = self::$DI->share(function ($DI) {
            return new Client($DI['app'], []);
        });

        self::$DI['user'] = self::$DI->share(function ($DI) {
            $user = User_Adapter::getInstance(self::$fixtureIds['user']['test_phpunit'], $DI['app']);
            $user->purgePreferences();

            return $user;
        });

        self::$DI['user_notAdmin'] = self::$DI->share(function ($DI) {
            return User_Adapter::getInstance(self::$fixtureIds['user']['test_phpunit_not_admin'], $DI['app']);
        });

        self::$DI['user_alt1'] = self::$DI->share(function ($DI) {
            return User_Adapter::getInstance(self::$fixtureIds['user']['test_phpunit_alt1'], $DI['app']);
        });

        self::$DI['user_alt2'] = self::$DI->share(function ($DI) {
            return User_Adapter::getInstance(self::$fixtureIds['user']['test_phpunit_alt2'], $DI['app']);
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

            $this->createSetOfUserTests(self::$DI['app']);
            self::setCollection(self::$DI['app']);

            self::resetUsersRights(self::$DI['app'], self::$DI['user']);
            self::resetUsersRights(self::$DI['app'], self::$DI['user_notAdmin']);

            self::$booted = true;
        }

        self::$DI['record_id_resolver'] = self::$DI->protect(function ($id) {
            if (isset(self::$fixtureIds['records'][$id])) {
                return self::$fixtureIds['records'][$id];
            }

            self::$recordsInitialized[] = $id;
            $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/' . ($id < 10 ? 'test00' . $id . '.jpg' : 'test0' . $id . '.jpg')), self::$DI['collection']);
            $record = record_adapter::createFromFile($file, self::$DI['app']);
            $record->generate_subdefs($record->get_databox(), self::$DI['app']);
            self::$fixtureIds['records'][$id] = $record->get_record_id();

            return self::$fixtureIds['records'][$id];
        });

        self::$DI['story_id_resolver'] = self::$DI->protect(function ($id) {
            $id = 'story_'.$id;

            if (isset(self::$fixtureIds['records'][$id])) {
                return self::$fixtureIds['records'][$id];
            }

            self::$recordsInitialized[] = $id;
            $story = record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

            $media = self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg');
            $story->substitute_subdef('preview', $media, self::$DI['app']);
            $story->substitute_subdef('thumbnail', $media, self::$DI['app']);

            self::$fixtureIds['records'][$id] = $story->get_record_id();

            return self::$fixtureIds['records'][$id];
        });

        foreach (range(1, 24) as $i) {
            self::$DI['record_' . $i] = self::$DI->share(function ($DI) use ($i) {
                return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], $DI['record_id_resolver']($i));
            });
        }

        foreach (range(1, 2) as $i) {
            self::$DI['record_story_' . $i] = self::$DI->share(function ($DI) use ($i) {
                return new \record_adapter($DI['app'], self::$fixtureIds['databox']['records'], $DI['story_id_resolver']($i));
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
            $record->generate_subdefs($record->get_databox(), self::$DI['app']);
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
            $record->generate_subdefs($record->get_databox(), self::$DI['app']);
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
            $host = parse_url($app['conf']->get(['main', 'servername']), PHP_URL_HOST);
            $generator->setContext(new RequestContext('', 'GET', $host));

            return $generator;
        }));

        $app['translator'] = $this->createTranslatorMock();

        $app['phraseanet.SE.subscriber'] = $this->getMock('Symfony\Component\EventDispatcher\EventSubscriberInterface');
        $app['phraseanet.SE.subscriber']::staticExpects($this->any())
            ->method('getSubscribedEvents')
            ->will($this->returnValue([]));

        $app['translator'] = $this->createTranslatorMock();

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
     * Inserts two tasks.
     *
     * @return Task[]
     */
    public function insertTwoTasks()
    {
        $task1 = new Task();
        $task1
            ->setName('task 1')
            ->setJobId('Null');

        $task2 = new Task();
        $task2
            ->setName('task 2')
            ->setJobId('Null');

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->persist($task2);
        self::$DI['app']['EM']->flush();

        return [$task1, $task2];
    }

    /**
     * Inserts one basket.
     *
     * @param User_Adapter $user
     *
     * @return Basket
     */
    protected function insertOneBasket(\User_Adapter $user = null)
    {
        $basket = new Basket();
        $basket->setOwner($user ?: self::$DI['user']);
        $basket->setName('test');
        $basket->setDescription('description test');

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        return $basket;
    }

    /**
     * Inserts one feed.
     *
     * @param User_Adapter $user
     * @param string|null  $title
     * @param bool         $public
     *
     * @return Feed
     */
    protected function insertOneFeed(\User_Adapter $user = null , $title = null, $public = false)
    {
        $feed = new Feed();
        $publisher = new FeedPublisher();

        $user = $user ?: self::$DI['user'];

        $publisher->setUsrId($user->get_id());
        $publisher->setIsOwner(true);
        $publisher->setFeed($feed);

        $feed->addPublisher($publisher);
        $feed->setTitle($title ?: "test");
        $feed->setIsPublic($public);
        $feed->setSubtitle("description");

        self::$DI['app']['EM']->persist($feed);
        self::$DI['app']['EM']->persist($publisher);
        self::$DI['app']['EM']->flush();

        return $feed;
    }

    /**
     * Inserts one feed entry.
     *
     * @param User_Adapter $user
     * @param bool         $public
     *
     * @return FeedEntry
     */
    protected function insertOneFeedEntry(\User_Adapter $user = null, $public = false)
    {
        $feed = $this->insertOneFeed($user, null, $public);

        $entry = new FeedEntry();
        $entry->setFeed($feed);
        $entry->setTitle("test");
        $entry->setSubtitle("description");
        $entry->setAuthorName('user');
        $entry->setAuthorEmail('user@email.com');

        $publisher = $feed->getPublisher($user ?: self::$DI['user']);

        if ($publisher !== null) {
            $entry->setPublisher($publisher);
        }

        $feed->addEntry($entry);

        self::$DI['app']['EM']->persist($entry);
        self::$DI['app']['EM']->persist($feed);
        self::$DI['app']['EM']->flush();

        return $entry;
    }

    /**
     * Inserts one feed token.
     *
     * @param Feed         $feed
     * @param User_Adapter $user
     *
     * @return FeedToken
     */
    protected function insertOneFeedToken(Feed $feed, \User_Adapter $user = null)
    {
        $user = $user ?: self::$DI['user'];

        $token = new FeedToken();
        $token->setValue(self::$DI['app']['tokens']->generatePassword(12));
        $token->setFeed($feed);
        $token->setUsrId($user->get_id());

        $feed->addToken($token);

        self::$DI['app']['EM']->persist($token);
        self::$DI['app']['EM']->persist($feed);
        self::$DI['app']['EM']->flush();

        return $token;
    }

    /**
     * Insert one aggregate token.
     *
     * @param User_Adapter $user
     *
     * @return AggregateToken
     */
    protected function insertOneAggregateToken(\User_Adapter $user = null)
    {
        $user = $user ?: self::$DI['user'];

        $token = new AggregateToken();
        $token->setValue(self::$DI['app']['tokens']->generatePassword(12));
        $token->setUsrId($user->get_id());

        self::$DI['app']['EM']->persist($token);
        self::$DI['app']['EM']->flush();

        return $token;
    }

    /**
     * Inserts one feed item.
     *
     * @param User_Adapter   $user
     * @param boolean        $public
     * @param integer        $qty
     * @param record_adapter $record
     *
     * @return FeedItem
     */
    protected function insertOneFeedItem(\User_Adapter $user = null, $public = false, $qty = 1, \record_adapter $record = null)
    {
        $entry = $this->insertOneFeedEntry($user, $public);

        for ($i = 0; $i < $qty; $i++) {
            $item = new FeedItem();
            $item->setEntry($entry);

            if (null === $record) {
                $actual = self::$DI['record_'.($i+1)];
            } else {
                $actual = $record;
            }

            $item->setRecordId($actual->get_record_id());
            $item->setSbasId($actual->get_sbas_id());
            $item->setEntry($entry);
            $entry->addItem($item);

            self::$DI['app']['EM']->persist($item);
        }

        self::$DI['app']['EM']->persist($entry);
        self::$DI['app']['EM']->flush();

        return $item;
    }

    /**
     * Inserts one lazaret file.
     *
     * @param User_Adapter $user
     *
     * @return LazaretFile
     */
    protected function insertOneLazaretFile(\User_Adapter $user = null)
    {
        $user = $user ?: self::$DI['user'];

        $lazaretSession = new LazaretSession();
        $lazaretSession->setUsrId($user->get_id());
        $lazaretSession->setUpdated(new \DateTime('now'));
        $lazaretSession->setCreated(new \DateTime('-1 day'));

        $lazaretFile = new LazaretFile();
        $lazaretFile->setOriginalName('test');
        $lazaretFile->setFilename('test.jpg');
        $lazaretFile->setThumbFilename('thumb_test.jpg');
        $lazaretFile->setBaseId(self::$DI['collection']->get_base_id());
        $lazaretFile->setSession($lazaretSession);
        $lazaretFile->setSha256('3191af52748620e0d0da50a7b8020e118bd8b8a0845120b0bb');
        $lazaretFile->setUuid('7b8ef0e3-dc8f-4b66-9e2f-bd049d175124');
        $lazaretFile->setCreated(new \DateTime('now'));
        $lazaretFile->setUpdated(new \DateTime('-1 day'));

        self::$DI['app']['EM']->persist($lazaretFile);
        self::$DI['app']['EM']->flush();

        return $lazaretFile;

    }

    /**
     * Inserts one user list owner.
     *
     * @param User_Adapter $user
     *
     * @return UsrListOwner
     */
    protected function insertOneUsrListOwner(\User_Adapter $user = null)
    {
        $user = $user ?: self::$DI['user'];

        $owner = new UsrListOwner();
        $owner->setRole(UsrListOwner::ROLE_ADMIN);
        $owner->setUser($user);

        self::$DI['app']['EM']->persist($owner);
        self::$DI['app']['EM']->flush();

        return $owner;
    }

    /**
     * Inserts one user list.
     *
     * @param User_Adapter $user
     *
     * @return UsrListOwner
     */
    protected function insertOneUsrList(\User_Adapter $user = null)
    {
        $owner = $this->insertOneUsrListOwner($user);
        $list = new UsrList();
        $list->setName('new list');
        $list->addOwner($owner);
        $owner->setList($list);

        self::$DI['app']['EM']->persist($list);
        self::$DI['app']['EM']->flush();

        return $list;
    }

    /**
     * Insert one user list entry.
     *
     * @param User_adapter $owner
     * @param User_adapter $user
     *
     * @return UsrListEntry
     */
    protected function insertOneUsrListEntry(\User_adapter $owner, \User_adapter $user)
    {
        $list = $this->insertOneUsrList($owner);

        $entry = new UsrListEntry();
        $entry->setUser($user);
        $entry->setList($list);

        $list->addEntrie($entry);

        self::$DI['app']['EM']->persist($entry);
        self::$DI['app']['EM']->persist($list);
        self::$DI['app']['EM']->flush();

        return $entry;
    }

    /**
     * Inserts five baskets.
     *
     * @return Basket[]
     */
    protected function insertFiveBasket()
    {
        $baskets = [];

        for ($i = 0; $i < 5; $i ++) {
            $basket = new Basket();
            $basket->setName('test ' . $i);
            $basket->setDescription('description');
            $basket->setOwner(self::$DI['user']);

            self::$DI['app']['EM']->persist($basket);
            $baskets[] = $basket;
        }
        self::$DI['app']['EM']->flush();

        return $baskets;
    }

    /**
     * Inserts one basket element.
     *
     * @param User_Adapter   $user
     * @param record_adapter $record
     *
     * @return BasketElement
     */
    protected function insertOneBasketElement(\User_Adapter $user = null, \record_adapter $record = null)
    {
        $element = new BasketElement();
        $element->setRecord($record ?: self::$DI['record_1']);

        $basket = $this->insertOneBasket($user);
        $basket->addElement($element);
        $element->setBasket($basket);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        return $element;
    }

    /**
     * Inserts one validation basket.
     *
     * @param array $parameters
     *
     * @return Basket
     */
    protected function insertOneValidationBasket(array $parameters = [])
    {
        $basketElement = $this->insertOneBasketElement();
        $basket = $basketElement->getBasket();

        $validation = new ValidationSession();
        $validation->setBasket($basket);
        $validation->setInitiator(self::$DI['user']);

        if (isset($parameters['expires']) && $parameters['expires'] instanceof \DateTime) {
            $validation->setExpires($parameters['expires']);
        }

        $basket->setValidation($validation);

        $participant = new ValidationParticipant();
        $participant->setUser(self::$DI['user']);
        $participant->setCanAgree(true);
        $participant->setCanSeeOthers(true);

        $validation->addParticipant($participant);
        $participant->setSession($validation);

        $data = new ValidationData();
        $data->setBasketElement($basketElement);
        $data->setParticipant($participant);
        $basketElement->addValidationData($data);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->persist($validation);
        self::$DI['app']['EM']->persist($participant);
        self::$DI['app']['EM']->persist($data);
        self::$DI['app']['EM']->persist($basketElement);

        self::$DI['app']['EM']->flush();

        return $basket;
    }

    /**
     * - Creates a new basket with current authenticated user as owner.
     * - Creates a new sessionValidation with the newly created basket.
     * - Sets current authenticated user as sessionValidation initiator.
     * - Adds 2 records as elements of the newly created basket.
     * - Adds 2 participants to the newly created sessionValidation.
     *
     * @return Basket
     */
    protected function insertOneBasketEnv()
    {
        $basket = new Basket();
        $basket->setName('test');
        $basket->setDescription('description');
        $basket->setOwner(self::$DI['user']);

        self::$DI['app']['EM']->persist($basket);

        foreach ([self::$DI['record_1'], self::$DI['record_2']] as $record) {
            $basketElement = new BasketElement();
            $basketElement->setRecord($record);
            $basketElement->setBasket($basket);
            $basket->addElement($basketElement);
            self::$DI['app']['EM']->persist($basketElement);
        }

        $validationSession = new ValidationSession();
        $validationSession->setBasket($basket);
        $basket->setValidation($validationSession);
        $expires = new \DateTime();
        $expires->modify('+1 week');
        $validationSession->setExpires($expires);
        $validationSession->setInitiator(self::$DI['user']);

        foreach ([self::$DI['user_alt1'], self::$DI['user_alt2']] as $user) {
            $validationParticipant = new ValidationParticipant();
            $validationParticipant->setUser($user);
            $validationParticipant->setSession($validationSession);
            $validationSession->addParticipant($validationParticipant);
            self::$DI['app']['EM']->persist($validationParticipant);
        }

        self::$DI['app']['EM']->flush();

        return $basket;
    }

    /**
     * Inserts one story.
     *
     * @param User_Adapter   $user
     * @param record_adapter $record
     *
     * @return StoryWZ
     */
    protected function insertOneStory(User_Adapter $user = null, \record_adapter $record = null)
    {
        $story = new StoryWZ();

        $story->setRecord($record ?: self::$DI['record_1']);
        $story->setUser($user ?: self::$DI['user']);

        self::$DI['app']['EM']->persist($story);
        self::$DI['app']['EM']->flush();

        return $story;
    }

    /**
     * Inserts one validation session.
     *
     * @param Basket       $basket
     * @param User_Adapter $user
     *
     * @return ValidationSession
     */
    protected function insertOneValidationSession(Basket $basket = null, \User_Adapter $user = null)
    {
        $validationSession = new ValidationSession();

        $validationSession->setBasket($basket ?: $this->insertOneBasket());

        $expires = new \DateTime();
        $expires->modify('+1 week');
        $validationSession->setExpires($expires);
        $validationSession->setInitiator($user ?: self::$DI['user']);

        self::$DI['app']['EM']->persist($validationSession);
        self::$DI['app']['EM']->flush();

        return $validationSession;
    }

    /**
     * Loads One WZ with one basket, one story and one ValidationSession with one participant.
     */
    protected function insertOneWZ()
    {
        $this->insertOneStory();
        $this->insertOneValidationSession($this->insertOneBasket(), self::$DI['user_alt1']);
    }

    /**
     * Inserts one user.
     *
     * @param string $login
     * @param null   $email
     * @param bool   $admin
     *
     * @return User
     */
    protected function insertOneUser($login, $email = null, $admin = false)
    {
        return self::$DI['app']['manipulator.user']->createUser($login, uniqid('pass'), $email, $admin);
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

    protected static function resetUsersRights(Application $app, \User_Adapter $user)
    {
        switch ($user->get_login()) {
            case 'test_phpunit':
                self::giveRightsToUser($app, $user);
                $app['acl']->get($user)->set_admin(true);
                $app['acl']->get(self::$DI['user'])->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
                break;
            case 'test_phpunit_not_admin':
            case 'test_phpunit_alt2':
            case 'test_phpunit_alt1':
                self::giveRightsToUser(self::$DI['app'], $user);
                $app['acl']->get($user)->set_admin(false);
                $app['acl']->get(self::$DI['user'])->revoke_access_from_bases([self::$DI['collection_no_access']->get_base_id()]);
                $app['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000', '00000000000000000000000000010000');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('User %s not found', $user->get_login()));
        }
    }

    private function createSetOfUserTests(Application $application)
    {
        if (false === $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit')) {
            $user = User_Adapter::create($application, 'test_phpunit', random::generatePassword(), 'noone@example.com', false);
            $usr_id = $user->get_id();
        } else {
            $user = User_Adapter::getInstance($usr_id, $application);
        }
        $user->set_email('valid@phraseanet.com');

        self::$fixtureIds['user']['test_phpunit'] = $usr_id;

        if (false === $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_not_admin')) {
            $user = User_Adapter::create($application, 'test_phpunit_not_admin', random::generatePassword(), 'noone_not_admin@example.com', false);
            $usr_id = $user->get_id();
        } else {
            $user = User_Adapter::getInstance($usr_id, $application);
        }

        self::$fixtureIds['user']['test_phpunit_not_admin'] = $usr_id;

        if (false === $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_alt1')) {
            $user = User_Adapter::create($application, 'test_phpunit_alt1', random::generatePassword(), 'noonealt1@example.com', false);
            $usr_id = $user->get_id();
        }

        self::$fixtureIds['user']['test_phpunit_alt1'] = $usr_id;

        if (false === $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_alt2')) {
            $user = User_Adapter::create($application, 'test_phpunit_alt2', random::generatePassword(), 'noonealt2@example.com', false);
            $usr_id = $user->get_id();
        }

        self::$fixtureIds['user']['test_phpunit_alt2'] = $usr_id;
    }

    /**
     * Gives Bases Rights to User.
     *
     * @param \User_Adapter $user
     */
    public static function giveRightsToUser(Application $app, \User_Adapter $user, $base_ids = null)
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
     * Sets self::$DI['collection'].
     */
    private static function setCollection(Application $application)
    {
        $coll = $collection_no_acces = $collection_no_acces_by_status = $db = null;

        foreach ($application['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                if ($collection_no_acces instanceof collection && !$collection_no_acces_by_status) {
                    $collection_no_acces_by_status = $collection;
                }
                if ($coll instanceof collection && !$collection_no_acces) {
                    $collection_no_acces = $collection;
                }
                if (!$coll) {
                    $coll = $collection;
                }
                if ($coll instanceof collection
                    && $collection_no_acces instanceof collection
                    && $collection_no_acces_by_status instanceof collection) {
                    break 2;
                }
            }
        }

        self::$fixtureIds['databox']['records'] = $coll->get_databox()->get_sbas_id();
        self::$fixtureIds['collection']['coll'] = $coll->get_base_id();

        if (!$collection_no_acces instanceof collection) {
            $collection_no_acces = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOO', self::$DI['user']);
        }
        self::$fixtureIds['collection']['coll_no_access'] = $collection_no_acces->get_base_id();

        if (!$collection_no_acces_by_status instanceof collection) {
            $collection_no_acces_by_status = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOONOACCESBYSTATUS', self::$DI['user']);
        }
        self::$fixtureIds['collection']['coll_no_status'] = $collection_no_acces_by_status->get_base_id();

        return;
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

        return;
    }

    /**
     * Authenticates self::['user'] against application.
     *
     * @param Application $app
     */
    protected function authenticate(Application $app)
    {
        $app['session']->clear();
        $app['session']->set('usr_id', self::$DI['user']->get_id());
        $session = new Session();
        $session->setUsrId(self::$DI['user']->get_id());
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

    public function createRandomMock()
    {
        return $this->getMockBuilder('\random')
            ->setMethods(['generatePassword'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function createAppboxMock()
    {
        return $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

class CsrfTestProvider implements CsrfProviderInterface
{
    public function generateCsrfToken($intention)
    {
        return mt_rand();
    }

    public function isCsrfTokenValid($intention, $token)
    {
        return true;
    }
}
