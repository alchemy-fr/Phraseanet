<?php

require_once __DIR__ . "/PhraseanetPHPUnitListener.class.inc";

use Silex\WebTestCase;
use Doctrine\Common\DataFixtures\Loader;

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
     * @var Symfony\Component\HttpKernel\Client
     */
    protected $client;
    protected static $records;
    public static $recordsInitialized = false;

    /**
     *
     * @var User_Adapter
     */
    protected static $user;

    /**
     *
     * @var User_Adapter
     */
    protected static $user_alt1;

    /**
     *
     * @var User_Adapter
     */
    protected static $user_alt2;

    /**
     * Tell if tables were updated with new schemas
     * @var boolean
     */
    protected static $updated;

    /**
     *
     * @var collection
     */
    protected static $collection;

    /**
     *
     * @var collection
     */
    protected static $collection_no_access;
    protected static $collection_no_access_by_status;

    /**
     * Test start time
     * @var float
     */
    protected static $time_start;

    /**
     *
     * @var \Alchemy\Phrasea\Core
     */
    protected static $core;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //launch chrono
        if ( ! self::$time_start) {
            self::$time_start = microtime(true);
        }

        self::updateTablesSchema();

        self::createSetOfUserTests();

        self::giveRightsToUser(self::$user);

        self::setCollection();

        self::generateRecords();
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    /**
     * Delete temporay sqlite database
     * Create schema using $this->classesMetatdas
     * Load DoctrineTestServices
     *
     * @return
     */
    public function __construct()
    {
        //check if app is set up
        if ( ! setup::is_installed()) {
            exit("\033[0;31mPhraseanet is not set up\033[0;37m\r\n");
        }

        //init core
        if (null === self::$core) {
            self::$core = \bootstrap::getCore();
        }
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

    /**
     * Implements abstract method from webtestcase
     */
    public function createApplication()
    {
        return;
    }

    public function setUp()
    {
        parent::setUp();
        //create a new core instance loaded with the test environment for each test
        self::$core = \bootstrap::execute('test');
        //$this->app['EM'] = self::$core->getEntityManager();
        //set Mozilla user agent as default
        $browser = Browser::getInstance();
        $browser->setUserAgent(self::USER_AGENT_FIREFOX8MAC);

        $this->purgeDatabase();

        self::$user->ACL()->revoke_access_from_bases(array(self::$collection_no_access->get_base_id()));
        self::$user->ACL()->set_masks_on_base(self::$collection_no_access_by_status->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');

        \PHPUnit_Framework_Error_Warning::$enabled = true;
        \PHPUnit_Framework_Error_Notice::$enabled = true;
    }

    public function tearDown()
    {
        //unset static::$core
        self::$core = null;

        /**
         * Kris Wallsmith pro-tip
         * @see http://kriswallsmith.net/post/18029585104/faster-phpunit
         */
        $refl = new ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if ( ! $prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_') && 0 !== strpos($prop->getDeclaringClass()->getName(), 'Phraseanet')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
        $refl = null;

        parent::tearDown();
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
        $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$core->getEntityManager(), $purger);
        $executor->execute($fixtureLoader->getFixtures(), $append);
    }

    /**
     * Purge sqlLite test temporary database by truncate all existing tables
     */
    protected function purgeDatabase()
    {
        $purger = new Doctrine\Common\DataFixtures\Purger\ORMPurger();
        $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$core->getEntityManager(), $purger);
        $executor->execute(array());
        self::$core["CacheService"]->flushAll();
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

            $basketFixture->setUser(self::$user);

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

            $lazaretFixture->setUser(self::$user);
            $lazaretFixture->setCollectionId(self::$collection->get_base_id());

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

            $basketFixture->setUser(self::$user);

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
        $basketElement->setRecord(static::$records['record_1']);
        $basketElement->setBasket($basket);

        $basket->addBasketElement($basketElement);

        $em = self::$core->getEntityManager();

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
        $em = self::$core->getEntityManager();

        $basketElement = $this->insertOneBasketElement();
        $basket = $basketElement->getBasket();

        $Validation = new Entities\ValidationSession();
        $Validation->setBasket($basket);
        $Validation->setInitiator(self::$user);

        if (isset($parameters['expires']) && $parameters['expires'] instanceof \DateTime) {
            $Validation->setExpires($parameters['expires']);
        }

        $basket->setValidation($Validation);
        $em->persist($Validation);
        $em->merge($basket);

        $Participant = new Entities\ValidationParticipant();
        $Participant->setUser(self::$user);
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

            $basketFixture->setUser(self::$user);

            $basketFixture->addParticipant(self::$user_alt1);
            $basketFixture->addParticipant(self::$user_alt2);

            $basketFixture->addBasketElement(static::$records['record_1']);
            $basketFixture->addBasketElement(static::$records['record_2']);

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
            $currentUser = self::$user;
            $altUser = self::$user_alt1;
            //add one basket
            $basket = new PhraseaFixture\Basket\LoadOneBasket();
            $basket->setUser($currentUser);
            //add one story
            $story = new PhraseaFixture\Story\LoadOneStory();
            $story->setUser($currentUser);
            $story->setRecord(static::$records['record_1']);
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
     * Update the sql tables with the current schema
     * @return void
     */
    private static function updateTablesSchema()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        if ( ! self::$updated) {

            if (file_exists(Setup_Upgrade::get_lock_file())) {
                unlink(Setup_Upgrade::get_lock_file());
            }

            if (null !== self::$core) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = self::$core->getEntityManager();

                //get sqlite filePath
                $params = $em->getConnection()->getParams();

                //remove if exists
                if (is_file($params["path"])) {
                    unlink($params["path"]);
                }

                //create schema
                $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
                $metas = $em->getMetadataFactory()->getAllMetadata();
                $tool->createSchema($metas);
            }

            $upgrader = new Setup_Upgrade($appbox);
            $appbox->forceUpgrade($upgrader);
            unset($upgrader);

            $command = __DIR__ . '/../bin/doctrine orm:schema-tool:update --force';

            try {
                $process = new Symfony\Component\Process\Process('php ' . $command);
                $process->run();
            } catch (Symfony\Component\Process\Exception\RuntimeException $e) {
                $this->fail('Unable to validate ORM schema');
            }

            $command = __DIR__ . '/../bin/doctrine orm:validate-schema';

            $process = new Symfony\Component\Process\Process('php ' . $command);

            try {
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
     * self::$user
     * self::$user_alt1
     * self::$user_alt2
     *
     * @return void;
     */
    private static function createSetOfUserTests()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $usr_id = User_Adapter::get_usr_id_from_login('test_phpunit');
        $usr_alt1_id = User_Adapter::get_usr_id_from_login('test_phpunit_alt1');
        $usr_alt2_id = User_Adapter::get_usr_id_from_login('test_phpunit_alt2');

        if ( ! $usr_id) {
            $user = User_Adapter::create($appbox, 'test_phpunit', random::generatePassword(), 'noone@example.com', false);
            $usr_id = $user->get_id();
        }
        if ( ! $usr_alt1_id) {
            $user = User_Adapter::create($appbox, 'test_phpunit_alt1', random::generatePassword(), 'noonealt1@example.com', false);
            $usr_alt1_id = $user->get_id();
        }
        if ( ! $usr_alt2_id) {
            $user = User_Adapter::create($appbox, 'test_phpunit_alt2', random::generatePassword(), 'noonealt2@example.com', false);
            $usr_alt2_id = $user->get_id();
        }

        self::$user = User_Adapter::getInstance($usr_id, $appbox);
        self::$user_alt1 = User_Adapter::getInstance($usr_alt2_id, $appbox);
        self::$user_alt2 = User_Adapter::getInstance($usr_alt1_id, $appbox);

        return;
    }

    /**
     * Give Bases Rights to User
     *
     * @param \User_AAdapter $user
     */
    private static function giveRightsToUser(\User_Adapter $user)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $user->ACL()->give_access_to_sbas(array_keys($appbox->get_databoxes()));

        foreach ($appbox->get_databoxes() as $databox) {

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

                set_exportorder::set_order_admins(array($user->get_id()), $base_id);

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
     * Set self::$collection
     * @return void
     */
    private static function setCollection()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $coll = $collection_no_acces = $collection_no_acces_by_status = $db = null;

        foreach ($appbox->get_databoxes() as $databox) {
            $db = $databox;
            foreach ($databox->get_collections() as $collection) {
                if ($collection_no_acces instanceof collection && ! $collection_no_acces_by_status) {
                    $collection_no_acces_by_status = $collection;
                }
                if ($coll instanceof collection && ! $collection_no_acces) {
                    $collection_no_acces = $collection;
                }
                if ( ! $coll) {
                    $coll = $collection;
                }
                if ($coll instanceof collection
                    && $collection_no_acces instanceof collection
                    && $collection_no_acces_by_status instanceof collection) {
                    break 2;
                }
            }
        }

        if ( ! $coll instanceof collection) {
            self::fail('Unable to find a collection');
        }
        if ( ! $collection_no_acces instanceof collection) {
            $collection_no_acces = collection::create($databox, $appbox, 'BIBOO', self::$user);
        }
        if ( ! $collection_no_acces_by_status instanceof collection) {
            $collection_no_acces_by_status = collection::create($databox, $appbox, 'BIBOONOACCESBYSTATUS', self::$user);
        }

        self::$collection = $coll;

        self::$collection_no_access = $collection_no_acces;
        self::$collection_no_access_by_status = $collection_no_acces_by_status;

        return;
    }

    /**
     * Generate a set of records for the current tests suites
     */
    private static function generateRecords()
    {
        $logger = new \Monolog\Logger('tests');
        $logger->pushHandler(new \Monolog\Handler\NullHandler());

        if (self::$recordsInitialized === false) {

            self::$recordsInitialized = array();

            static::$records = new Pimple();

            $collection = self::$collection;

            $resolvePathfile = function($i) {
                    $finder = new Symfony\Component\Finder\Finder();

                    $name = $i < 10 ? 'test00' . $i . '.*' : 'test0' . $i . '.*';

                    $finder->name($name)->in(__DIR__ . '/testfiles/');

                    foreach ($finder as $file) {
                        return $file;
                    }

                    throw new Exception(sprintf('File %d not found', $i));
                };

            $core = self::$core;

            foreach (range(1, 24) as $i) {
                static::$records['record_' . $i] = static::$records->share(function() use ($logger, $collection, $resolvePathfile, $i, $core) {

                        PhraseanetPHPUnitAbstract::$recordsInitialized[] = $i;

                        $file = new Alchemy\Phrasea\Border\File($core['mediavorus']->guess($resolvePathfile($i)), $collection);

                        $record = record_adapter::createFromFile($file);

                        $record->generate_subdefs($record->get_databox(), $logger);

                        return $record;
                    });
            }
            foreach (range(1, 2) as $i) {
                static::$records['record_story_' . $i] = static::$records->share(function() use ($collection, $i) {

                        PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'story_' . $i;

                        return record_adapter::createStory($collection);
                    });
            }

            $collection_no_access = self::$collection_no_access;

            static::$records['record_no_access'] = static::$records->share(function() use ($collection_no_access, $core) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access';

                    $file = new Alchemy\Phrasea\Border\File($core['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/testfiles/cestlafete.jpg')), $collection_no_access);

                    return \record_adapter::createFromFile($file);
                });

            static::$records['record_no_access_by_status'] = static::$records->share(function()use($collection_no_access, $core) {

                    PhraseanetPHPUnitAbstract::$recordsInitialized[] = 'no_access_by_status';

                    $file = new Alchemy\Phrasea\Border\File($core['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/testfiles/cestlafete.jpg')), $collection_no_access);

                    return \record_adapter::createFromFile($file);
                });
        }

        return;
    }

    protected function getMockRecord()
    {
        $record_id = mt_rand(1, 9999999);
        $sbas_id = mt_rand(1, 20);

        $record = $this->getMock('record_adapter', array('get_record_id', 'get_sbas_id', 'get_serialize_key'));

        $record->expects($this->any())
            ->method('get_record_id')
            ->will($this->returnValue($record_id));

        $record->expects($this->any())
            ->method('get_sbas_id')
            ->will($this->returnValue($sbas_id));

        $record->expects($this->any())
            ->method('get_serialize_key')
            ->will($this->returnValue($record_id . '_' . $sbas_id));

        return $record;
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
                static::$records['record_' . $i]->delete();
            }

            self::$recordsInitialized = array();
        }

        return;
    }
}
