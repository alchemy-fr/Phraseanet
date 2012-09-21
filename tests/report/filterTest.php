<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class filterTest extends PhraseanetPHPUnitAbstract
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
        $this->initFilter();
    }

    public function initFilter()
    {
        $conf = array(
            'user' => array(_('phraseanet::utilisateurs'), 1, 1, 1, 1),
            'ddate' => array(_('report:: date'), 1, 0, 1, 1),
            'ip' => array(_('report:: IP'), 1, 0, 0, 0),
            'appli' => array(_('report:: modules'), 1, 0, 0, 0),
            'fonction' => array(_('report::fonction'), 1, 1, 1, 1),
            'activite' => array(_('report::activite'), 1, 1, 1, 1),
            'pays' => array(_('report::pays'), 1, 1, 1, 1),
            'societe' => array(_('report::societe'), 1, 1, 1, 1)
        );

        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report_connexion(
                    self::$application,
                    $this->dmin,
                    $this->dmax,
                    $sbasid,
                    $collections
            );
            break;
        }
    }

    public function testFilter()
    {
        $filter = new module_report_filter(array(), $this->report->getTransQueryString());
        $this->assertEquals(array(), $filter->getTabFilter());
        $filter->addFilter('x', 'LIKE', 'y');
        $filter->addFilter('x', 'LIKE', 'z');
        $filter->addFilter('1', '=', '1');
        $filter->addFilter('1', '=', '1');
        $tabfilter = $filter->getTabFilter();
        $this->assertEquals(2, count($tabfilter));
        $added_filter = $tabfilter[0];
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $added_filter);
        $this->assertArrayHasKey('f', $added_filter);
        $this->assertArrayHasKey('o', $added_filter);
        $this->assertArrayHasKey('v', $added_filter);
        $this->assertEquals('x', $added_filter['f']);
        $this->assertEquals('LIKE', $added_filter['o']);
        $this->assertEquals('y', $added_filter['v']);
        $active_column = $filter->getActiveColumn();
        $this->assertEquals('x', $active_column[0]);

        $tabfilter = $filter->getTabFilter();
        $this->assertEquals(2, count($tabfilter));
        $filter->addFilter('y', '=', 'z');
        $tabfilter = $filter->getTabFilter();
        $this->assertEquals(3, count($tabfilter));
        $filter->addFilter('user', '=', 'o');
        $tabfilter = $filter->getTabFilter();
        $this->assertEquals(4, count($tabfilter));
        $filter->addFilter('a', 'OR', '');
        $filter->addFilter('appli', '=', 'a:1:{i:0;i:1;}');
        $filter->addFilter('ddate', '=', 'o');
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $filter->getPostingFilter());
        $nbBefore = count($filter->getTabFilter());
        $filter->removeFilter('ddate');
        $nbAfter = count($filter->getTabFilter());
        $this->assertEquals($nbBefore - 1, $nbAfter);
    }
}
