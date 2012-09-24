<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class questionReportTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    protected $ret;
    protected $dmin;
    protected $dmax;
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

    public function ColFilter()
    {
        $ret = $this->report->colFilter('user');
        $this->moreFilter($ret);
        $ret = $this->report->colFilter('ddate');
        $this->moreFilter($ret);
    }

    public function moreFilter($ret)
    {
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $ret);
        foreach ($ret as $result) {
            $this->assertArrayHasKey('val', $result);
            $this->assertArrayHasKey('value', $result);
        }
    }

    public function testBuildReport()
    {
        $conf = array(
            'user' => array(_('report:: utilisateur'), 1, 1, 1, 1),
            'search' => array(_('report:: question'), 1, 0, 1, 1),
            'ddate' => array(_('report:: date'), 1, 0, 1, 1),
            'fonction' => array(_('report:: fonction'), 1, 1, 1, 1),
            'activite' => array(_('report:: activite'), 1, 1, 1, 1),
            'pays' => array(_('report:: pays'), 1, 1, 1, 1),
            'societe' => array(_('report:: societe'), 1, 1, 1, 1)
        );

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_question(
                    self::$application,
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $this->ColFilter();

            $result = $this->report->buildReport($conf);
            $this->reporttestPage($result);
            if (count($result['result']) > 0)
                $this->reporttestConf($conf);
            if (count($result['result']) > 0)
                $this->reporttestResult($result, $conf);
        }

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_question(
                    self::$application,
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $this->ColFilter();

            $result = $this->report->buildReport(false, 'user');
            $this->reporttestPage($result);
            if (count($result['result']) > 0)
                $this->reporttestConf($conf, 'user');
            if (count($result['result']) > 0)
                $this->reporttestResult($result, $conf, 'user');
        }
    }

    public function reporttestPage($report)
    {
        $this->assertLessThanOrEqual($this->report->getNbRecord(), count($report['result']));

        $nbPage = ceil($this->report->getTotal() / $this->report->getNbRecord());

        if ($this->report->getTotal() > $this->report->getNbRecord())
            $this->assertTrue($report['display_nav']);
        else
            $this->assertFalse($report['display_nav']);

        if ($report['page'] == 1)
            $this->assertFalse($report['previous_page']);
        else
            $this->assertEquals($report['page'] - 1, $report['previous_page']);

        if (intval($nbPage) > $report['page'])
            $this->assertEquals($report['page'] + 1, $report['next_page']);
        else
            $this->assertFalse($report['next_page']);
    }

    public function reporttestConf($conf, $groupby = false)
    {
        if ($groupby)
            $this->assertEquals(count($this->report->getDisplay()), 2);
        else
            $this->assertEquals(count($this->report->getDisplay()), count($conf));

        if ( ! $groupby) {
            foreach ($this->report->getDisplay() as $col => $colconf) {
                $this->assertArrayHaskey($col, $conf);
                $this->assertTrue(is_array($colconf));
                $this->assertArrayHasKey('title', $colconf);
                $this->assertArrayHasKey('sort', $colconf);
                $this->assertArrayHasKey('bound', $colconf);
                $this->assertArrayHasKey('filter', $colconf);
                $this->assertArrayHasKey('groupby', $colconf);
                $i = 0;
                foreach ($colconf as $key => $value) {
                    if ($i == 1)
                        $this->assertEquals($conf[$col][$i], $value);
                    elseif ($i == 2)
                        $this->assertEquals($conf[$col][$i], $value);
                    elseif ($i == 3)
                        $this->assertEquals($conf[$col][$i], $value);
                    elseif ($i == 4)
                        $this->assertEquals($conf[$col][$i], $value);
                    $i ++;
                }
            }
        }
        else {
            $this->assertArrayHasKey($groupby, $this->report->getDisplay());
            $this->assertArrayHasKey('nb', $this->report->getDisplay());
        }
    }

    public function reporttestResult($report, $conf, $groupby = false)
    {
        if ( ! $groupby) {
            foreach ($report['result'] as $row) {
                foreach ($conf as $key => $value) {

                    $this->assertArrayHasKey($key, $row);
                    $condition = is_string($row[$key]) || is_int($row[$key]);
                    $this->assertTrue($condition);
                }
            }
        } else {
            foreach ($report['result'] as $row) {
                $this->assertArrayHasKey($groupby, $row);
                $this->assertArrayHasKey('nb', $row);
            }
        }
    }
}
