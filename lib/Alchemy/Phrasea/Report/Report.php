<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Out\Module\Excel;


abstract class Report
{
    const FORMAT_CSV  = 'format_csv';
    const FORMAT_ODS  = 'format_ods';
    const FORMAT_XLS  = 'format_xls';
    const FORMAT_XLSX = 'format_xlsx';

    private $format = self::FORMAT_CSV;

    /** @var  \databox */
    protected $databox;
    protected $parms;

    // protected $sql = null;
    // protected $sqlParms = null;

    public function __construct(\databox $databox, $parms)
    {
        $this->databox = $databox;
        $this->parms = $parms;
    }

    abstract function getName();

    abstract function getSql();

    abstract function getSqlParms();

    abstract function getColumnTitles();

    abstract function getKeyName();

    public function getRows()
    {
        return new ReportRows(
            $this->databox->get_connection(),
            $this->getSql(),
            $this->getSqlParms(),
            $this->getKeyName()
        );
    }

    public function getContent()
    {
        $ret = [];
        foreach($this->getRows() as $k=>$r) {
            $ret[] = $r;
        }

        return $ret;
    }

    /**
     * get quoted coll id's granted for report, possibly filtered by
     * baseIds : only from this list of bases
     *
     * @param \ACL $acl
     * @param int[]|null $baseIds
     * @return array
     */
    protected function getCollIds(\ACL $acl, $baseIds)
    {
        $ret = [];
        /** @var \collection $collection */
        foreach($acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            if($collection->get_sbas_id() != $this->databox->get_sbas_id()) {
                continue;
            }
            if(!is_null($baseIds) && !in_array($collection->get_base_id(), $baseIds)) {
                continue;
            }
            $ret[] = $this->databox->get_connection()->quote($collection->get_coll_id());
        }

        return $ret;
    }

    public function setFormat($format)
    {
        if(!in_array($format, [
            //self::FORMAT_XLS,
            self::FORMAT_CSV,
            self::FORMAT_ODS,
            self::FORMAT_XLSX,
        ])) {
            throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $format));
        }
        $this->format = $format;

        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function render()
    {
        switch($this->format) {
            //case self::FORMAT_XLS:
            case self::FORMAT_CSV:
            case self::FORMAT_ODS:
            case self::FORMAT_XLSX:
                $this->renderAsExcel();
                break;
            default:
                // should not happen since format is checked before
                break;
        }
    }

    private function renderAsExcel()
    {
        switch($this->format) {
            //case self::FORMAT_XLS:
            //    $excel = new Excel(Excel::FORMAT_XLS);
            //    header('Content-Type: application/vnd.ms-excel');
            //    break;
            case self::FORMAT_XLSX:
                $excel = new Excel(Excel::FORMAT_XLSX, $this->getName() . ".xlsx");
                break;
            case self::FORMAT_ODS:
                $excel = new Excel(Excel::FORMAT_ODS, $this->getName() . ".ods");
                break;
            case self::FORMAT_CSV:
            default:
                $excel = new Excel(Excel::FORMAT_CSV, $this->getName() . ".csv");
                break;
        }

        $excel->addRow($this->getColumnTitles());

        foreach($this->getRows() as $k=>$row) {
            $excel->addRow($row);
        }

        $excel ->render();
    }

}
