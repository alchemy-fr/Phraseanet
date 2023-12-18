<?php

namespace Alchemy\Phrasea\Out\Module;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Out\Tool\PhraseaPDF;
use IntlDateFormatter as DateFormatter;
use record_adapter;

class PDFCgu extends PDF
{
    private $databoxId;
    private $htmlContent = '';
    private $recordIds;

    public function __construct(Application $app, $databoxId, array $recordIds)
    {
        parent::__construct($app);

        $this->app = $app;
        $this->databoxId = $databoxId;
        $this->recordIds = $recordIds;

        $this->printCgu();
    }

    public function save()
    {
        if (!$this->isContentEmpty()) {
            $this->pdf->Close();
            $pathName =  self::getDataboxCguPath($this->app, $this->databoxId);

            $this->pdf->Output($pathName, 'F');
        }
    }

    public static function getDataboxCguPath(Application $app, $databoxId)
    {
        return \p4string::addEndSlash($app['tmp.download.path']). self::getDataboxCguPdfName($app, $databoxId);
    }

    public static function getDataboxCguPdfName(Application $app, $databoxId)
    {
        $databox = $app->findDataboxById($databoxId);

        return 'cgu_' . $databox->get_viewname() . '.pdf';
    }

    public static function isDataboxCguEmpty(Application $app, $databoxId)
    {
        $databox = $app->findDataboxById($databoxId);
        $CGUs = $databox->get_cgus();

        foreach ($CGUs as $locale => $tou) {
            if (trim($tou['value']) !== '') {
                return false;
            }
        }

        return true;
    }

    private function printCgu()
    {
        $databox = $this->app->findDataboxById($this->databoxId);
        $databox->get_dbname();

        $CGUs = $databox->get_cgus();
        $printedDate = new \DateTime();

        foreach ($CGUs as $locale => $tou) {
            if (trim($tou['value']) !== '') {
                $this->htmlContent .= '<h2> ' . $this->app->trans('Terms Of Use', [], 'messages', $locale) . '</h2>';
                $infoDate = $this->app->trans('CGU::PDF CGU generated on %updated_on% and printed on %printed_on%', [
                    '%updated_on%' => $this->formatDate(new \DateTime($tou['updated_on']), $locale),
                    '%printed_on%' => $this->formatDate($printedDate, $locale)
                ], 'messages', $locale);

                $this->htmlContent .= '<p><strong style="color:#737275;">'. $infoDate . '</strong></p>';
                $this->htmlContent .= $tou['value'];
            }
        }

        if (!$this->isContentEmpty()) {
            $this->pdf->AddPage();

            $this->pdf->writeHTML($this->htmlContent);
            // add thumbnail in cgu
            $this->print_thumbnail_list();
        }
    }

