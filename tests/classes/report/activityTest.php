<?php

class report_activityTest extends \PhraseanetAuthenticatedTestCase
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
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $this->ret = [];
        foreach ($databoxes as $databox) {
            $colls = $databox->get_collections();
            $rett = [];
            foreach ($colls as $coll) {
                $rett[$coll->get_coll_id()] = $coll->get_coll_id();
            }
            $this->ret[$databox->get_sbas_id()] = implode(',', $rett);
        }
    }

    public function testBuildReport()
    {
        $conf = [
            'user' => ["", 1, 0, 1, 1],
            'date' => ["", 1, 0, 1, 1],
            'record_id' => ["", 1, 1, 1, 1],
            'file' => ["", 1, 0, 1, 1],
            'mime' => ["", 1, 0, 1, 1],
            'size' => ["", 1, 0, 1, 1]
        ];

        foreach ($this->ret as $sbasid => $colllist) {
            $report = new module_report_activity(
                    self::$DI['app'],
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $colllist
            );
            $report->setUser_id(self::$DI['user']->getId());
            $this->activerPerHours($report);
            $this->ConnexionBase($report);
            $this->activiteAddedDocument($report, $sbasid, $colllist);
            $this->activiteAddedTopTenUser($report, $sbasid, $colllist);
            $this->activiteEditedDocument($report, $sbasid, $colllist);
            $this->activiteTopTenSiteView($report, $sbasid, $colllist);
            $this->activity($report, $sbasid, $colllist);
            $this->activityDay($report, $sbasid, $colllist);
            $this->activityQuestion($report, $sbasid, $colllist);
            $this->detailDownload($report);
            $this->downloadByBaseByDay($report);
            $this->otherTest($report);
            $this->topQuestion($report);
            $this->topTenUser($report, $sbasid, $colllist);
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

<<<<<<< HEAD
    public function allQuestion($report)
    {
        $allQuestion = $report->getAllQuestionByUser(self::$DI['user']->getId(), 'usrid');
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $allQuestion);
    }

=======
>>>>>>> 3.8
    public function topQuestion($report)
    {
        $topQuestion = $report->getTopQuestion();
        $topQuestion2 = $report->getTopQuestion(false, true);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $topQuestion);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $topQuestion2);
    }

<<<<<<< HEAD
    public function allDownloadByUserBase($report)
    {
        $allDownload = $report->getAllDownloadByUserBase(self::$DI['user']->getId());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $allDownload);
    }

=======
>>>>>>> 3.8
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

    public function topTenUser($report, $sbasid, $colllist)
    {
        $result = $report->topTenUser(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activity($report, $sbasid, $colllist)
    {
        $result = $report->activity(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activityDay($report, $sbasid, $colllist)
    {
        $result = $report->activityDay(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activityQuestion($report, $sbasid, $colllist)
    {
        $result = $report->activityQuestion(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteTopTenSiteView($report, $sbasid, $colllist)
    {
        $result = $report->activiteTopTenSiteView(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteAddedDocument($report, $sbasid, $colllist)
    {
        $result = $report->activiteAddedDocument(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteEditedDocument($report, $sbasid, $colllist)
    {
        $result = $report->activiteEditedDocument(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }

    public function activiteAddedTopTenUser($report, $sbasid, $colllist)
    {
        $result = $report->activiteAddedTopTenUser(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $colllist);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
    }
}
