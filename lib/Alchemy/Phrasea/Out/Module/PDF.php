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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Out\Tool\PhraseaPDF;

class PDF
{
    protected $app;
    protected $records;
    protected $pdf;

    const LAYOUT_PREVIEW = 'preview';
    const LAYOUT_PREVIEWCAPTION = 'previewCaption';
    const LAYOUT_PREVIEWCAPTIONTDM = 'previewCaptionTdm';
    const LAYOUT_THUMBNAILLIST = 'thumbnailList';
    const LAYOUT_THUMBNAILGRID = 'thumbnailGrid';

    public function __construct(Application $app, array $records, $layout)
    {
        $this->app = $app;

        $list = [];

        foreach ($records as $record) {
            switch ($layout) {
                default:
                    throw new \Exception('Unknown layout');
                    break;
                case self::LAYOUT_PREVIEW:
                case self::LAYOUT_PREVIEWCAPTION:
                case self::LAYOUT_PREVIEWCAPTIONTDM:
                    try {
                        $subdef = $record->get_subdef('preview');
                        // fallback to thumbnail ( video, sound, doc ) ..
                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                            $subdef = $record->get_thumbnail();
                        }

                        if (!$subdef->is_physically_present()) {
                            continue 2;
                        }

                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                            continue 2;
                        }
                    } catch (\Exception $e) {
                        continue 2;
                    }
                    break;
                case self::LAYOUT_THUMBNAILLIST:
                case self::LAYOUT_THUMBNAILGRID:
                    try {
                        $subdef = $record->get_thumbnail();
                        if (!$subdef->is_physically_present()) {
                            continue 2;
                        }

                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                            continue 2;
                        }
                    } catch (\Exception $e) {
                        continue 2;
                    }
                    break;
            }

            $record->setNumber(count($list) + 1);

            $list[] = $record;
        }

        $this->records = $list;

        $pdf = new PhraseaPDF("P", "mm", "A4", true, 'UTF-8', false);

        $pdf->SetAuthor("Phraseanet");
        $pdf->SetTitle("Phraseanet Print");
        $pdf->SetDisplayMode("fullpage", "single");

        $this->pdf = $pdf;

        switch ($layout) {
            case self::LAYOUT_PREVIEW:
                $this->print_preview(false, false);
                break;
            case self::LAYOUT_PREVIEWCAPTION:
                $this->print_preview(false, true);
                break;
            case self::LAYOUT_PREVIEWCAPTIONTDM:
                $this->print_preview(true, true);
                break;
            case self::LAYOUT_THUMBNAILLIST:
                $this->print_thumbnailList();
                break;
            case self::LAYOUT_THUMBNAILGRID:
                $this->print_thumbnailGrid();
                break;
        }

        return $this;
    }

    public function render()
    {
        $this->pdf->Close();

        return $this->pdf->Output('', 'S');
    }

    protected function print_thumbnailGrid($links = false)
    {
        $NDiapoW = 3;
        $NDiapoH = 4;

        $this->pdf->AddPage();

        $oldMargins = $this->pdf->getMargins();
        $tmargin = $oldMargins['top'];
        $lmargin = $oldMargins['left'];
        $bmargin = $oldMargins['bottom'];
        $rmargin = $oldMargins['right'];

        $this->pdf->SetLeftMargin($lmargin + 55);

        $clientW = $this->pdf->getPageWidth() - $lmargin - $rmargin;
        $clientH = $this->pdf->getPageHeight() - $tmargin - $bmargin;

        $DiapoW = floor($clientW / $NDiapoW);
        $DiapoH = floor($clientH / $NDiapoH);
        $TitleH = 5;
        $ImgSize = min($DiapoW, ($DiapoH - $TitleH)) - 5;

        $npages = ceil(count($this->records) / ($NDiapoW * $NDiapoH));

        $irow = $ipage = 0;
        $icol = -1;
        foreach ($this->records as $rec) {
            /* @var \record_adapter $rec */
            if (++$icol >= $NDiapoW) {
                $icol = 0;
                if (++$irow >= $NDiapoH) {
                    $irow = 0;
                    $ipage++;
                    $this->pdf->AddPage();
                }
            }
            $fimg = null;
            $himg = 0;

            $subdef = $rec->get_subdef('preview');

            if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                $subdef = $rec->get_thumbnail();
            }

            $fimg = $subdef->getRealPath();

            if (!$this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::NOWATERMARK)
                && $subdef->get_type() == \media_subdef::TYPE_IMAGE) {
                $fimg = \recordutils_image::watermark($this->app, $subdef);
            }

            $wimg = $himg = $ImgSize;
            if ($subdef->get_height() > 0 && $subdef->get_width() > 0) {
                if ($subdef->get_width() > $subdef->get_height())
                    $himg = $wimg * $subdef->get_height() / $subdef->get_width();
                else
                    $wimg = $himg * $subdef->get_width() / $subdef->get_height();
            }

            if ($fimg) {
                $x = $lmargin + ($icol * $DiapoW);
                $y = $tmargin + ($irow * $DiapoH);
                $this->pdf->SetDrawColor(0);
                $this->pdf->Rect($x, $y, $DiapoW, $DiapoH, "D");

                $this->pdf->SetXY($x, $y + 1);
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 10);
                $t = $irow . '-' . $x;
                $t = $rec->get_title();

                if ($links) {
                    $lk = $this->pdf->AddLink();
                    $this->pdf->SetLink($lk, 0, $npages + $rec->getNumber());
                    $this->pdf->Image(
                        $fimg
                        , $x + (($DiapoW - $wimg) / 2)
                        , $TitleH + $y + (($DiapoH - $TitleH - $himg) / 2)
                        , $wimg, $himg
                        , null, $lk
                    );
                } else {
                    $this->pdf->Image($fimg
                        , $x + (($DiapoW - $wimg) / 2)
                        , $TitleH + $y + (($DiapoH - $TitleH - $himg) / 2)
                        , $wimg, $himg
                    );
                }

                $this->pdf->MultiCell($DiapoW, $TitleH, $t, '0', 'C', false);
            }
        }
        $this->pdf->SetLeftMargin($oldMargins['left']);
    }

    protected function print_thumbnailList()
    {
        $this->pdf->AddPage();
        $oldMargins = $this->pdf->getMargins();

        $lmargin = $oldMargins['left'];
        $rmargin = $oldMargins['right'];

        $this->pdf->SetLeftMargin($lmargin + 55);

        $ndoc = 0;
        foreach ($this->records as $rec) {
            /* @var \record_adapter $rec */
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

            $title = "record : " . $rec->get_title();

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
                /* @var $field caption_field */

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

    protected function print_preview($withtdm, $write_caption)
    {
        if ($withtdm === true) {
            $this->print_thumbnailGrid($this->pdf, $this->records, true);
        }

        foreach ($this->records as $krec => $rec) {
            /* @var \record_adapter $rec */

            $this->pdf->AddPage();

            if ($withtdm === "CALCPAGES") {
                $rec->setNumber($this->pdf->PageNo());
            }
            $lmargin = $this->pdf->GetX();
            $himg = 0;
            $y = 0;
            $miniConv = NULL;

            $LEFT__TEXT = "";
            $LEFT__IMG = NULL;
            $RIGHT_TEXT = "";
            $RIGHT_IMG = NULL;

            $LEFT__IMG = $this->app['root.path'] . "/config/minilogos/logopdf_" . $rec->getDataboxId() . ".jpg";

            if (!is_file($LEFT__IMG)) {
                $databox = $rec->getDatabox();
                $str = $databox->get_sxml_structure();
                $vn = (string) ($str->pdfPrintLogo);
                if (($vn * 1) == 1) {
                    $LEFT__TEXT = $databox->get_label($this->app['locale']);
                }
            }

            $collection = \collection::getByBaseId($this->app, $rec->getBaseId());

            $vn = "";
            if (false !== $str = simplexml_load_string($collection->get_prefs())) {
                $vn = (string) ($str->pdfPrintappear);
            }

            if ($vn == "" || $vn == "1") {
                $RIGHT_TEXT = \phrasea::bas_labels($rec->getBaseId(), $this->app);
            } elseif ($vn == "2") {
                $RIGHT_IMG = $this->app['root.path'] . "/config/minilogos/" . $rec->getBaseId();
            }

            $xtmp = $this->pdf->GetX();
            $ytmp = $this->pdf->GetY();

            $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
            $this->pdf->SetFillColor(220, 220, 220);
            $y = $this->pdf->GetY();
            $this->pdf->MultiCell(95, 7, $LEFT__TEXT, "LTB", "L", 1);
            $y2 = $this->pdf->GetY();
            $h = $y2 - $y;
            $this->pdf->SetY($y);
            $this->pdf->SetX(105);
            $this->pdf->Cell(95, $h, $RIGHT_TEXT, "TBR", 1, "R", 1);

            if ($LEFT__TEXT == "" && is_file($LEFT__IMG)) {
                if ($size = @getimagesize($LEFT__IMG)) {
                    $wmm = (int) $size[0] * 25.4 / 72;
                    $hmm = (int) $size[1] * 25.4 / 72;
                    if ($hmm > 6) {
                        $coeff = $hmm / 6;
                        $wmm = (int) $wmm / $coeff;
                        $hmm = (int) $hmm / $coeff;
                    }
                    $this->pdf->Image($LEFT__IMG, $xtmp + 0.5, $ytmp + 0.5, $wmm, $hmm);
                }
            }

            if ($RIGHT_IMG != NULL && is_file($RIGHT_IMG)) {
                if ($size = @getimagesize($RIGHT_IMG)) {

                    if ($size[2] == '1') {
                        if (!isset($miniConv[$RIGHT_IMG])) {
                            $tmp_filename = tempnam('minilogos/', 'gif4fpdf');
                            $img = imagecreatefromgif($RIGHT_IMG);
                            imageinterlace($img, 0);
                            imagepng($img, $tmp_filename);
                            rename($tmp_filename, $tmp_filename . '.png');
                            $miniConv[$RIGHT_IMG] = $tmp_filename . '.png';
                            $RIGHT_IMG = $tmp_filename . '.png';
                        } else
                            $RIGHT_IMG = $miniConv[$RIGHT_IMG];

                        $wmm = (int) $size[0] * 25.4 / 72;
                        $hmm = (int) $size[1] * 25.4 / 72;
                        if ($hmm > 6) {
                            $coeff = $hmm / 6;
                            $wmm = (int) $wmm / $coeff;
                            $hmm = (int) $hmm / $coeff;
                        }
                        $tt = 0;
                        if ($hmm < 6)
                            $tt = (6 - $hmm) / 2;
                        $this->pdf->Image($RIGHT_IMG, 200 - 0.5 - $wmm, $ytmp + 0.5 + $tt);
                    } else {
                        $wmm = (int) $size[0] * 25.4 / 72;
                        $hmm = (int) $size[1] * 25.4 / 72;
                        if ($hmm > 6) {
                            $coeff = $hmm / 6;
                            $wmm = (int) $wmm / $coeff;
                            $hmm = (int) $hmm / $coeff;
                        }
                        $this->pdf->Image($RIGHT_IMG, 200 - 0.5 - $wmm, $ytmp + 0.5);
                    }
                }
            }

            $y = $this->pdf->GetY() + 5;

            $subdef = $rec->get_subdef('preview');

            if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                $subdef = $rec->get_thumbnail();
            }

            $f = $subdef->getRealPath();

            if (!$this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::NOWATERMARK)
                && $subdef->get_type() == \media_subdef::TYPE_IMAGE)
                $f = \recordutils_image::watermark($this->app, $subdef);

            // original height / original width x new width = new height
            $wimg = $himg = 150; // preview dans un carre de 150 mm
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

            $this->pdf->Image($f, (210 - $finalWidth) / 2, $y, $finalWidth, $finalHeight);

            if ($miniConv != NULL) {
                foreach ($miniConv as $oneF)
                    unlink($oneF);
            }
            $this->pdf->SetXY($lmargin, $y += ( $finalHeight + 5));

            $nf = 0;
            if ($write_caption) {
                foreach ($rec->get_caption()->get_fields() as $field) {
                    /* @var $field caption_field */
                    if ($nf > 0) {
                        $this->pdf->Write(6, "\n");
                    }

                    $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                    $this->pdf->Write(5, $field->get_name() . " : ");

                    $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);

                    $t = str_replace(
                        ["&lt;", "&gt;", "&amp;"]
                        , ["<", ">", "&"]
                        , strip_tags($field->get_serialized_values())
                    );

                    $this->pdf->Write(5, $t);

                    $nf++;
                }
            }
        }

        return;
    }
}
