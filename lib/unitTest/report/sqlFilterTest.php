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


class sqlFilterTest extends PhraseanetPHPUnitAbstract
{
  /**
   *
   * @var module_report_sqlfilter
   */
  protected $filter;
  protected $report;

  public function setUp()
  {
    $date = new Datetime();
    $dmax = $date->format("Y-m-d H:i:s");
    $date->modify('-6 month');
    $dmin = $date->format("Y-m-d H:i:s");
    $appbox = appbox::get_instance();
    $databoxes = $appbox->get_databoxes();
    $ret = array();
    foreach ($databoxes as $databox)
    {
      $colls = $databox->get_collections();
      $rett = array();
      foreach ($colls as $coll)
      {
        $rett[$coll->get_coll_id()] = $coll->get_coll_id();
      }
      $ret[$databox->get_sbas_id()] = implode(',', $rett);
    }
    foreach ($ret as $sbasid => $collections)
    {
      $report = new module_report_connexion(
                      $dmin,
                      $dmax,
                      $sbasid,
                      $collections
      );
      if(!$this->report instanceof module_report)
      {
        $this->report = $report;
      }
      elseif($report->getTotal() > $this->report->getTotal())
      {
        $this->report = $report;
      }
    }

    $this->report->setFilter(array(array('f' => 'user', 'o' => '=', 'v'=>'admin'), array('f' => 'ddate', 'o' => 'LIKE', 'v'=>'*'), array('f' => '1', 'o' => 'OR', 'v'=>'1')));
    $this->report->setUser_id(self::$user->get_id());
    $this->report->setOrder('user', 'ASC');
    $this->filter = new module_report_sqlfilter($this->report);
  }

  public function checkFilter($filter)
  {
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $filter);
    $this->assertArrayHasKey('params', $filter);
    $this->assertArrayHasKey('sql', $filter);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $filter['params']);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $filter['sql']);
    foreach($filter['params'] as $key => $value)
    {
      $this->assertRegExp('/'.$key.'/', $filter['sql']);
    }
  }

  public function testGvFilter()
  {
    $filter = $this->filter->getGvSitFilter();
    $this->checkFilter($filter);
  }

  public function testUserIDFilter()
  {
    $filter = $this->filter->getUserIdFilter(2);
    $this->assertTrue(in_array(2, $filter['params']));
    $this->checkFilter($filter);
  }

  public function testDateFilter()
  {
    $filter = $this->filter->getDateFilter();
    $this->checkFilter($filter);
  }

  public function testUserFilter()
  {
    $f = $this->report->getTabFilter();

    if (sizeof($f) == 0)
    {
      $this->assertFalse($this->filter->getUserFilter());
    }
    else
    {
      $filter = $this->filter->getUserFilter();
      $this->checkFilter($filter);
    }

  }

  public function testCollectionFilter()
  {
    if($this->report->getUserId() == '')
    {
      $this->assertFalse($this->filter->getCollectionFilter());
    }
    elseif(count(explode(",", $this->report->getListCollId()) > 0))
    {
      $filter = $this->filter->getCollectionFilter();
      $this->checkFilter($filter);
    }
    else
    {
      $this->assertFalse($this->filter->getCollectionFilter());
    }
  }

  public function testRecordFilter()
  {
    if($this->report->getUserId() == '')
    {
      $this->assertFalse($this->filter->getRecordFilter());
    }
    else
    {
      $filter = $this->filter->getRecordFilter();
      $this->checkFilter($filter);
    }
  }

  public function testLimitFilter()
  {
    $p = $this->report->getNbPage();
    $r = $this->report->getNbRecord();

    if ($p && $r)
    {
      $filter = $this->filter->getLimitFilter();
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $filter);
    }
    else
    {
      $this->assertFalse($this->filter->getLimitFilter());
    }

  }

  public function testOrderFilter()
  {
    if (sizeof($this->report->getOrder()) > 0)
    {
      $filter = $this->filter->getOrderFilter();
      $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $filter);
    }
    else
    {
      $this->assertFalse($this->filter->getOrderFilter());
    }
  }

  public function testReportFilter()
  {
    $filter = $this->filter->getReportFilter();
    $this->checkFilter($filter);
  }
}
