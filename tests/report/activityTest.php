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

class activityTest extends PhraseanetPHPUnitAbstract
{
  protected $ret;
  protected $dmin;
  protected $dmax;
  /**
   *
   * @var module_report_activity
   */
  protected $report;

  public function setUp()
  {
    $date = new Datetime();
    $this->dmax = $date->format("Y-m-d H:i:s");
    $date->modify('-6 month');
    $this->dmin = $date->format("Y-m-d H:i:s");
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databoxes = $appbox->get_databoxes();
    $this->ret = array();
    foreach ($databoxes as $databox)
    {
      $colls = $databox->get_collections();
      $rett = array();
      foreach ($colls as $coll)
      {
        $rett[$coll->get_coll_id()] = $coll->get_coll_id();
      }
      $this->ret[$databox->get_sbas_id()] = implode(',', $rett);
    }
  }

  public function testBuildReport()
  {
    $conf = array(
        'user' => array("", 1, 0, 1, 1),
        'date' => array("", 1, 0, 1, 1),
        'record_id' => array("", 1, 1, 1, 1),
        'file' => array("", 1, 0, 1, 1),
        'mime' => array("", 1, 0, 1, 1),
        'size' => array("", 1, 0, 1, 1)
    );

    foreach ($this->ret as $sbasid => $colllist)
    {
      $report = new module_report_activity(
                      $this->dmin,
                      $this->dmax,
                      $sbasid,
                      $colllist
      );
      $report->setUser_id(self::$user->get_id());
      $this->activerPerHours($report);
      $this->ConnexionBase($report);
      $this->activiteAddedDocument($report, $sbasid, $colllist);
      $this->activiteAddedTopTenUser($report, $sbasid, $colllist);
      $this->activiteEditedDocument($report, $sbasid, $colllist);
      $this->activiteTopTenSiteView($report, $sbasid, $colllist);
      $this->activity($report, $sbasid, $colllist);
      $this->activityDay($report, $sbasid, $colllist);
      $this->activityQuestion($report, $sbasid, $colllist);
      $this->allDownloadByUserBase($report);
      $this->allQuestion($report);
      $this->detailDownload($report);
      $this->downloadByBaseByDay($report);
      $this->otherTest($report);
      $this->push($report);
      $this->topQuestion($report);
      $this->topTenUser($report, $sbasid, $colllist);
    }
  }


  public function otherTest($report)
  {
    $report->setTop(15);
    $this->assertEquals(15, $report->getTop());
  }

  public function  activerPerHours($report)
  {
    $activityHours = $report->getActivityPerHours();
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $activityHours);
  }

  public function allQuestion($report)
  {
    $allQuestion = $report->getAllQuestionByUser(self::$user->get_id(), 'usrid');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $allQuestion);
  }


  public function topQuestion($report)
  {
    $topQuestion = $report->getTopQuestion();
    $topQuestion2 = $report->getTopQuestion(false, true);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $topQuestion);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $topQuestion2);
  }

  public function allDownloadByUserBase($report)
  {
    $allDownload = $report->getAllDownloadByUserBase(self::$user->get_id());
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $allDownload);
  }

  public function downloadByBaseByDay($report)
  {
    $dlBaseDay = $report->getDownloadByBaseByDay();
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $dlBaseDay);
  }

  public function ConnexionBase($report)
  {
    $connexionBase = $report->getConnexionBase();
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $connexionBase);
  }

  public function detailDownload($report)
  {
    $detailDl = $report->getDetailDownload();
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $detailDl);
  }

  public function push($report)
  {
    $push = $report->getPush();
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $push);
  }

  public function topTenUser($report, $sbasid, $colllist)
  {
    $result = $report->topTenUser($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activity($report,  $sbasid, $colllist)
  {
    $result = $report->activity($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activityDay($report,  $sbasid, $colllist)
  {
    $result = $report->activityDay($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activityQuestion($report,  $sbasid, $colllist)
  {
    $result = $report->activityQuestion($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activiteTopTenSiteView($report,  $sbasid, $colllist)
  {
    $result = $report->activiteTopTenSiteView($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activiteAddedDocument($report,  $sbasid, $colllist)
  {
    $result = $report->activiteAddedDocument($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activiteEditedDocument($report,  $sbasid, $colllist)
  {
    $result = $report->activiteEditedDocument($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }

  public function activiteAddedTopTenUser($report,  $sbasid, $colllist)
  {
    $result = $report->activiteAddedTopTenUser($this->dmin, $this->dmax, $sbasid, $colllist);
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
  }


}
