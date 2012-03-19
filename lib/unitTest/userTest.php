<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */



class userTest extends PhraseanetPHPUnitAbstract
{


  public function testMail()
  {
    $this->assertFalse(User_Adapter::get_usr_id_from_email(null));
    try
    {
      $appbox = appbox::get_instance(\bootstrap::getCore());

      self::$user->set_email(null);

      $this->assertFalse(User_Adapter::get_usr_id_from_email(null));
      self::$user->set_email('');
      $this->assertFalse(User_Adapter::get_usr_id_from_email(null));
      self::$user->set_email('noone@example.com');
      $this->assertEquals(self::$user->get_id(), User_Adapter::get_usr_id_from_email('noone@example.com'));
    }
    catch(Exception $e)
    {
      $this->fail($e->getMessage());
    }
    try
    {

      self::$user->set_email('noonealt1@example.com');
      $this->fail('A user already got this address');
    }
    catch(Exception $e)
    {

    }
    $this->assertFalse(User_Adapter::get_usr_id_from_email(null));
  }
}
