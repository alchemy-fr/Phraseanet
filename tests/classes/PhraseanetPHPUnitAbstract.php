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
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\Routing\RequestContext;

use Alchemy\Tests\Tools\TranslatorMockTrait;

abstract class PhraseanetPHPUnitAbstract extends WebTestCase
{
    use TranslatorMockTrait;
    /**
     * Define some user agents
     */
    const USER_AGENT_FIREFOX8MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:8.0) Gecko/20100101 Firefox/8.0';
    const USER_AGENT_IE6 = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)';
    const USER_AGENT_IPHONE = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 2_1 like Mac OS X; fr-fr) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5F137 Safari/525.20';

    /**
     *
     * @var \Pimple
     */
    public static $DI;
    protected static $testsTime = [];
    protected static $records;
    public static $recordsInitialized = false;

    /**
     * Tell if tables were updated with new schemas
     * @var boolean
     */
    protected static $updated;

    /**
     * Test start time
     * @var float
     */
    protected static $time_start;
    public $app;
    protected $start;

    /**
     *
     * @var Symfony\Component\HttpKernel\Client
     */
    protected $client;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        if (!self::$updated) {
            self::$time_start = microtime(true);

            self::$DI = new \Pimple();
            self::initializeSqliteDB();

            $application = new Application('test');

            if (!$application['phraseanet.configuration-tester']->isInstalled()) {
                echo "\033[0;31mPhraseanet is not set up\033[0;37m\n";
                exit(1);
            }

            self::updateTablesSchema($application);

            self::createSetOfUserTests($application);

            self::setCollection($application);

            self::generateRecords($application);

            self::$DI['user']->set_email('valid@phraseanet.com');

            self::$updated = true;
        }
    }

    public static function initializeSqliteDB($path = '/tmp/db.sqlite')
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

        if (self::$time_start) {
            self::$time_start = null;
        }
    }

    public function setUp()
    {
        ini_set('memory_limit', '2048M');

        $this->start = $start = microtime(true);

        parent::setUp();

        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;

        $phpunit = $this;

        self::$DI['app'] = self::$DI->share(function ($DI) use ($phpunit) {
            $environment = 'test';
            $app = require __DIR__ . '/../../lib/Alchemy/Phrasea/Application/Root.php';

            $app['form.csrf_provider'] = $app->share(function () {
                return new CsrfTestProvider();
            });

            $app['url_generator'] = $app->share($app->extend('url_generator', function ($generator, $app) {
                $host = parse_url($app['conf']->get(['main', 'servername']), PHP_URL_HOST);
                $generator->setContext(new RequestContext('', 'GET', $host));

                return $generator;
            }));
            $app['translator'] = $this->createTranslatorMock();

            $app['debug'] = true;

            $app['EM'] = $app->share($app->extend('EM', function ($em) use ($phpunit) {
                $phpunit::initializeSqliteDB();

                return $em;
            }));

            $app['browser'] = $app->share($app->extend('browser', function ($browser) {

                $browser->setUserAgent(PhraseanetPHPUnitAbstract::USER_AGENT_FIREFOX8MAC);

                return $browser;
            }));

            $app['notification.deliverer'] = $phpunit->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
                ->disableOriginalConstructor()
                ->getMock();
            $app['notification.deliverer']->expects($phpunit->any())
                ->method('deliver')
                ->will($phpunit->returnCallback(function () use ($phpunit) {
                    $phpunit->fail('Notification deliverer must be mocked');
                }));

            return $app;
        });

        self::$DI['cli'] = self::$DI->share(function ($DI) use ($phpunit) {
            $app = new CLI('cli test', null, 'test');

            $app['form.csrf_provider'] = $app->share(function () {
                return new CsrfTestProvider();
            });

            $app['url_generator'] = $app->share($app->extend('url_generator', function ($generator, $app) {
                $host = parse_url($app['conf']->get(['main', 'servername']), PHP_URL_HOST);
                $generator->setContext(new RequestContext('', 'GET', $host));

                return $generator;
            }));

            $app['debug'] = true;

            $app['EM'] = $app->share($app->extend('EM', function ($em) use ($phpunit) {
                $phpunit::initializeSqliteDb();

                return $em;
            }));

            $app['browser'] = $app->share($app->extend('browser', function ($browser) {

                $browser->setUserAgent(PhraseanetPHPUnitAbstract::USER_AGENT_FIREFOX8MAC);

                return $browser;
            }));

            $app['notification.deliverer'] = $phpunit->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
                ->disableOriginalConstructor()
                ->getMock();
            $app['notification.deliverer']->expects($phpunit->any())
                ->method('deliver')
                ->will($phpunit->returnCallback(function () use ($phpunit) {
                    $phpunit->fail('Notification deliverer must be mocked');
                }));

            return $app;
        });

        self::$DI['client'] = self::$DI->share(function ($DI) {
            return new Client($DI['app'], []);
        });

        self::$DI['user']->purgePreferences();
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

//        $duration = (microtime(true) - $this->start);
//        if ($duration > 0.75) {
//            echo "test in " . get_class($this) . " last " . $duration . "\n";
//        }
        $this->start = null;

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

    /**
     * Updates the sql tables with the current schema.
     */
    private static function updateTablesSchema(Application $application)
    {
        if (!self::$updated) {
            if (file_exists(Setup_Upgrade::get_lock_file())) {
                unlink(Setup_Upgrade::get_lock_file());
            }

            $upgrader = new Setup_Upgrade($application);
            $application['phraseanet.appbox']->forceUpgrade($upgrader, $application);
            unset($upgrader);

            $command = __DIR__ . '/../../bin/developer orm:schema-tool:update --force';

            try {
                $process = new Symfony\Component\Process\Process('php ' . $command);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }

                self::$updated = true;
            } catch (\RuntimeException $e) {
                echo "\033[0;31mUnable to validate ORM schema\033[0;37m\n";
                exit(1);
            }
        }

        set_time_limit(3600);

        return;
    }

    /**
     * Creates a set of users for the test suite.
     *
     * self::$DI['user']
     * self::$DI['user_alt1']
     * self::$DI['user_alt2']
     */
    private static function createSetOfUserTests(Application $application)
    {
        self::$DI['user'] = self::$DI->share(function ($DI) use ($application) {
            $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit');

            if (!$usr_id) {
                $user = User_Adapter::create($application, 'test_phpunit', random::generatePassword(), 'noone@example.com', false);
                $usr_id = $user->get_id();
            }

            $user = User_Adapter::getInstance($usr_id, $application);

            return $user;
        });

        self::$DI['user_notAdmin'] = self::$DI->share(function () use ($application) {
            $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_not_admin');

            if (!$usr_id) {
                $user = User_Adapter::create($application, 'test_phpunit_not_admin', random::generatePassword(), 'noone_not_admin@example.com', false);
                $usr_id = $user->get_id();
            }

            return User_Adapter::getInstance($usr_id, $application);
        });

        self::$DI['user_alt1'] = self::$DI->share(function () use ($application) {
            $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_alt1');

            if (!$usr_id) {
                $user = User_Adapter::create($application, 'test_phpunit_alt1', random::generatePassword(), 'noonealt1@example.com', false);
                $usr_id = $user->get_id();
            }

            return User_Adapter::getInstance($usr_id, $application);
        });

        self::$DI['user_alt2'] = self::$DI->share(function () use ($application) {
            $usr_id = User_Adapter::get_usr_id_from_login($application, 'test_phpunit_alt2');

            if (!$usr_id) {
                $user = User_Adapter::create($application, 'test_phpunit_alt2', random::generatePassword(), 'noonealt2@example.com', false);
                $usr_id = $user->get_id();
            }

            return User_Adapter::getInstance($usr_id, $application);
        });
    }

    /**
     * Gives Bases Rights to User.
     *
     * @param \User_Adapter $user
     */
    public static function giveRightsToUser(Application $app, \User_Adapter $user)
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
            $db = $databox;
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

        if (!$coll instanceof collection) {
            self::fail('Unable to find a collection');
        }

        self::$DI['collection'] = $coll;

        self::$DI['collection_no_access'] = self::$DI->share(function ($DI) use ($application, $databox, $collection_no_acces) {
            if (!$collection_no_acces instanceof collection) {
                $collection_no_acces = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOO', $DI['user']);
            }

            $DI['user'] = $DI->share(
                $DI->extend('user', function ($user, $DI) use ($collection_no_acces) {
                    $DI['app']['acl']->get($user)->revoke_access_from_bases([$collection_no_acces->get_base_id()]);
                    $DI['client'] = new Client($DI['app'], []);

                    return $user;
                })
            );

            $DI['user'];

            return $collection_no_acces;
        });

        self::$DI['collection_no_access_by_status'] = self::$DI->share(function ($DI) use ($application, $databox, $collection_no_acces_by_status) {
            if (!$collection_no_acces_by_status instanceof collection) {
                $collection_no_acces_by_status = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOONOACCESBYSTATUS', $DI['user']);
            }

            $DI['user'] = $DI->share(
                $DI->extend('user', function ($user, $DI) use ($collection_no_acces_by_status) {
                    $DI['app']['acl']->get($user)->set_masks_on_base($collection_no_acces_by_status->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
                    $DI['client'] = new Client($DI['app'], []);

                    return $user;
                })
            );

            $DI['user'];

            return $collection_no_acces_by_status;
        });

        return;
    }

    /**
     * Generates a set of records for the current tests suites.
     */
    private static function generateRecords(Application $app)
    {
        if (self::$recordsInitialized === false) {

            $logger = new \Monolog\Logger('tests');
            $logger->pushHandler(new \Monolog\Handler\NullHandler());
            self::$recordsInitialized = [];

            $resolvePathfile = function ($i) {
                $finder = new Symfony\Component\Finder\Finder();

                $name = $i < 10 ? 'test00' . $i . '.*' : 'test0' . $i . '.*';

                $finder->name($name)->in(__DIR__ . '/../files/');

                foreach ($finder as $file) {
                    return $file;
                }

                throw new Exception(sprintf('File %d not found', $i));
            };

            foreach (range(1, 24) as $i) {
                self::$DI['record_' . $i] = self::$DI->share(function ($DI) use ($logger, $resolvePathfile, $i) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = $i;

                    $file = new File($DI['app'], $DI['app']['mediavorus']->guess($resolvePathfile($i)->getPathname()), $DI['collection']);

                    $record = record_adapter::createFromFile($file, $DI['app']);

                    $record->generate_subdefs($record->get_databox(), $DI['app']);

                    return $record;
                });
            }

            foreach (range(1, 2) as $i) {
                self::$DI['record_story_' . $i] = self::$DI->share(function ($DI) use ($i) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'story_' . $i;

                    return record_adapter::createStory($DI['app'], $DI['collection']);
                });
            }

            self::$DI['record_no_access'] = self::$DI->share(function ($DI) {

                PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access';

                $file = new File($DI['app'], $DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), $DI['collection_no_access']);

                return \record_adapter::createFromFile($file, $DI['app']);
            });

            self::$DI['record_no_access_by_status'] = self::$DI->share(function ($DI) {

                PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access_by_status';

                $file = new File($DI['app'], $DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), $DI['collection_no_access']);

                return \record_adapter::createFromFile($file, $DI['app']);
            });

            self::$DI['user'] = self::$DI->share(
                self::$DI->extend('user', function ($user, $DI) use ($app) {
                    PhraseanetPHPUnitAbstract::giveRightsToUser($app, $user);
                    $app['acl']->get($user)->set_admin(true);

                    return $user;
                })
            );

            self::$DI['user_notAdmin'] = self::$DI->share(
                self::$DI->extend('user_notAdmin', function ($user, $DI) use ($app) {
                    PhraseanetPHPUnitAbstract::giveRightsToUser($app, $user);
                    $app['acl']->get($user)->set_admin(false);

                    return $user;
                })
            );
        }

        return;
    }

    /**
     * Deletes previously created Resources.
     */
    private static function deleteResources()
    {
        $skipped = \PhraseanetPHPUnitListener::getSkipped();

        if ($skipped) {
            echo "\nSkipped test : \n\n";
            foreach ($skipped as $skipped_test) {
                echo $skipped_test . "\n";
            }
            echo "\n";
        }

        \PhraseanetPHPUnitListener::resetSkipped();

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

        $phpunit = $this;

        self::$DI['app']['notification.deliverer']->expects($this->any())
            ->method('deliver')
            ->will($this->returnCallback(function ($email, $receipt) use ($phpunit, &$expectedMails) {
                $phpunit->assertTrue(isset($expectedMails[get_class($email)]));
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