    private function print_thumbnail_list()
    {
        $this->pdf->AddPage();

        $oldMargins = $this->pdf->getMargins();

        $lmargin = $oldMargins['left'];
        $rmargin = $oldMargins['right'];

        $this->pdf->SetLeftMargin($lmargin + 55);

        $ndoc = 0;
        foreach ($this->recordIds as $recordId) {
            /* @var record_adapter $rec */
            $rec = new record_adapter($this->app, $this->databoxId, $recordId);
            $subdef = $rec->get_subdef('thumbnail');

            $fimg = $subdef->getRealPath();

            $wimg = $himg = 50;
            // 1px = 3.77952 mm
            $finalWidth = round($subdef->get_width() / 3.779528, 2);
            $finalHeight = round($subdef->get_height() / 3.779528, 2);
            $aspectH = $finalWidth/$finalHeight;
            $aspectW = $finalHeight/$finalWidth;

            if ($finalWidth > 0 && $finalHeight > 0) {
                if ($finalWidth > $finalHeight && $finalWidth > $wimg) {
                    $finalWidth = $wimg;
                    $finalHeight = $wimg * $aspectW;
                } else if ($finalHeight > $finalWidth && $finalHeight > $himg) {
                    $finalHeight = $himg;
                    $finalWidth = $himg * $aspectH;
                } else if ($finalHeight == $finalWidth & $finalWidth > $wimg) {
                    $finalHeight = $wimg;
                    $finalWidth = $himg;
                }
            }

            if ($this->pdf->GetY() > $this->pdf->getPageHeight() - (6 + $finalHeight + 20))
                $this->pdf->AddPage();

            $title = "record : " . $rec->get_title(['encode'=> record_adapter::ENCODE_NONE]);

            $y = $this->pdf->GetY();

            $t = \phrasea::bas_labels($rec->getBaseId(), $this->app);
            $this->pdf->SetFont(PhraseaPDF::FONT, '', 10);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->SetLeftMargin($lmargin);
            $this->pdf->SetRightMargin($rmargin);
            $this->pdf->SetX($lmargin);
            $this->pdf->SetY($y);

            $this->pdf->out = false;
            $this->pdf->MultiCell(140, 4, $title, "LTR", "L", 1);
            $y2 = $this->pdf->GetY();
            $h = $y2 - $y;
            $this->pdf->out = true;
            $this->pdf->SetX($lmargin);
            $this->pdf->SetY($y);
            $this->pdf->Cell(0, $h, "", "LTR", 1, "R", 1);
            $this->pdf->SetX($lmargin);
            $this->pdf->SetY($y);
            $this->pdf->Cell(0, 4, $t, "", 1, "R");
            $this->pdf->SetX($lmargin);
            $this->pdf->SetY($y);
            $this->pdf->MultiCell(140, 4, $title, "", "L");
            $this->pdf->SetX($lmargin);
            $this->pdf->SetY($y = $y2);

            $this->pdf->SetLeftMargin($lmargin + 55);
            $this->pdf->SetY($y + 2);

            if ($fimg) {
                $y = $this->pdf->GetY();
                $this->pdf->Image($fimg, $lmargin, $y, $finalWidth, $finalHeight);
                $this->pdf->SetY($y + 3);
            }

            $nf = 0;
            $this->pdf->SetX($lmargin + 55);
            $p0 = $this->pdf->PageNo();
            $y0 = $this->pdf->GetY();
            foreach ($rec->get_caption()->get_fields() as $field) {
                /* @var $field \caption_field */

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $field->get_name() . " : ");

                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                $this->pdf->Write(5, $field->get_serialized_values());

                $this->pdf->Write(6, "\n");
                $nf++;
            }
            if ($this->pdf->PageNo() == $p0 && ($this->pdf->GetY() - $y0) < $finalHeight)
                $this->pdf->SetY($y0 + $finalHeight);
            $ndoc++;
        }
        $this->pdf->SetLeftMargin($lmargin);
    }

    private function isContentEmpty()
    {
        return (trim($this->htmlContent) === '') ? true : false;
    }

    private function formatDate(\DateTime $date, $locale)
    {
        switch ($locale) {
            case 'fr':
                $fmt = new DateFormatter(
                    'fr_FR',
                    DateFormatter::LONG,
                    DateFormatter::NONE
                );

                $date_formated = $fmt->format($date);
                break;

            case 'en':
                $fmt = new DateFormatter(
                    'en_EN',
                    DateFormatter::LONG,
                    DateFormatter::NONE
                );

                $date_formated = $fmt->format($date);
                break;

            case 'de':
                $fmt = new DateFormatter(
                    'de_DE',
                    DateFormatter::LONG,
                    DateFormatter::NONE
                );

                $date_formated = $fmt->format($date);
                break;

            default:
                $fmt = new DateFormatter(
                    'en_EN',
                    DateFormatter::LONG,
                    DateFormatter::NONE ,
                    null,
                    null,
                    'yyyy/mm/dd'
                );

                $date_formated = $fmt->format($date);
                break;
        }

        return $date_formated;
    }
}
