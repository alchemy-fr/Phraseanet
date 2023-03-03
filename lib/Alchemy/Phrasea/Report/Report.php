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
    // const FORMAT_XLS  = 'format_xls';
    const FORMAT_XLSX = 'format_xlsx';

    private $format = self::FORMAT_CSV;

    /** @var  \databox */
    protected $databox;
    protected $parms;

    public function __construct(\databox $databox, $parms)
    {
        $this->databox = $databox;
        $this->parms = $parms;

        $this->databox->get_connection()->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);
    }

    abstract function getName();

    abstract function getColumnTitles();

    abstract function getKeyName();

    abstract function getAllRows($callback);

    protected function getDatabox()
    {
        return $this->databox;
    }

    public function getContent()
    {
        $ret = [];
        $this->getAllRows(
            function($row) use($ret) {
                $ret[] = $row;
            }
        );

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

    public function render($absoluteDirectoryPath = null)
    {
        switch($this->format) {
            //case self::FORMAT_XLS:
            case self::FORMAT_CSV:
            case self::FORMAT_ODS:
            case self::FORMAT_XLSX:
                $this->renderAsExcel($absoluteDirectoryPath);
                break;
            default:
                // should not happen since format is checked before
                break;
        }
    }

    private function renderAsExcel($absoluteDirectoryPath = null)
    {
        switch($this->format) {
            //case self::FORMAT_XLS:
            //    $excel = new Excel(Excel::FORMAT_XLS);
            //    header('Content-Type: application/vnd.ms-excel');
            //    break;
            case self::FORMAT_XLSX:
                $filename = $this->getName() . ".xlsx";
                $excel = new Excel(Excel::FORMAT_XLSX, $filename);
                break;
            case self::FORMAT_ODS:
                $filename = $this->getName() . ".ods";
                $excel = new Excel(Excel::FORMAT_ODS, $filename);
                break;
            case self::FORMAT_CSV:
            default:
                $filename = $this->getName() . ".csv";
                $excel = new Excel(Excel::FORMAT_CSV, $filename);
                break;
        }

        // override the open to browser by the writer
        if (!empty($absoluteDirectoryPath)) {
            if (!is_dir($absoluteDirectoryPath)) {
                @mkdir($absoluteDirectoryPath, 0777, true);
            }
            $filePath = \p4string::addEndSlash($absoluteDirectoryPath) . $filename;
            @touch($filePath);
            $excel->getWriter()->openToFile($filePath);
        }

        $excel->addRow($this->getColumnTitles());

        $n = 0;
        $this->getAllRows(
            function($row) use($excel, $n) {
                $excel->addRow($row);
                if($n++ % 10000 === 0) {
                    flush();
                }
            }
        );

        $excel->render();
    }

}
