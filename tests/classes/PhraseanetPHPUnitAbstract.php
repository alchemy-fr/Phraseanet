<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Doctrine\Common\DataFixtures\Loader;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

abstract class PhraseanetPHPUnitAbstract extends WebTestCase
{
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
    protected static $testsTime = array();
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

            $application = new Application('test');

            if (!$application['phraseanet.configuration-tester']->isInstalled()) {
                echo "\033[0;31mPhraseanet is not set up\033[0;37m\n";
                exit(1);
            }

            self::createSetOfUserTests($application);

            self::updateTablesSchema($application);

            self::setCollection($application);

            self::generateRecords($application);

            self::$DI['user']->set_email('valid@phraseanet.com');

            self::$updated = true;
        }
    }

    public function createApplication()
    {

    }

    /**
     * Delete all ressources created during the test
     */
    public function __destruct()
    {
        self::deleteRessources();

        if (self::$time_start) {
            self::$time_start = null;
        }
    }

    public function setUp()
    {
        $this->start = $start = microtime(true);

        parent::setUp();

        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;

        $phpunit = $this;

        self::$DI['app'] = self::$DI->share(function($DI) use ($phpunit) {
            $environment = 'test';
            $app = require __DIR__ . '/../../lib/Alchemy/Phrasea/Application/Root.php';

            $app['debug'] = true;

            $app['EM'] = $app->share($app->extend('EM', function($em) {
                @unlink('/tmp/db.sqlite');
                copy(__DIR__ . '/../db-ref.sqlite', '/tmp/db.sqlite');

                return $em;
            }));


            $app['browser'] = $app->share($app->extend('browser', function($browser) {

                $browser->setUserAgent(PhraseanetPHPUnitAbstract::USER_AGENT_FIREFOX8MAC);
                return $browser;
            }));

            $app['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
                ->disableOriginalConstructor()
                ->getMock();
            $app['notification.deliverer']->expects($this->any())
                ->method('deliver')
                ->will($this->returnCallback(function() use ($phpunit){
                    $phpunit->fail('Notification deliverer must be mocked');
                }));

            return $app;
        });


        self::$DI['client'] = self::$DI->share(function($DI) {
            return new Client($DI['app'], array());
        });

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
        $this->assertTrue(false !== stripos($response->getContent(), 'forbidden'));
    }

    protected function assertBadResponse(Response $response)
    {
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'bad request'));
    }

    protected function assertNotFoundResponse(Response $response)
    {
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'not found'));
    }


    /**
     * Insert fixture contained in the specified fixtureLoader
     * into sqlLite test temporary database
     *
     * @param Doctrine\Common\DataFixtures\Loader $fixtureLoader
     */
    public function insertFixtureInDatabase(Doctrine\Common\DataFixtures\Loader $fixtureLoader, $append = true)
    {
        $purger = new Doctrine\Common\DataFixtures\Purger\ORMPurger();
        $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$DI['app']['EM'], $purger);
        $executor->execute($fixtureLoader->getFixtures(), $append);
        self::$DI['client'] = self::$DI->share(function($DI) {
            return new Client($DI['app'], array());
        });
    }

    /**
     * Purge sqlLite test temporary database by truncate all existing tables
     */
    protected static function purgeDatabase()
    {
        $purger = new Doctrine\Common\DataFixtures\Purger\ORMPurger();
        $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$DI['app']['EM'], $purger);
        $executor->execute(array());
        self::$DI['app']["phraseanet.cache-service"]->flushAll();
    }

    protected function assertDateAtom($date)
    {
        return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
    }

    protected function set_user_agent($user_agent, Alchemy\Phrasea\Application $app)
    {
        $app['browser']->setUserAgent($user_agent);
        $app->register(new \Silex\Provider\TwigServiceProvider());
        $app->setupTwig();
        self::$DI['client'] = self::$DI->share(function($DI) use($app) {
            return new Client($app, array());
        });
    }

    /**
     * Insert one basket entry ans set current authenticated user as owner
     *
     * @return \Entities\Basket
     */
    protected function insertOneBasket()
    {
        try {
            $basketFixture = new PhraseaFixture\Basket\LoadOneBasket();

            $basketFixture->setUser(self::$DI['user']);

            $loader = new Loader();
            $loader->addFixture($basketFixture);

            $this->insertFixtureInDatabase($loader);

            return $basketFixture->basket;
        } catch (\Exception $e) {
            $this->fail('Fail load one Basket : ' . $e->getMessage());
        }
    }

    /**
     * Insert one basket entry ans set current authenticated user as owner
     *
     * @return \Entities\Basket
     */
    protected function insertOneLazaretFile()
    {
        try {
            $lazaretFixture = new PhraseaFixture\Lazaret\LoadOneFile();

            $lazaretFixture->setUser(self::$DI['user']);
            $lazaretFixture->setCollectionId(self::$DI['collection']->get_base_id());

            $loader = new Loader();
            $loader->addFixture($lazaretFixture);

            $this->insertFixtureInDatabase($loader);

            return $lazaretFixture->file;
        } catch (\Exception $e) {
            $this->fail('Fail load one Basket : ' . $e->getMessage());
        }
    }

    protected function insertOneUsrList(\User_Adapter $user)
    {
        try {
            $loader = new Loader();

            $UsrOwner = new PhraseaFixture\UsrLists\UsrListOwner();
            $UsrOwner->setUser($user);

            $loader->addFixture($UsrOwner);

            $UsrList = new PhraseaFixture\UsrLists\UsrList();

            $loader->addFixture($UsrList);


            $this->insertFixtureInDatabase($loader);

            return $UsrList->list;
        } catch (\Exception $e) {
            $this->fail('Fail load one UsrList : ' . $e->getMessage());
        }
    }

    /**
     *
     * @param \Entities\UsrList $UsrList
     * @return \Entities\UsrListEntry
     */
    protected function insertOneUsrListEntry(\User_adapter $owner, \User_adapter $user)
    {
        try {
            $loader = new Loader();

            $UsrOwner = new PhraseaFixture\UsrLists\UsrListOwner();
            $UsrOwner->setUser($owner);

            $loader->addFixture($UsrOwner);

            $UsrList = new PhraseaFixture\UsrLists\UsrList();

            $loader->addFixture($UsrList);

            $UsrEntry = new PhraseaFixture\UsrLists\UsrListEntry();

            $UsrEntry->setUser($user);

            $loader->addFixture($UsrEntry);


            $this->insertFixtureInDatabase($loader);

            return $UsrEntry->entry;
        } catch (\Exception $e) {
            $this->fail('Fail load one UsrListEntry : ' . $e->getMessage());
        }
    }

    /**
     * Insert five baskets and set current authenticated user as owner
     *
     * @return \Entities\Basket
     */
    protected function insertFiveBasket()
    {
        try {
            $basketFixture = new PhraseaFixture\Basket\LoadFiveBaskets();

            $basketFixture->setUser(self::$DI['user']);

            $loader = new Loader();
            $loader->addFixture($basketFixture);

            $this->insertFixtureInDatabase($loader);

            return $basketFixture->baskets;
        } catch (\Exception $e) {
            $this->fail('Fail load five Basket : ' . $e->getMessage());
        }
    }

    /**
     *
     * @return \Entities\BasketElement
     */
    protected function insertOneBasketElement()
    {
        $basket = $this->insertOneBasket();

        $basketElement = new \Entities\BasketElement();
        $basketElement->setRecord(self::$DI['record_1']);
        $basketElement->setBasket($basket);

        $basket->addBasketElement($basketElement);

        $em = self::$DI['app']['EM'];

        $em->persist($basketElement);

        $em->merge($basket);

        $em->flush();

        return $basketElement;
    }

    /**
     *
     * @return \Entities\Basket
     */
    protected function insertOneValidationBasket(array $parameters = array())
    {
        $em = self::$DI['app']['EM'];

        $basketElement = $this->insertOneBasketElement();
        $basket = $basketElement->getBasket();

        $Validation = new Entities\ValidationSession();
        $Validation->setBasket($basket);
        $Validation->setInitiator(self::$DI['user']);

        if (isset($parameters['expires']) && $parameters['expires'] instanceof \DateTime) {
            $Validation->setExpires($parameters['expires']);
        }

        $basket->setValidation($Validation);
        $em->persist($Validation);
        $em->merge($basket);

        $Participant = new Entities\ValidationParticipant();
        $Participant->setUser(self::$DI['user']);
        $Participant->setCanAgree(true);
        $Participant->setCanSeeOthers(true);

        $Validation->addValidationParticipant($Participant);
        $Participant->setSession($Validation);

        $em->persist($Participant);
        $em->merge($Validation);

        $Data = new Entities\ValidationData();
        $Data->setBasketElement($basketElement);
        $Data->setParticipant($Participant);
        $basketElement->addValidationData($Data);

        $em->persist($Data);
        $em->merge($basketElement);

        $em->flush();

        return $basket;
    }

    /**
     * Create a new basket with current auhtenticated user as owner
     * Create a new sessionValidation with the newly created basket
     * Set current authenticated user as sessionValidation initiator
     * Add 2 records as elments of the newly created basket
     * Add 2 participants to the newly created sessionValidation
     *
     * @return \Entities\Basket
     */
    protected function insertOneBasketEnv()
    {
        try {
            $basketFixture = new PhraseaFixture\Basket\LoadOneBasketEnv();

            $basketFixture->setUser(self::$DI['user']);

            $basketFixture->addParticipant(self::$DI['user_alt1']);
            $basketFixture->addParticipant(self::$DI['user_alt2']);

            $basketFixture->addBasketElement(self::$DI['record_1']);
            $basketFixture->addBasketElement(self::$DI['record_2']);

            $loader = new Loader();
            $loader->addFixture($basketFixture);

            $this->insertFixtureInDatabase($loader);

            return $basketFixture->basket;
        } catch (\Exception $e) {
            $this->fail('Fail load one Basket context : ' . $e->getMessage());
        }
    }

    /**
     * Load One WZ with
     * One basket
     * One story
     * One ValidationSession & one participant
     * @return
     */
    protected function insertOneWZ()
    {
        try {
            $currentUser = self::$DI['user'];
            $altUser = self::$DI['user_alt1'];

            // add one basket
            $basket = new PhraseaFixture\Basket\LoadOneBasket();
            $basket->setUser($currentUser);

            //add one story
            $story = new PhraseaFixture\Story\LoadOneStory();
            $story->setUser($currentUser);
            $story->setRecord(self::$DI['record_1']);

            //add a validation session initiated by alt user
            $validationSession = new PhraseaFixture\ValidationSession\LoadOneValidationSession();
            $validationSession->setUser($altUser);

            $loader = new Loader();
            $loader->addFixture($basket);
            $loader->addFixture($story);
            $loader->addFixture($validationSession);

            $this->insertFixtureInDatabase($loader);

            //add current user as participant
            $validationParticipant = new PhraseaFixture\ValidationParticipant\LoadParticipantWithSession();
            $validationParticipant->setSession($validationSession->validationSession);
            $validationParticipant->setUser($currentUser);

            $loader = new Loader();
            $loader->addFixture($validationParticipant);
            $this->insertFixtureInDatabase($loader);
        } catch (\Exception $e) {
            $this->fail('Fail load one WorkingZone : ' . $e->getMessage());
        }

        return;
    }

    /**
     * Calls a URI as XMLHTTP request.
     *
     * @param string  $method        The request method
     * @param string  $uri           The URI to fetch
     * @param array   $parameters    The Request parameters
     * @param array   $httpAccept    Contents of the Accept header
     *
     * @return Crawler
     */
    protected function XMLHTTPRequest($method, $uri, array $parameters = array(), $httpAccept = 'application/json')
    {
        return self::$DI['client']->request($method, $uri, $parameters, array(), array(
            'HTTP_ACCEPT'           => $httpAccept,
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
    }

    /**
     * Update the sql tables with the current schema
     * @return void
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

            $command = __DIR__ . '/../../bin/doctrine orm:schema-tool:update --force';

            try {
                $process = new Symfony\Component\Process\Process('php ' . $command);
                $process->run();
            } catch (Symfony\Component\Process\Exception\RuntimeException $e) {
                $this->fail('Unable to validate ORM schema');
            }

            self::$updated = true;
        }

        set_time_limit(3600);

        return;
    }

    /**
     * Create a set of users for the test suite
     * self::$DI['user']
     * self::$DI['user_alt1']
     * self::$DI['user_alt2']
     *
     * @return void;
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
     * Give Bases Rights to User
     *
     * @param \User_Adapter $user
     */
    public static function giveRightsToUser(Application $app, \User_Adapter $user)
    {
        $user->ACL()->give_access_to_sbas(array_keys($app['phraseanet.appbox']->get_databoxes()));

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            $rights = array(
                'bas_manage'        => '1'
                , 'bas_modify_struct' => '1'
                , 'bas_modif_th'      => '1'
                , 'bas_chupub'        => '1'
            );

            $user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                $user->ACL()->give_access_to_base(array($base_id));
                $user->ACL()->update_rights_to_base($base_id, array('order_master' => true));

                $rights = array(
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
                );

                $user->ACL()->update_rights_to_base($collection->get_base_id(), $rights);
            }
        }
    }

    /**
     * Set self::$DI['collection']
     * @return void
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

        self::$DI['collection_no_access'] = self::$DI->share(function($DI) use ($application, $databox, $collection_no_acces) {
            if (!$collection_no_acces instanceof collection) {
                $collection_no_acces = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOO', $DI['user']);
            }

            $DI['user'] = $DI->share(
                $DI->extend('user', function ($user, $DI) use ($collection_no_acces) {
                    $user->ACL()->revoke_access_from_bases(array($collection_no_acces->get_base_id()));
                    $DI['client'] = new Client($DI['app'], array());
                    return $user;
                })
            );

            $DI['user'];

            return $collection_no_acces;
        });

        self::$DI['collection_no_access_by_status'] = self::$DI->share(function($DI) use ($application, $databox, $collection_no_acces_by_status) {
            if (!$collection_no_acces_by_status instanceof collection) {
                $collection_no_acces_by_status = collection::create($application, $databox, $application['phraseanet.appbox'], 'BIBOONOACCESBYSTATUS', $DI['user']);
            }

            $DI['user'] = $DI->share(
                $DI->extend('user', function ($user, $DI) use ($collection_no_acces_by_status) {
                    $user->ACL()->set_masks_on_base($collection_no_acces_by_status->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
                    $DI['client'] = new Client($DI['app'], array());
                    return $user;
                })
            );

            $DI['user'];

            return $collection_no_acces_by_status;
        });

        return;
    }

    /**
     * Generate a set of records for the current tests suites
     */
    private static function generateRecords(Application $app)
    {
        if (self::$recordsInitialized === false) {

            $logger = new \Monolog\Logger('tests');
            $logger->pushHandler(new \Monolog\Handler\NullHandler());
            self::$recordsInitialized = array();

            $resolvePathfile = function($i) {
                $finder = new Symfony\Component\Finder\Finder();

                $name = $i < 10 ? 'test00' . $i . '.*' : 'test0' . $i . '.*';

                $finder->name($name)->in(__DIR__ . '/../files/');

                foreach ($finder as $file) {
                    return $file;
                }

                throw new Exception(sprintf('File %d not found', $i));
            };

            foreach (range(1, 24) as $i) {
                self::$DI['record_' . $i] = self::$DI->share(function($DI) use ($logger, $resolvePathfile, $i) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = $i;

                    $file = new File($DI['app'], $DI['app']['mediavorus']->guess($resolvePathfile($i)->getPathname()), $DI['collection']);

                    $record = record_adapter::createFromFile($file, $DI['app']);

                    $record->generate_subdefs($record->get_databox(), $DI['app']);

                    return $record;
                });
            }

            foreach (range(1, 2) as $i) {
                self::$DI['record_story_' . $i] = self::$DI->share(function($DI) use ($i) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'story_' . $i;

                    return record_adapter::createStory($DI['app'], $DI['collection']);
                });
            }

            self::$DI['record_no_access'] = self::$DI->share(function($DI) {

                PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access';

                $file = new File($DI['app'], $DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), $DI['collection_no_access']);

                return \record_adapter::createFromFile($file, $DI['app']);
            });

            self::$DI['record_no_access_by_status'] = self::$DI->share(function($DI) {

                PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access_by_status';

                $file = new File($DI['app'], $DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), $DI['collection_no_access']);

                return \record_adapter::createFromFile($file, $DI['app']);
            });

            self::$DI['user'] = self::$DI->share(
                self::$DI->extend('user', function ($user, $DI) use ($app) {
                    PhraseanetPHPUnitAbstract::giveRightsToUser($app, $user);
                    $user->ACL()->set_admin(true);
                    return $user;
                })
            );

            self::$DI['user_notAdmin'] = self::$DI->share(
                self::$DI->extend('user_notAdmin', function ($user, $DI) use ($app) {
                    PhraseanetPHPUnitAbstract::giveRightsToUser($app, $user);
                    $user->ACL()->set_admin(false);
                    return $user;
                })
            );
        }

        return;
    }

    /**
     * Delete previously created Ressources
     *
     * @return void
     */
    private static function deleteRessources()
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

            self::$recordsInitialized = array();
        }

        return;
    }

    protected function authenticate(Application $app)
    {
        $app['session']->clear();
        $app['session']->set('usr_id', self::$DI['user']->get_id());
    }

    protected function logout(Application $app)
    {
        $app['session']->clear();
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
}
