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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class dashboardTest extends PhraseanetPHPUnitAbstract
{

  protected $dashboard;

  public function setUp()
  {
    $this->dashboard = new module_report_dashboard(self::$user);
    $this->dashboard->setDate('-2 month', 'now');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $this->dashboard->legendDay);
    $this->assertNotNull($this->dashboard->dmin);
    $this->assertNotNull($this->dashboard->dmax);
    $this->assertGreaterThanOrEqual(1, count($this->dashboard->authorizedCollection));
    $this->assertEquals($this->dashboard->authorizedCollection, $this->dashboard->authorizedCollection());


    foreach ($this->dashboard->authorizedCollection as $coll)
    {
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $coll);
      $this->assertArrayHasKey('sbas_id', $coll);
      $this->assertArrayHasKey('coll', $coll);
      $this->assertArrayHasKey('name', $coll);
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $coll['sbas_id']);
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $coll['name']);
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $coll['coll']);
    }
  }

  public function testValid()
  {
//    $this->assertFalse($this->dashboard->isValid());
//    $this->dashboard->execute();
//    $this->assertTrue($this->dashboard->isValid());
  }

  public function testExecute()
  {
    $this->dashboard->execute();
    $dashboard = $this->dashboard->getDash();
    $auth = $this->dashboard->authorizedCollection;

    $this->verify($dashboard);
  }

  public function verify($dashboard)
  {
    $date1 = new DateTime($this->dashboard->dmin);
    $date2 = new DateTime($this->dashboard->dmax);
    $interval = $date1->diff($date2);
    $nbDay = $interval->format("%a");
    $int = array('nb_dl', 'nb_conn');
    $top = array('top_ten_user_doc', 'top_ten_user_poiddoc', 'top_dl_document', 'top_ten_question', 'ask', 'top_ten_added');
    $activity = array('activity', 'activity_day', 'activity_added');
    foreach ($dashboard as $key => $dash)
    {
      if (count($dash) == 0)
        continue;

      if (in_array($key, $int))
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $dash);
      elseif (in_array($key, $top))
      {
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $dash);
        $this->assertLessThanOrEqual($this->dashboard->nbtop, count($dash));
        $lastvalue = null;
        foreach ($dash as $value)
        {
          if (is_null($lastvalue))
            $lastvalue = $value['nb'];
          $this->assertLessThanOrEqual($lastvalue, $value['nb']);
          $lastvalue = $value['nb'];
        }
      }
      elseif (in_array($key, $activity))
      {
        if ($key == 'activity')
        {
          $this->assertEquals(24, count($dash));
        }
        else
        {
          if ($key == 'activity_added')
          {

          }
          $this->assertLessThanOrEqual($nbDay, count($dash));
        }
      }
    }
  }

  public function testGetTitleDate()
  {
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->dashboard->getTitleDate('dmax'));
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->dashboard->getTitleDate('dmin'));
    try
    {
      $this->dashboard->getTitleDate('none');
      $this->fail('must throw an axception right here');
    }
    catch (Exception $e)
    {

    }
  }

  public function testGetListeBase()
  {
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->dashboard->getListeBase(' '));
  }

  public function testGroup()
  {
    $this->assertInstanceOf('module_report_dashboard_group', $this->dashboard->group());
  }

}
