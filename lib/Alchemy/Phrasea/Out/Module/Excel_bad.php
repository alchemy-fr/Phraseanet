<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*

namespace Alchemy\Phrasea\Out\Module;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel
{
    const FORMAT_CSV  = 'format_csv';
    const FORMAT_XLS  = 'format_xls';
    const FORMAT_XLSX = 'format_xlsx';

    private $spreadsheet;

    /** @var int[] * /
    private $currentRowBySheet;

    public function __construct()
    {
        $this->currentRowBySheet = [];
        $this->spreadsheet = new Spreadsheet();
    }

    public function getActiveSheet()
    {
        $sheetIndex = $this->spreadsheet->getActiveSheetIndex();
        if(!array_key_exists($sheetIndex, $this->currentRowBySheet)) {
            $this->currentRowBySheet[$sheetIndex] = 1;
        }

        return $this->spreadsheet->getActiveSheet();
    }

    public function addRow($row)
    {
        $sheet = $this->getActiveSheet();
        $sheetIndex = $this->spreadsheet->getActiveSheetIndex();
        /** @var int $r * /
        $r = $this->currentRowBySheet[$sheetIndex];
        $c = 1;
        foreach($row as $v) {
            $sheet->setCellValueByColumnAndRow($c++, $r, $v);
        }
        $this->currentRowBySheet[$sheetIndex] = $r+1;
    }

    public function fill()
    {
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');
    }

    public function render($format)
    {
        switch($format) {
            case self::FORMAT_XLS:
                header('Content-Type: application/vnd.ms-excel');
                $writer = IOFactory::createWriter($this->spreadsheet, 'Xls');
                break;
            case self::FORMAT_XLSX:
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
                break;
        }
        header('Content-Disposition: attachment;filename="myfile.xls"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($this->spreadsheet, 'Xls');

        $writer->save('php://output');
    }

}

*/
