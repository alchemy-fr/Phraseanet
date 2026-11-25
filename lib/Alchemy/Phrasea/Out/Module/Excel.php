<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Out\Module;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\CSV;
use OpenSpout\Writer\ODS;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX;

class Excel
{
    const FORMAT_CSV  = 'format_csv';
    const FORMAT_ODS  = 'format_ods';
    const FORMAT_XLSX = 'format_xlsx';

    private $format;

    private $filename;

    /** @var WriterInterface */
    private $writer;

    private $opened = false;

    /**
     * The document is sent to the browser unless openToFile() is called before the first row is added.
     */
    public function __construct($format, $filename)
    {
        $this->format = $format;
        $this->filename = $filename;

        switch($format) {
            case self::FORMAT_CSV:
                $writer = new CSV\Writer(new CSV\Options(FIELD_DELIMITER: ';', SHOULD_ADD_BOM: false));
                break;
            case self::FORMAT_ODS:
                $writer = new ODS\Writer();
                break;
            case self::FORMAT_XLSX:
                $writer = new XLSX\Writer();
                break;
            default:
                throw new \InvalidArgumentException(sprintf("format \"%s\" is not handled by OpenSpout", $format));
        }

        $this->writer = $writer;
    }

    public function __destruct()
    {
        $this->writer->close();
    }

    public function openToFile($filePath)
    {
        $this->writer->openToFile($filePath);
        $this->opened = true;
    }

    public function getActiveSheet()
    {
        if (!$this->writer instanceof AbstractWriterMultiSheets) {
            return "_unique_sheet_";
        }

        $this->open();

        return $this->writer->getCurrentSheet()->getIndex();
    }

    public function addRow($row)
    {
        $this->open();
        $this->writer->addRow(Row::fromValues($row));
    }

    public function render()
    {
        $this->open();
        $this->writer->close();
    }

    public function getWriter()
    {
        return $this->writer;
    }

    private function open()
    {
        if (!$this->opened) {
            $this->writer->openToBrowser($this->filename);
            $this->opened = true;
        }
    }
}
