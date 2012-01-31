<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
bootstrap::register_autoloads();

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetPHPUnitAbstract extends WebTestCase
{
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
  protected static $updated;
  protected static $need_records = false;
  protected static $need_subdefs = false;

  /**
   *
   * @var collection
   */
  protected static $collection;
  protected static $time_start;
  protected static $generated_subdefs = array();

  public static function setUpBeforeClass()
  {
    if (!self::$time_start)
      self::$time_start = microtime(true);

    if(!setup::is_installed())
      exit("Phraseanet is not set up\n");

    $appbox = appbox::get_instance();
    if (!self::$updated)
    {
      echo "Upgrading tables ...\n";
      if (file_exists(Setup_Upgrade::get_lock_file()))
        unlink(Setup_Upgrade::get_lock_file());
      $upgrader = new Setup_Upgrade($appbox);
      $appbox->forceUpgrade($upgrader);
      unset($upgrader);
      self::$updated = true;
    }

    set_time_limit(3600);
    parent::setUpBeforeClass();



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

    self::$user->ACL()->give_access_to_sbas(array_keys($appbox->get_databoxes()));

    foreach ($appbox->get_databoxes() as $databox)
    {

      $rights = array(
          'bas_manage' => '1'
          , 'bas_modify_struct' => '1'
          , 'bas_modif_th' => '1'
          , 'bas_chupub' => '1'
      );

      self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

      foreach ($databox->get_collections() as $collection)
      {
        $base_id = $collection->get_base_id();
        self::$user->ACL()->give_access_to_base(array($base_id));

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
            , 'bas_modify_struct' => '1'
        );

        self::$user->ACL()->update_rights_to_base($collection->get_base_id(), $rights);
      }
    }

    $basket_coll = new basketCollection($appbox, self::$user->get_id());
    $baskets = $basket_coll->get_baskets();

    foreach($baskets['baskets'] as $basket)
    {
      $basket->delete();
    }

    basket_adapter::create($appbox, 'TEST', self::$user);

    $appbox->get_session()->authenticate(new Session_Authentication_None(self::$user));

    $coll = null;
    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $coll = $collection;
        break;
      }
      if ($coll instanceof collection)
        break;
    }

    if (!$coll instanceof collection)
      self::fail('Unable to find a collection');

    self::$collection = $coll;

    if (static::$need_records)
    {
      if ((static::$need_records === true || static::$need_records >= 1) && !self::$record_1 instanceof record_adapter)
      {
        echo "generate record 1\n";
        self::$record_sf_1 = new system_file(dirname(__FILE__) . '/testfiles/test001.CR2');
        self::$record_1 = record_adapter::create($coll, self::$record_sf_1);
      }
      if ((static::$need_records === true || static::$need_records >= 2) && !self::$record_2 instanceof record_adapter)
      {
        echo "generate record 2\n";
        self::$record_sf_2 = new system_file(dirname(__FILE__) . '/testfiles/test002.CR2');
        self::$record_2 = record_adapter::create($coll, self::$record_sf_2);
      }
      if ((static::$need_records === true || static::$need_records >= 3) && !self::$record_3 instanceof record_adapter)
      {
        echo "generate record 3\n";
        self::$record_sf_3 = new system_file(dirname(__FILE__) . '/testfiles/test003.CR2');
        self::$record_3 = record_adapter::create($coll, self::$record_sf_3);
      }
      if ((static::$need_records === true || static::$need_records >= 4) && !self::$record_4 instanceof record_adapter)
      {
        echo "generate record 4\n";
        self::$record_sf_4 = new system_file(dirname(__FILE__) . '/testfiles/test004.CR2');
        self::$record_4 = record_adapter::create($coll, self::$record_sf_4);
      }
      if ((static::$need_records === true || static::$need_records >= 5) && !self::$record_5 instanceof record_adapter)
      {
        echo "generate record 5\n";
        self::$record_sf_5 = new system_file(dirname(__FILE__) . '/testfiles/test005.CR2');
        self::$record_5 = record_adapter::create($coll, self::$record_sf_5);
      }
      if ((static::$need_records === true || static::$need_records >= 6) && !self::$record_6 instanceof record_adapter)
      {
        echo "generate record 6\n";
        self::$record_sf_6 = new system_file(dirname(__FILE__) . '/testfiles/test006.wav');
        self::$record_6 = record_adapter::create($coll, self::$record_sf_6);
      }
      if ((static::$need_records === true || static::$need_records >= 7) && !self::$record_7 instanceof record_adapter)
      {
        echo "generate record 7\n";
        self::$record_sf_7 = new system_file(dirname(__FILE__) . '/testfiles/test007.ppt');
        self::$record_7 = record_adapter::create($coll, self::$record_sf_7);
      }
      if ((static::$need_records === true || static::$need_records >= 8) && !self::$record_8 instanceof record_adapter)
      {
        echo "generate record 8\n";
        self::$record_sf_8 = new system_file(dirname(__FILE__) . '/testfiles/test008.ai');
        self::$record_8 = record_adapter::create($coll, self::$record_sf_8);
      }
      if ((static::$need_records === true || static::$need_records >= 9) && !self::$record_9 instanceof record_adapter)
      {
        echo "generate record 9\n";
        self::$record_sf_9 = new system_file(dirname(__FILE__) . '/testfiles/test009.TIFF');
        self::$record_9 = record_adapter::create($coll, self::$record_sf_9);
      }
      if ((static::$need_records === true || static::$need_records >= 10) && !self::$record_10 instanceof record_adapter)
      {
        self::$record_sf_10 = new system_file(dirname(__FILE__) . '/testfiles/test010.fla');
        echo "generate record 10\n";
        self::$record_10 = record_adapter::create($coll, self::$record_sf_10);
      }
      if ((static::$need_records === true || static::$need_records >= 11) && !self::$record_11 instanceof record_adapter)
      {
        self::$record_sf_11 = new system_file(dirname(__FILE__) . '/testfiles/test011.swf');
        echo "generate record 11\n";
        self::$record_11 = record_adapter::create($coll, self::$record_sf_11);
      }
      if ((static::$need_records === true || static::$need_records >= 12) && !self::$record_12 instanceof record_adapter)
      {
        self::$record_sf_12 = new system_file(dirname(__FILE__) . '/testfiles/test012.wav');
        echo "generate record 12\n";
        self::$record_12 = record_adapter::create($coll, self::$record_sf_12);
      }
      if ((static::$need_records === true || static::$need_records >= 13) && !self::$record_13 instanceof record_adapter)
      {
        self::$record_sf_13 = new system_file(dirname(__FILE__) . '/testfiles/test013.ai');
        echo "generate record 13\n";
        self::$record_13 = record_adapter::create($coll, self::$record_sf_13);
      }
      if ((static::$need_records === true || static::$need_records >= 14) && !self::$record_14 instanceof record_adapter)
      {
        echo "generate record 14\n";
        self::$record_sf_14 = new system_file(dirname(__FILE__) . '/testfiles/test014.swf');
        self::$record_14 = record_adapter::create($coll, self::$record_sf_14);
      }
      if ((static::$need_records === true || static::$need_records >= 15) && !self::$record_15 instanceof record_adapter)
      {
        echo "generate record 15\n";
        self::$record_sf_15 = new system_file(dirname(__FILE__) . '/testfiles/test015.eps');
        self::$record_15 = record_adapter::create($coll, self::$record_sf_15);
      }
      if ((static::$need_records === true || static::$need_records >= 16) && !self::$record_16 instanceof record_adapter)
      {
        echo "generate record 16\n";
        self::$record_sf_16 = new system_file(dirname(__FILE__) . '/testfiles/test016.ai');
        self::$record_16 = record_adapter::create($coll, self::$record_sf_16);
      }
      if ((static::$need_records === true || static::$need_records >= 17) && !self::$record_17 instanceof record_adapter)
      {
        echo "generate record 17\n";
        self::$record_sf_17 = new system_file(dirname(__FILE__) . '/testfiles/test017.wav');
        self::$record_17 = record_adapter::create($coll, self::$record_sf_17);
      }
      if ((static::$need_records === true || static::$need_records >= 18) && !self::$record_18 instanceof record_adapter)
      {
        echo "generate record 18\n";
        self::$record_sf_18 = new system_file(dirname(__FILE__) . '/testfiles/test018.TIFF');
        self::$record_18 = record_adapter::create($coll, self::$record_sf_18);
      }
      if ((static::$need_records === true || static::$need_records >= 19) && !self::$record_19 instanceof record_adapter)
      {
        echo "generate record 19\n";
        self::$record_sf_19 = new system_file(dirname(__FILE__) . '/testfiles/test019.mp3');
        self::$record_19 = record_adapter::create($coll, self::$record_sf_19);
      }
      if ((static::$need_records === true || static::$need_records >= 20) && !self::$record_20 instanceof record_adapter)
      {
        echo "generate record 20\n";
        self::$record_sf_20 = new system_file(dirname(__FILE__) . '/testfiles/test020.mp3');
        self::$record_20 = record_adapter::create($coll, self::$record_sf_20);
      }
      if ((static::$need_records === true || static::$need_records >= 21) && !self::$record_21 instanceof record_adapter)
      {
        echo "generate record 21\n";
        self::$record_sf_21 = new system_file(dirname(__FILE__) . '/testfiles/test021.fla');
        self::$record_21 = record_adapter::create($coll, self::$record_sf_21);
      }
      if ((static::$need_records === true || static::$need_records >= 22) && !self::$record_22 instanceof record_adapter)
      {
        echo "generate record 22\n";
        self::$record_sf_22 = new system_file(dirname(__FILE__) . '/testfiles/test022.swf');
        self::$record_22 = record_adapter::create($coll, self::$record_sf_22);
      }
      if ((static::$need_records === true || static::$need_records >= 23) && !self::$record_23 instanceof record_adapter)
      {
        echo "generate record 23\n";
        self::$record_sf_23 = new system_file(dirname(__FILE__) . '/testfiles/test023.mp4');
        self::$record_23 = record_adapter::create($coll, self::$record_sf_23);
      }
    }

    if (static::$need_records && static::$need_subdefs)
    {
      if (self::$record_1 instanceof record_adapter && !isset(self::$generated_subdefs['a1']))
      {
        echo "\ngenerate subdefs for record 01\n";
        self::$record_1->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a1'] = true;
      }
      if (self::$record_2 instanceof record_adapter && !isset(self::$generated_subdefs['a2']))
      {
        echo "\ngenerate subdefs for record 02\n";
        self::$record_2->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a2'] = true;
      }
      if (self::$record_3 instanceof record_adapter && !isset(self::$generated_subdefs['a3']))
      {
        echo "\ngenerate subdefs for record 03\n";
        self::$record_3->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a3'] = true;
      }
      if (self::$record_4 instanceof record_adapter && !isset(self::$generated_subdefs['a4']))
      {
        echo "\ngenerate subdefs for record 04\n";
        self::$record_4->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a4'] = true;
      }
      if (self::$record_5 instanceof record_adapter && !isset(self::$generated_subdefs['a5']))
      {
        echo "\ngenerate subdefs for record 05\n";
        self::$record_5->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a5'] = true;
      }
      if (self::$record_6 instanceof record_adapter && !isset(self::$generated_subdefs['a6']))
      {
        echo "\ngenerate subdefs for record 06\n";
        self::$record_6->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a6'] = true;
      }
      if (self::$record_7 instanceof record_adapter && !isset(self::$generated_subdefs['a7']))
      {
        echo "\ngenerate subdefs for record 07\n";
        self::$record_7->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a7'] = true;
      }
      if (self::$record_8 instanceof record_adapter && !isset(self::$generated_subdefs['a8']))
      {
        echo "\ngenerate subdefs for record 08\n";
        self::$record_8->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a8'] = true;
      }
      if (self::$record_9 instanceof record_adapter && !isset(self::$generated_subdefs['a9']))
      {
        echo "\ngenerate subdefs for record 09\n";
        self::$record_9->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a9'] = true;
      }
      if (self::$record_10 instanceof record_adapter && !isset(self::$generated_subdefs['a10']))
      {
        echo "\ngenerate subdefs for record 10\n";
        self::$record_10->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a10'] = true;
      }
      if (self::$record_11 instanceof record_adapter && !isset(self::$generated_subdefs['a11']))
      {
        echo "\ngenerate subdefs for record 11\n";
        self::$record_11->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a11'] = true;
      }
      if (self::$record_12 instanceof record_adapter && !isset(self::$generated_subdefs['a12']))
      {
        echo "\ngenerate subdefs for record 12\n";
        self::$record_12->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a12'] = true;
      }
      if (self::$record_13 instanceof record_adapter && !isset(self::$generated_subdefs['a13']))
      {
        echo "\ngenerate subdefs for record 13\n";
        self::$record_13->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a13'] = true;
      }
      if (self::$record_14 instanceof record_adapter && !isset(self::$generated_subdefs['a14']))
      {
        echo "\ngenerate subdefs for record 14\n";
        self::$record_14->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a14'] = true;
      }
      if (self::$record_15 instanceof record_adapter && !isset(self::$generated_subdefs['a15']))
      {
        echo "\ngenerate subdefs for record 15\n";
        self::$record_15->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a15'] = true;
      }
      if (self::$record_16 instanceof record_adapter && !isset(self::$generated_subdefs['a16']))
      {
        echo "\ngenerate subdefs for record 16\n";
        self::$record_16->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a16'] = true;
      }
      if (self::$record_17 instanceof record_adapter && !isset(self::$generated_subdefs['a17']))
      {
        echo "\ngenerate subdefs for record 17\n";
        self::$record_17->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a17'] = true;
      }
      if (self::$record_18 instanceof record_adapter && !isset(self::$generated_subdefs['a18']))
      {
        echo "\ngenerate subdefs for record 18\n";
        self::$record_18->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a18'] = true;
      }
      if (self::$record_19 instanceof record_adapter && !isset(self::$generated_subdefs['a19']))
      {
        echo "\ngenerate subdefs for record 19\n";
        self::$record_19->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a19'] = true;
      }
      if (self::$record_20 instanceof record_adapter && !isset(self::$generated_subdefs['a20']))
      {
        echo "\ngenerate subdefs for record 20\n";
        self::$record_20->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a20'] = true;
      }
      if (self::$record_21 instanceof record_adapter && !isset(self::$generated_subdefs['a21']))
      {
        echo "\ngenerate subdefs for record 21\n";
        self::$record_21->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a21'] = true;
      }
      if (self::$record_22 instanceof record_adapter && !isset(self::$generated_subdefs['a22']))
      {
        echo "\ngenerate subdefs for record 22\n";
        self::$record_22->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a22'] = true;
      }
      if (self::$record_23 instanceof record_adapter && !isset(self::$generated_subdefs['a23']))
      {
        echo "\ngenerate subdefs for record 23\n";
        self::$record_23->generate_subdefs($coll->get_databox());
        self::$generated_subdefs['a23'] = true;
      }
    }
    $appbox->get_session()->logout();
  }

  public function __destruct()
  {
    if (self::$record_1 instanceof record_adapter)
    {
      echo ".";
      self::$record_1->delete();
      self::$record_1 = null;
    }
    if (self::$record_2 instanceof record_adapter)
    {
      echo ".";
      self::$record_2->delete();
      self::$record_2 = null;
    }
    if (self::$record_3 instanceof record_adapter)
    {
      echo ".";
      self::$record_3->delete();
      self::$record_3 = null;
    }
    if (self::$record_4 instanceof record_adapter)
    {
      echo ".";
      self::$record_4->delete();
      self::$record_4 = null;
    }
    if (self::$record_5 instanceof record_adapter)
    {
      echo ".";
      self::$record_5->delete();
      self::$record_5 = null;
    }
    if (self::$record_6 instanceof record_adapter)
    {
      echo ".";
      self::$record_6->delete();
      self::$record_6 = null;
    }
    if (self::$record_7 instanceof record_adapter)
    {
      echo ".";
      self::$record_7->delete();
      self::$record_7 = null;
    }
    if (self::$record_8 instanceof record_adapter)
    {
      echo ".";
      self::$record_8->delete();
      self::$record_8 = null;
    }
    if (self::$record_9 instanceof record_adapter)
    {
      echo ".";
      self::$record_9->delete();
      self::$record_9 = null;
    }
    if (self::$record_10 instanceof record_adapter)
    {
      echo ".";
      self::$record_10->delete();
      self::$record_10 = null;
    }
    if (self::$record_11 instanceof record_adapter)
    {
      echo ".";
      self::$record_11->delete();
      self::$record_11 = null;
    }
    if (self::$record_12 instanceof record_adapter)
    {
      echo ".";
      self::$record_12->delete();
      self::$record_12 = null;
    }
    if (self::$record_13 instanceof record_adapter)
    {
      echo ".";
      self::$record_13->delete();
      self::$record_13 = null;
    }
    if (self::$record_14 instanceof record_adapter)
    {
      echo ".";
      self::$record_14->delete();
      self::$record_14 = null;
    }
    if (self::$record_15 instanceof record_adapter)
    {
      echo ".";
      self::$record_15->delete();
      self::$record_15 = null;
    }
    if (self::$record_16 instanceof record_adapter)
    {
      echo ".";
      self::$record_16->delete();
      self::$record_16 = null;
    }
    if (self::$record_17 instanceof record_adapter)
    {
      echo ".";
      self::$record_17->delete();
      self::$record_17 = null;
    }
    if (self::$record_18 instanceof record_adapter)
    {
      echo ".";
      self::$record_18->delete();
      self::$record_18 = null;
    }
    if (self::$record_19 instanceof record_adapter)
    {
      echo ".";
      self::$record_19->delete();
      self::$record_19 = null;
    }
    if (self::$record_20 instanceof record_adapter)
    {
      echo ".";
      self::$record_20->delete();
      self::$record_20 = null;
    }
    if (self::$record_21 instanceof record_adapter)
    {
      echo ".";
      self::$record_21->delete();
      self::$record_21 = null;
    }
    if (self::$record_22 instanceof record_adapter)
    {
      echo ".";
      self::$record_22->delete();
      self::$record_22 = null;
    }
    if (self::$record_23 instanceof record_adapter)
    {
      echo ".";
      self::$record_23->delete();
      self::$record_23 = null;
    }

    if (self::$time_start)
    {
      $stop = microtime(true);
      echo "\ntotal time : " . ($stop - self::$time_start) . "\n";
      self::$time_start = null;
    }
  }

  public function createClient(array $options = array(), array $server = array())
  {
    return;
  }

  public function createApplication()
  {
    return;
  }

  public function setUp()
  {
    parent::setUp();
    $browser = Browser::getInstance();
    $browser->setUserAgent(self::USER_AGENT_FIREFOX8MAC);
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  protected function assertDateAtom($date)
  {
    return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
  }

  protected function set_user_agent($user_agent)
  {
    $browser = Browser::getInstance();
    $browser->setUserAgent($user_agent);
  }

}
