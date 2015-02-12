<?php

class report_downloadReportTest extends \report_abstractReportTestCase
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

    public function ColFilter()
    {
        $ret = $this->report->colFilter('user');
        $this->manyCol($ret);
        $ret = $this->report->colFilter('ddate');
        $this->manyCol($ret);
        $ret = $this->report->colFilter('coll_id');
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

    public function testBuildReport()
    {
        $conf = [
            'user' => [self::$DI['app']['translator']->trans('report:: utilisateurs'), 1, 1, 1, 1],
            'ddate' => [self::$DI['app']['translator']->trans('report:: date'), 1, 0, 1, 1],
            'record_id' => [self::$DI['app']['translator']->trans('report:: record id'), 1, 1, 1, 1],
            'final' => [self::$DI['app']['translator']->trans('phrseanet:: sous definition'), 1, 0, 1, 1],
            'coll_id' => [self::$DI['app']['translator']->trans('report:: collections'), 1, 0, 1, 1],
            'comment' => [self::$DI['app']['translator']->trans('report:: commentaire'), 1, 0, 0, 0],
            'fonction' => [self::$DI['app']['translator']->trans('report:: fonction'), 1, 1, 1, 1],
            'activite' => [self::$DI['app']['translator']->trans('report:: activite'), 1, 1, 1, 1],
            'pays' => [self::$DI['app']['translator']->trans('report:: pays'), 1, 1, 1, 1],
            'societe' => [self::$DI['app']['translator']->trans('report:: societe'), 1, 1, 1, 1]
        ];

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_download(
                    self::$DI['app'],
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
            $this->report = new module_report_download(
                    self::$DI['app'],
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $this->ColFilter();

            $result = $this->report->buildReport(false, 'fonction');

            $this->reporttestPage($result);
            if (count($result['result']) > 0)
                $this->reporttestConf($conf, 'fonction');
            if (count($result['result']) > 0)
                $this->reporttestResult($result, $conf, 'fonction');
        }

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_download(
                    self::$DI['app'],
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $this->ColFilter();

            $result = $this->report->buildReport(false, 'record_id', 'DOC');
            $this->reporttestPage($result);
            if (count($result['result']) > 0)
                $this->reporttestConf($conf, 'record_id');
            if (count($result['result']) > 0)
                $this->reporttestResult($result, $conf, 'record_id');
        }

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_download(
                    self::$DI['app'],
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );

            $this->ColFilter();

            $result = $this->report->buildReport(false, 'user', 'DOC');
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
        if ($groupby) {
            if ($groupby != 'record_id')
                $this->assertEquals(count($this->report->getDisplay()), 2);
        } else
            $this->assertEquals(count($this->report->getDisplay()), count($conf));

        if (! $groupby) {
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
        } else {

            $this->assertArrayHasKey($groupby, $this->report->getDisplay());
        }
    }

    public function reporttestResult($report, $conf, $groupby = false)
    {
        if (! $groupby) {
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
            }
        }
    }
}
