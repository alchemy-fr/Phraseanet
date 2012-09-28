<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class editTest extends PhraseanetPHPUnitAuthenticatedAbstract
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
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
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

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_edit(
                    self::$DI['app'],
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $result = $this->report->buildReport($conf);

            $this->reporttestPage($result);
            if (count($result['result']) > 0)
                $this->reporttestConf($conf);
            if (count($result['result']) > 0)
                $this->reporttestResult($result, $conf);

            $this->ColFilter();
        }

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_edit(
                    self::$DI['app'],
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

    public function ColFilter()
    {
        $ret = $this->report->colFilter('user');
        $this->manyCol($ret);
        $ret = $this->report->colFilter('getter');
        $this->manyCol($ret);
        $ret = $this->report->colFilter('date');
        $this->manyCol($ret);
        $ret = $this->report->colFilter('size');
        $this->manyCol($ret);
    }

    public function manyCol($ret)
    {
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $ret);
        foreach ($ret as $result) {
            $this->assertArrayHasKey('val', $result);
            $this->assertArrayHasKey('value', $result);
        }
    }

    public function reporttestPage($report)
    {
        $this->assertLessThanOrEqual($this->report->getNbRecord(), count($report['result']));

        $nbPage = $this->report->getTotal() / $this->report->getNbRecord();

        if ($this->report->getTotal() > $this->report->getNbRecord())
            $this->assertTrue($report['display_nav']);
        else
            $this->assertFalse($report['display_nav']);

        if ($report['page'] == 1)
            $this->assertFalse($report['previous_page']);
        else
            $this->assertEquals($report['page'] - 1, $report['previous_page']);

        if (intval(ceil($nbPage)) == $report['page'] || intval(ceil($nbPage)) == 0)
            $this->assertFalse($report['next_page']);
        else
            $this->assertEquals($report['page'] + 1, $report['next_page']);
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
            $this->assertArrayHasKey('nombre', $this->report->getDisplay());
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
                $this->assertArrayHasKey('nombre', $row);
            }
        }
    }
}
