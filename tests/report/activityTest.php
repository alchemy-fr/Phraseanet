<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class activityTest extends PhraseanetPHPUnitAuthenticatedAbstract
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
        parent::setUp();
        $date = new Datetime();
        $this->dmax = $date->format("Y-m-d H:i:s");
        $date->modify('-6 month');
        $this->dmin = $date->format("Y-m-d H:i:s");
        $appbox = self::$application['phraseanet.appbox'];
        $databoxes = $appbox->get_databoxes();
        $this->ret = array();
        foreach ($databoxes as $databox) {
            $colls = $databox->get_collections();
            $rett = array();
            foreach ($colls as $coll) {
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

        foreach ($this->ret as $sbasid => $colllist) {
            $report = new module_report_activity(
                    self::$application,
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $colllist
            );
            $report->setUser_id(self::$user->get_id());
            $this->activerPerHours($report);
            $this->ConnexionBase($report);
            $this->activiteAddedDocument(self::$application, $report, $sbasid, $colllist);
            $this->activiteAddedTopTenUser(self::$application, $report, $sbasid, $colllist);
            $this->activiteEditedDocument(self::$application, $report, $sbasid, $colllist);
            $this->activiteTopTenSiteView(self::$application, $report, $sbasid, $colllist);
            $this->activity(self::$application, $report, $sbasid, $colllist);
            $this->activityDay(self::$application, $report, $sbasid, $colllist);
            $this->activityQuestion(self::$application, $report, $sbasid, $colllist);
            $this->allDownloadByUserBase($report);
            $this->allQuestion($report);
            $this->detailDownload($report);
            $this->downloadByBaseByDay($report);
            $this->otherTest($report);
            $this->push($report);
            $this->topQuestion($report);
            $this->topTenUser(self::$application, $report, $sbasid, $colllist);
        }
    }

    public function otherTest($report)
    {
        $report->setTop(15);
        $this->assertEquals(15, $report->getTop());
    }

    public function activerPerHours($report)
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
        $result = $report->topTenUser(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activity($report, $sbasid, $colllist)
    {
        $result = $report->activity($this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activityDay($report, $sbasid, $colllist)
    {
        $result = $report->activityDay(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activityQuestion($report, $sbasid, $colllist)
    {
        $result = $report->activityQuestion(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteTopTenSiteView($report, $sbasid, $colllist)
    {
        $result = $report->activiteTopTenSiteView(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteAddedDocument($report, $sbasid, $colllist)
    {
        $result = $report->activiteAddedDocument(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteEditedDocument($report, $sbasid, $colllist)
    {
        $result = $report->activiteEditedDocument(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteAddedTopTenUser($report, $sbasid, $colllist)
    {
        $result = $report->activiteAddedTopTenUser(self::$application, $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }
}
