<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . "/../../lib/classes/bootstrap.class.php";

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
bootstrap::execute('test');

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Loader;
use PhraseaFixture\Basket as MyFixture;

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

  /**
   *
   * @var record_adapter
   */
  protected static $story_1;

  /**
   *
   * @var record_adapter
   */
  protected static $record_1;

  /**
   *
   * @var record_adapter
   */
  protected static $record_2;

  /**
   *
   * @var record_adapter
   */
  protected static $record_3;

  /**
   *
   * @var record_adapter
   */
  protected static $record_4;

  /**
   *
   * @var record_adapter
   */
  protected static $record_5;

  /**
   *
   * @var record_adapter
   */
  protected static $record_6;

  /**
   *
   * @var record_adapter
   */
  protected static $record_7;

  /**
   *
   * @var record_adapter
   */
  protected static $record_8;

  /**
   *
   * @var record_adapter
   */
  protected static $record_9;

  /**
   *
   * @var record_adapter
   */
  protected static $record_10;

  /**
   *
   * @var record_adapter
   */
  protected static $record_11;

  /**
   *
   * @var record_adapter
   */
  protected static $record_12;

  /**
   *
   * @var record_adapter
   */
  protected static $record_13;

  /**
   *
   * @var record_adapter
   */
  protected static $record_14;

  /**
   *
   * @var record_adapter
   */
  protected static $record_15;

  /**
   *
   * @var record_adapter
   */
  protected static $record_16;

  /**
   *
   * @var record_adapter
   */
  protected static $record_17;

  /**
   *
   * @var record_adapter
   */
  protected static $record_18;

  /**
   *
   * @var record_adapter
   */
  protected static $record_19;

  /**
   *
   * @var record_adapter
   */
  protected static $record_20;

  /**
   *
   * @var record_adapter
   */
  protected static $record_21;

  /**
   *
   * @var record_adapter
   */
  protected static $record_22;

  /**
   *
   * @var record_adapter
   */
  protected static $record_23;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_1;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_2;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_3;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_4;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_5;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_6;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_7;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_8;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_9;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_10;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_11;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_12;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_13;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_14;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_15;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_16;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_17;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_18;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_19;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_20;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_21;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_22;

  /**
   *
   * @var system_file
   */
  protected static $record_sf_23;

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
   * if yo need record for your test
   * just tell how many 
   * example = $need_records = 2 
   * will create 2 records self::$record_1 & self::$record_2
   * You can use the same mechanism for subdef and stories
   * @var mixed int|boolean
   */
  protected static $need_records = false;
  protected static $need_subdefs = false;
  protected static $need_story = false;

  /**
   *
   * @var collection 
   */
  protected static $collection;

  /**
   * Test start time
   * @var float
   */
  protected static $time_start;

  /**
   *
   * @var Array
   */
  protected static $generated_subdefs = array();

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
    if (!self::$time_start)
    {
      self::$time_start = microtime(true);
      echo "\033[1;35m\n==Start==\033[0;37m\r\n";
    }
    
    self::updateTablesSchema();

    self::createSetOfUserTests();

    self::giveRightsToUser(self::$user);

    self::setCollection();

    self::generateStories();

    self::generateRecords();

    self::generateSubdefs();
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
    if (!setup::is_installed())
    {
      exit("\033[0;31mPhraseanet is not set up\033[0;37m\r\n");
    }

    //init core
    if (null === self::$core)
    {
      self::$core = bootstrap::getCore();
    }
  }

  /**
   * Delete all ressources created during the test
   */
  public function __destruct()
  {
    self::deleteRessources();

    if (self::$time_start)
    {
      $stop = microtime(true);
      echo "\033[1;35m\nTotal time : " . round(($stop - self::$time_start), 2) . "\033[0;37m\r\n";
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
    self::$core = new \Alchemy\Phrasea\Core('test');
    //set Mozilla user agent as default
    $browser = Browser::getInstance();
    $browser->setUserAgent(self::USER_AGENT_FIREFOX8MAC);
  }

  public function tearDown()
  {
    //unset static::$core
    self::$core = null;
    parent::tearDown();
  }

  /**
   * Insert fixture contained in the specified fixtureLoader
   * into sqlLite test temporary database
   * 
   * @param Doctrine\Common\DataFixtures\Loader $fixtureLoader 
   */
  public function insertFixtureInDatabase(Doctrine\Common\DataFixtures\Loader $fixtureLoader)
  {
    $purger = new Doctrine\Common\DataFixtures\Purger\ORMPurger();
    $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$core->getEntityManager(), $purger);
    $executor->execute($fixtureLoader->getFixtures());
  }

  /**
   * Purge sqlLite test temporary database by truncate all existing tables
   */
  protected function purgeDatabase()
  {
    $purger = new Doctrine\Common\DataFixtures\Purger\ORMPurger();
    $executor = new Doctrine\Common\DataFixtures\Executor\ORMExecutor(self::$core->getEntityManager(), $purger);
    $executor->execute(array());
  }

  protected function assertDateAtom($date)
  {
    return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
  }

  protected function set_user_agent($user_agent)
  {
    $browser = Browser::getInstance();
    $browser->setUserAgent($user_agent);
    //reset twg instance
    $this->resetTwig();
  }

  /**
   * Insert one basket entry ans set current authenticated user as owner
   * 
   * @return \Entities\Basket
   */
  protected function insertOneBasket()
  {
    try
    {
      $basketFixture = new MyFixture\LoadOneBasket();

      $basketFixture->setUser(self::$user);

      $loader = new Loader();
      $loader->addFixture($basketFixture);

      $this->insertFixtureInDatabase($loader);

      return $basketFixture->basket;
    }
    catch (\Exception $e)
    {
      $this->fail('Fail load one Basket : ' . $e->getMessage());
    }
  }

  /**
   * Insert five baskets and set current authenticated user as owner
   * 
   * @return \Entities\Basket
   */
  protected function insertFiveBasket()
  {
    try
    {
      $basketFixture = new MyFixture\LoadFiveBaskets();

      $basketFixture->setUser(self::$user);

      $loader = new Loader();
      $loader->addFixture($basketFixture);

      $this->insertFixtureInDatabase($loader);

      return $basketFixture->baskets;
    }
    catch (\Exception $e)
    {
      $this->fail('Fail load one Basket : ' . $e->getMessage());
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
    $basketElement->setRecord(self::$record_1);
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
  protected function insertOneValidationBasket()
  {
    $em = self::$core->getEntityManager();

    $basketElement = $this->insertOneBasketElement();
    $basket = $basketElement->getBasket();

    $Validation = new Entities\ValidationSession();
    $Validation->setBasket($basket);
    $Validation->setInitiator(self::$user);

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
    try
    {
      $em = self::$core->getEntityManager();
      /* @var $em \Doctrine\ORM\EntityManager */

      $basketFixture = new MyFixture\LoadOneBasketEnv();

      $basketFixture->setUser(self::$user);

      $basketFixture->addParticipant(self::$user_alt1);
      $basketFixture->addParticipant(self::$user_alt2);

      $basketFixture->addBasketElement(self::$record_1);
      $basketFixture->addBasketElement(self::$record_2);

      $loader = new Loader();
      $loader->addFixture($basketFixture);

      $this->insertFixtureInDatabase($loader);

      return $basketFixture->basket;
    }
    catch (\Exception $e)
    {
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
    try
    {
      $currentUser = self::$user;
      $altUser = self::$user_alt1;
      //add one basket
      $basket = new PhraseaFixture\Basket\LoadOneBasket();
      $basket->setUser($currentUser);
      //add one story
      $story = new PhraseaFixture\Story\LoadOneStory();
      $story->setUser($currentUser);
      $story->setRecord(self::$record_1);
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
    }
    catch (\Exception $e)
    {
      $this->fail('Fail load one WorkingZone : ' . $e->getMessage());
    }

    return;
  }

  /**
   * Create a new instance of Twig service
   * @return void
   */
  protected function resetTwig()
  {
    $serviceTwig = new \Alchemy\Phrasea\Core\Service\Twig(array());
    $this->app['Core']["Twig"] = $serviceTwig->getTwig();
  }

  /**
   * Update the sql tables with the current schema
   * @return void
   */
  private static function updateTablesSchema()
  {
    $appbox = appbox::get_instance();

    if (!self::$updated)
    {
      echo "\033[1;32m==Upgrading tables==\033[0;37m\r\n";

      if (file_exists(Setup_Upgrade::get_lock_file()))
      {
        unlink(Setup_Upgrade::get_lock_file());
      }

      $upgrader = new Setup_Upgrade($appbox);
      $appbox->forceUpgrade($upgrader);
      unset($upgrader);

      echo "\033[1;32m==Creating doctrine schema==\033[0;37m\r\n";
      if (null !== self::$core)
      {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = self::$core->getEntityManager();

        //get sqlite filePath
        $params = $em->getConnection()->getParams();

        //remove if exists
        if (is_file($params["path"]))
        {
          unlink($params["path"]);
        }

        //create schema
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $metas = $em->getMetadataFactory()->getAllMetadata();
        $tool->createSchema($metas);
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
    $appbox = appbox::get_instance();

    $usr_id = User_Adapter::get_usr_id_from_login('test_phpunit');
    $usr_alt1_id = User_Adapter::get_usr_id_from_login('test_phpunit_alt1');
    $usr_alt2_id = User_Adapter::get_usr_id_from_login('test_phpunit_alt2');

    if (!$usr_id)
    {
      $user = User_Adapter::create($appbox, 'test_phpunit', random::generatePassword(), 'noone@example.com', false);
      $usr_id = $user->get_id();
    }
    if (!$usr_alt1_id)
    {
      $user = User_Adapter::create($appbox, 'test_phpunit_alt1', random::generatePassword(), 'noonealt1@example.com', false);
      $usr_id = $user->get_id();
    }
    if (!$usr_alt2_id)
    {
      $user = User_Adapter::create($appbox, 'test_phpunit_alt2', random::generatePassword(), 'noonealt2@example.com', false);
      $usr_id = $user->get_id();
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
    $appbox = appbox::get_instance();

    $user->ACL()->give_access_to_sbas(array_keys($appbox->get_databoxes()));

    foreach ($appbox->get_databoxes() as $databox)
    {

      $rights = array(
          'bas_manage' => '1'
          , 'bas_modify_struct' => '1'
          , 'bas_modif_th' => '1'
          , 'bas_chupub' => '1'
      );

      $user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

      foreach ($databox->get_collections() as $collection)
      {
        $base_id = $collection->get_base_id();
        $user->ACL()->give_access_to_base(array($base_id));

        $rights = array(
            'canputinalbum' => '1'
            , 'candwnldhd' => '1'
            , 'candwnldsubdef' => '1'
            , 'nowatermark' => '1'
            , 'candwnldpreview' => '1'
            , 'cancmd' => '1'
            , 'canadmin' => '1'
            , 'canreport' => '1'
            , 'canpush' => '1'
            , 'creationdate' => '1'
            , 'canaddrecord' => '1'
            , 'canmodifrecord' => '1'
            , 'candeleterecord' => '1'
            , 'chgstatus' => '1'
            , 'imgtools' => '1'
            , 'manage' => '1'
            , 'modify_struct' => '1'
            , 'manage' => '1'
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
    $appbox = appbox::get_instance();
    $coll = null;

    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $coll = $collection;
        break;
      }

      if ($coll instanceof collection)
      {
        break;
      }
    }

    if (!$coll instanceof collection)
    {
      self::fail('Unable to find a collection');
    }

    self::$collection = $coll;
    return;
  }

  /**
   * Generate a set of stories for the current test suites
   * 
   * @return void
   */
  private static function generateStories()
  {
    if (static::$need_story && !self::$story_1 instanceof record_adapter)
    {
      self::$story_1 = \record_adapter::create(
                      self::$collection
                      , new system_file(__DIR__ . '/testfiles/test001.CR2')
                      , false
                      , true
      );
      echo "generate story 1\n";
    }

    return;
  }

  /**
   * Generate a set subdef according to the records previously created 
   * 
   * @return void 
   */
  private static function generateSubdefs()
  {
    if (static::$need_records && static::$need_subdefs)
    {
      if (self::$record_1 instanceof record_adapter && !isset(self::$generated_subdefs['a1']))
      {
        echo "generate subdefs for record 01\n";
        self::$record_1->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a1'] = true;
      }
      if (self::$record_2 instanceof record_adapter && !isset(self::$generated_subdefs['a2']))
      {
        echo "generate subdefs for record 02\n";
        self::$record_2->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a2'] = true;
      }
      if (self::$record_3 instanceof record_adapter && !isset(self::$generated_subdefs['a3']))
      {
        echo "generate subdefs for record 03\n";
        self::$record_3->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a3'] = true;
      }
      if (self::$record_4 instanceof record_adapter && !isset(self::$generated_subdefs['a4']))
      {
        echo "generate subdefs for record 04\n";
        self::$record_4->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a4'] = true;
      }
      if (self::$record_5 instanceof record_adapter && !isset(self::$generated_subdefs['a5']))
      {
        echo "generate subdefs for record 05\n";
        self::$record_5->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a5'] = true;
      }
      if (self::$record_6 instanceof record_adapter && !isset(self::$generated_subdefs['a6']))
      {
        echo "generate subdefs for record 06\n";
        self::$record_6->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a6'] = true;
      }
      if (self::$record_7 instanceof record_adapter && !isset(self::$generated_subdefs['a7']))
      {
        echo "generate subdefs for record 07\n";
        self::$record_7->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a7'] = true;
      }
      if (self::$record_8 instanceof record_adapter && !isset(self::$generated_subdefs['a8']))
      {
        echo "generate subdefs for record 08\n";
        self::$record_8->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a8'] = true;
      }
      if (self::$record_9 instanceof record_adapter && !isset(self::$generated_subdefs['a9']))
      {
        echo "generate subdefs for record 09\n";
        self::$record_9->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a9'] = true;
      }
      if (self::$record_10 instanceof record_adapter && !isset(self::$generated_subdefs['a10']))
      {
        echo "generate subdefs for record 10\n";
        self::$record_10->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a10'] = true;
      }
      if (self::$record_11 instanceof record_adapter && !isset(self::$generated_subdefs['a11']))
      {
        echo "generate subdefs for record 11\n";
        self::$record_11->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a11'] = true;
      }
      if (self::$record_12 instanceof record_adapter && !isset(self::$generated_subdefs['a12']))
      {
        echo "generate subdefs for record 12\n";
        self::$record_12->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a12'] = true;
      }
      if (self::$record_13 instanceof record_adapter && !isset(self::$generated_subdefs['a13']))
      {
        echo "generate subdefs for record 13\n";
        self::$record_13->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a13'] = true;
      }
      if (self::$record_14 instanceof record_adapter && !isset(self::$generated_subdefs['a14']))
      {
        echo "generate subdefs for record 14\n";
        self::$record_14->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a14'] = true;
      }
      if (self::$record_15 instanceof record_adapter && !isset(self::$generated_subdefs['a15']))
      {
        echo "generate subdefs for record 15\n";
        self::$record_15->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a15'] = true;
      }
      if (self::$record_16 instanceof record_adapter && !isset(self::$generated_subdefs['a16']))
      {
        echo "generate subdefs for record 16\n";
        self::$record_16->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a16'] = true;
      }
      if (self::$record_17 instanceof record_adapter && !isset(self::$generated_subdefs['a17']))
      {
        echo "generate subdefs for record 17\n";
        self::$record_17->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a17'] = true;
      }
      if (self::$record_18 instanceof record_adapter && !isset(self::$generated_subdefs['a18']))
      {
        echo "generate subdefs for record 18\n";
        self::$record_18->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a18'] = true;
      }
      if (self::$record_19 instanceof record_adapter && !isset(self::$generated_subdefs['a19']))
      {
        echo "generate subdefs for record 19\n";
        self::$record_19->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a19'] = true;
      }
      if (self::$record_20 instanceof record_adapter && !isset(self::$generated_subdefs['a20']))
      {
        echo "generate subdefs for record 20\n";
        self::$record_20->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a20'] = true;
      }
      if (self::$record_21 instanceof record_adapter && !isset(self::$generated_subdefs['a21']))
      {
        echo "generate subdefs for record 21\n";
        self::$record_21->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a21'] = true;
      }
      if (self::$record_22 instanceof record_adapter && !isset(self::$generated_subdefs['a22']))
      {
        echo "generate subdefs for record 22\n";
        self::$record_22->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a22'] = true;
      }
      if (self::$record_23 instanceof record_adapter && !isset(self::$generated_subdefs['a23']))
      {
        echo "generate subdefs for record 23\n";
        self::$record_23->generate_subdefs(self::$collection->get_databox());
        self::$generated_subdefs['a23'] = true;
      }
    }
    return;
  }

  /**
   * Generate a set of records for the current tests suites
   * @return void
   */
  private static function generateRecords()
  {
    if (static::$need_records)
    {
      if ((static::$need_records === true || static::$need_records >= 1) && !self::$record_1 instanceof record_adapter)
      {
        echo "generate record 1\n";
        self::$record_sf_1 = new system_file(__DIR__ . '/testfiles/test001.CR2');
        self::$record_1 = record_adapter::create(self::$collection, self::$record_sf_1);
      }
      if ((static::$need_records === true || static::$need_records >= 2) && !self::$record_2 instanceof record_adapter)
      {
        echo "generate record 2\n";
        self::$record_sf_2 = new system_file(__DIR__ . '/testfiles/test002.CR2');
        self::$record_2 = record_adapter::create(self::$collection, self::$record_sf_2);
      }
      if ((static::$need_records === true || static::$need_records >= 3) && !self::$record_3 instanceof record_adapter)
      {
        echo "generate record 3\n";
        self::$record_sf_3 = new system_file(__DIR__ . '/testfiles/test003.CR2');
        self::$record_3 = record_adapter::create(self::$collection, self::$record_sf_3);
      }
      if ((static::$need_records === true || static::$need_records >= 4) && !self::$record_4 instanceof record_adapter)
      {
        echo "generate record 4\n";
        self::$record_sf_4 = new system_file(__DIR__ . '/testfiles/test004.CR2');
        self::$record_4 = record_adapter::create(self::$collection, self::$record_sf_4);
      }
      if ((static::$need_records === true || static::$need_records >= 5) && !self::$record_5 instanceof record_adapter)
      {
        echo "generate record 5\n";
        self::$record_sf_5 = new system_file(__DIR__ . '/testfiles/test005.CR2');
        self::$record_5 = record_adapter::create(self::$collection, self::$record_sf_5);
      }
      if ((static::$need_records === true || static::$need_records >= 6) && !self::$record_6 instanceof record_adapter)
      {
        echo "generate record 6\n";
        self::$record_sf_6 = new system_file(__DIR__ . '/testfiles/test006.wav');
        self::$record_6 = record_adapter::create(self::$collection, self::$record_sf_6);
      }
      if ((static::$need_records === true || static::$need_records >= 7) && !self::$record_7 instanceof record_adapter)
      {
        echo "generate record 7\n";
        self::$record_sf_7 = new system_file(__DIR__ . '/testfiles/test007.ppt');
        self::$record_7 = record_adapter::create(self::$collection, self::$record_sf_7);
      }
      if ((static::$need_records === true || static::$need_records >= 8) && !self::$record_8 instanceof record_adapter)
      {
        echo "generate record 8\n";
        self::$record_sf_8 = new system_file(__DIR__ . '/testfiles/test008.ai');
        self::$record_8 = record_adapter::create(self::$collection, self::$record_sf_8);
      }
      if ((static::$need_records === true || static::$need_records >= 9) && !self::$record_9 instanceof record_adapter)
      {
        echo "generate record 9\n";
        self::$record_sf_9 = new system_file(__DIR__ . '/testfiles/test009.TIFF');
        self::$record_9 = record_adapter::create(self::$collection, self::$record_sf_9);
      }
      if ((static::$need_records === true || static::$need_records >= 10) && !self::$record_10 instanceof record_adapter)
      {
        self::$record_sf_10 = new system_file(__DIR__ . '/testfiles/test010.fla');
        echo "generate record 10\n";
        self::$record_10 = record_adapter::create(self::$collection, self::$record_sf_10);
      }
      if ((static::$need_records === true || static::$need_records >= 11) && !self::$record_11 instanceof record_adapter)
      {
        self::$record_sf_11 = new system_file(__DIR__ . '/testfiles/test011.swf');
        echo "generate record 11\n";
        self::$record_11 = record_adapter::create(self::$collection, self::$record_sf_11);
      }
      if ((static::$need_records === true || static::$need_records >= 12) && !self::$record_12 instanceof record_adapter)
      {
        self::$record_sf_12 = new system_file(__DIR__ . '/testfiles/test012.wav');
        echo "generate record 12\n";
        self::$record_12 = record_adapter::create(self::$collection, self::$record_sf_12);
      }
      if ((static::$need_records === true || static::$need_records >= 13) && !self::$record_13 instanceof record_adapter)
      {
        self::$record_sf_13 = new system_file(__DIR__ . '/testfiles/test013.ai');
        echo "generate record 13\n";
        self::$record_13 = record_adapter::create(self::$collection, self::$record_sf_13);
      }
      if ((static::$need_records === true || static::$need_records >= 14) && !self::$record_14 instanceof record_adapter)
      {
        echo "generate record 14\n";
        self::$record_sf_14 = new system_file(__DIR__ . '/testfiles/test014.swf');
        self::$record_14 = record_adapter::create(self::$collection, self::$record_sf_14);
      }
      if ((static::$need_records === true || static::$need_records >= 15) && !self::$record_15 instanceof record_adapter)
      {
        echo "generate record 15\n";
        self::$record_sf_15 = new system_file(__DIR__ . '/testfiles/test015.eps');
        self::$record_15 = record_adapter::create(self::$collection, self::$record_sf_15);
      }
      if ((static::$need_records === true || static::$need_records >= 16) && !self::$record_16 instanceof record_adapter)
      {
        echo "generate record 16\n";
        self::$record_sf_16 = new system_file(__DIR__ . '/testfiles/test016.ai');
        self::$record_16 = record_adapter::create(self::$collection, self::$record_sf_16);
      }
      if ((static::$need_records === true || static::$need_records >= 17) && !self::$record_17 instanceof record_adapter)
      {
        echo "generate record 17\n";
        self::$record_sf_17 = new system_file(__DIR__ . '/testfiles/test017.wav');
        self::$record_17 = record_adapter::create(self::$collection, self::$record_sf_17);
      }
      if ((static::$need_records === true || static::$need_records >= 18) && !self::$record_18 instanceof record_adapter)
      {
        echo "generate record 18\n";
        self::$record_sf_18 = new system_file(__DIR__ . '/testfiles/test018.TIFF');
        self::$record_18 = record_adapter::create(self::$collection, self::$record_sf_18);
      }
      if ((static::$need_records === true || static::$need_records >= 19) && !self::$record_19 instanceof record_adapter)
      {
        echo "generate record 19\n";
        self::$record_sf_19 = new system_file(__DIR__ . '/testfiles/test019.mp3');
        self::$record_19 = record_adapter::create(self::$collection, self::$record_sf_19);
      }
      if ((static::$need_records === true || static::$need_records >= 20) && !self::$record_20 instanceof record_adapter)
      {
        echo "generate record 20\n";
        self::$record_sf_20 = new system_file(__DIR__ . '/testfiles/test020.mp3');
        self::$record_20 = record_adapter::create(self::$collection, self::$record_sf_20);
      }
      if ((static::$need_records === true || static::$need_records >= 21) && !self::$record_21 instanceof record_adapter)
      {
        echo "generate record 21\n";
        self::$record_sf_21 = new system_file(__DIR__ . '/testfiles/test021.fla');
        self::$record_21 = record_adapter::create(self::$collection, self::$record_sf_21);
      }
      if ((static::$need_records === true || static::$need_records >= 22) && !self::$record_22 instanceof record_adapter)
      {
        echo "generate record 22\n";
        self::$record_sf_22 = new system_file(__DIR__ . '/testfiles/test022.swf');
        self::$record_22 = record_adapter::create(self::$collection, self::$record_sf_22);
      }
      if ((static::$need_records === true || static::$need_records >= 23) && !self::$record_23 instanceof record_adapter)
      {
        echo "generate record 23\n";
        self::$record_sf_23 = new system_file(__DIR__ . '/testfiles/test023.mp4');
        self::$record_23 = record_adapter::create(self::$collection, self::$record_sf_23);
      }
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
    if (self::$story_1 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$story_1->delete();
      self::$story_1 = null;
    }
    if (self::$record_1 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_1->delete();
      self::$record_1 = null;
    }
    if (self::$record_2 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_2->delete();
      self::$record_2 = null;
    }
    if (self::$record_3 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_3->delete();
      self::$record_3 = null;
    }
    if (self::$record_4 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_4->delete();
      self::$record_4 = null;
    }
    if (self::$record_5 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_5->delete();
      self::$record_5 = null;
    }
    if (self::$record_6 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_6->delete();
      self::$record_6 = null;
    }
    if (self::$record_7 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_7->delete();
      self::$record_7 = null;
    }
    if (self::$record_8 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_8->delete();
      self::$record_8 = null;
    }
    if (self::$record_9 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_9->delete();
      self::$record_9 = null;
    }
    if (self::$record_10 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_10->delete();
      self::$record_10 = null;
    }
    if (self::$record_11 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_11->delete();
      self::$record_11 = null;
    }
    if (self::$record_12 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_12->delete();
      self::$record_12 = null;
    }
    if (self::$record_13 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_13->delete();
      self::$record_13 = null;
    }
    if (self::$record_14 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_14->delete();
      self::$record_14 = null;
    }
    if (self::$record_15 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_15->delete();
      self::$record_15 = null;
    }
    if (self::$record_16 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_16->delete();
      self::$record_16 = null;
    }
    if (self::$record_17 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_17->delete();
      self::$record_17 = null;
    }
    if (self::$record_18 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_18->delete();
      self::$record_18 = null;
    }
    if (self::$record_19 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_19->delete();
      self::$record_19 = null;
    }
    if (self::$record_20 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_20->delete();
      self::$record_20 = null;
    }
    if (self::$record_21 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_21->delete();
      self::$record_21 = null;
    }
    if (self::$record_22 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_22->delete();
      self::$record_22 = null;
    }
    if (self::$record_23 instanceof record_adapter)
    {
      echo "\033[0;33m.\033[0;37m";
      self::$record_23->delete();
      self::$record_23 = null;
    }

    return;
  }

}
