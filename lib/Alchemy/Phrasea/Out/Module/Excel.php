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

use Box\Spout\Writer;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;


class Excel
{
    const FORMAT_CSV  = 'format_csv';
    const FORMAT_ODS  = 'format_ods';
    const FORMAT_XLSX = 'format_xlsx';

    private $format;

    /** @var \Box\Spout\Writer\WriterInterface  */
    private $writer;


    public function __construct($format, $filename)
    {
        $this->format = $format;

        switch($format) {
            case self::FORMAT_CSV:
                /** @var Writer\CSV\Writer $writer */
                $writer = WriterFactory::create(Type::CSV);
                $writer->setFieldDelimiter(';')
                       ->setShouldAddBOM(false);
                break;
            case self::FORMAT_ODS:
                /** @var Writer\ODS\Writer $writer */
                $writer = WriterFactory::create(Type::ODS);
                break;
            case self::FORMAT_XLSX:
                /** @var Writer\XLSX\Writer $writer */
                $writer = WriterFactory::create(Type::XLSX);
                break;
            default:
                throw new \InvalidArgumentException(sprintf("format \"%s\" is not handled by Spout"));
                break;
        }

        $writer->openToBrowser($filename);
        $this->writer = $writer;
    }

    public function __destruct()
    {
        $this->writer->close();
    }

    public function getActiveSheet()
    {
        if($this->format == self::FORMAT_CSV) {
            return "_unique_sheet_";
        }
        /** @var Writer\XLSX\Writer $w */
        $w = $this->writer;
        $sheetIndex = $w->getCurrentSheet()->getIndex();

        return $sheetIndex;
    }

    public function addRow($row)
    {
        $this->writer->addRow($row);
    }

    public function render()
    {
        $this->writer->close();
    }

    public function getWriter()
    {
        return $this->writer;
    }

}
