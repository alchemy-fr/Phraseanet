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
use Alchemy\Phrasea\Helper\Record\Printer;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Out\Tool\PhraseaPDF;
use IntlDateFormatter as DateFormatter;
use record_adapter;

class PDFRecords extends PDF
{
    public static $maxFilenameLength = 256;

    /** @var Printer */
    private $printer;

    /** @var MediaSubDefinitionUrlGenerator */
    private $urlGenerator;

    private $pdfTitle;
    private $pdfDescription;
    private $isUserInputPrinted = false;
    private $canDownload;
    private $downloadSubdef;
    private $showRecordInfo;
    private $descriptionFontSize;
    private $fieldTitleColor;

    private $thumbnailName  = 'thumbnail';
    private $previewName    = 'preview;';

    public function __construct(Application $app, Printer $printer, $layout, $pdfTitle = '', $pdfDescription = '', $userPassword = '', $canDownload = false, $downloadSubdef = '', $showRecordInfo = true, $descriptionFontSize = 12, $fieldTitleColor = '')
    {
        parent::__construct($app);
        $this->urlGenerator = $app['media_accessor.subdef_url_generator'];
        $this->printer  = $printer;
        $this->pdfTitle = $pdfTitle;
        $this->pdfDescription = $pdfDescription;
        $this->canDownload    = $canDownload;
        $this->showRecordInfo = $showRecordInfo;
        $this->descriptionFontSize = $descriptionFontSize;
        $this->fieldTitleColor = $fieldTitleColor;

        $this->downloadSubdef = $downloadSubdef;
        $this->thumbnailName  = $printer->getThumbnailName();
        $this->previewName    = $printer->getPreviewName();

        if (!empty($userPassword)) {
            $this->pdf->SetProtection([], $userPassword);
        }

        $this->pdf->setPrintOwnerUser($app->getAuthenticatedUser());
        $this->pdf->setApp($app);

        $records = $printer->get_elements();
        $aclUser = $this->app->getAclForUser($this->app->getAuthenticatedUser());

        $list = [];

        foreach ($records as $record) {
            switch ($layout) {
                default:
                    throw new \Exception('Unknown layout');
                    break;
                case self::LAYOUT_FEEDBACK:
                case self::LAYOUT_FEEDBACKONLY:
                case self::LAYOUT_PREVIEW:
                case self::LAYOUT_PREVIEWCAPTION:
                case self::LAYOUT_PREVIEWCAPTIONTDM:
                    try {
                        $subdef = $record->get_subdef($this->previewName);
                        // fallback to thumbnail ( video, sound, doc ) ..
                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                            $subdef = $record->get_subdef($this->thumbnailName);
                        }

//                        if (!$subdef->is_physically_present()) {
//                            continue 2;
//                        }

                        // check access right on the subdef
                        if (
                            ($subdef->get_name() != 'document' && !$aclUser->has_access_to_subdef($record, $subdef->get_name()))
                            ||
                            ($subdef->get_name() == 'document' && !$aclUser->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD))
                        ) {
                            continue 2;
                        }

//                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
//                            continue 2;
//                        }
                    } catch (\Exception $e) {
                        // use substitution image if the subdef is not available
                        //continue 2;
                    }
                    break;
                case self::LAYOUT_THUMBNAILLIST:
                case self::LAYOUT_THUMBNAILGRID:
                    try {
                        $subdef = $record->get_subdef($this->thumbnailName);
//                        if (!$subdef->is_physically_present()) {
//                            continue 2;
//                        }

                        // check access right on the subdef
                        if (
                            ($subdef->get_name() != 'document' && !$aclUser->has_access_to_subdef($record, $subdef->get_name()))
                            ||
                            ($subdef->get_name() == 'document' && !$aclUser->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD))
                        ) {
                            continue 2;
                        }

//                        if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
//                            continue 2;
//                        }
                    } catch (\Exception $e) {
                        // use substitution image if the subdef is not available
                        //continue 2;
                    }
                    break;
                case self::LAYOUT_CAPTION:
                    break;
            }

            $record->setNumber(count($list) + 1);

            $list[] = $record;
        }

        $this->records = $list;

        switch ($layout) {
            case self::LAYOUT_FEEDBACK:
                $this->print_preview(false, true, true);
                break;
            case self::LAYOUT_FEEDBACKONLY:
                $this->print_preview(false, false, true);
                break;
            case self::LAYOUT_PREVIEW:
                $this->print_preview(false, false, false);
                break;
            case self::LAYOUT_PREVIEWCAPTION:
                $this->print_preview(false, true, false);
                break;
            case self::LAYOUT_PREVIEWCAPTIONTDM:
                $this->print_preview(true, true, false);
                break;
            case self::LAYOUT_THUMBNAILLIST:
                $this->print_thumbnailList();
                break;
            case self::LAYOUT_THUMBNAILGRID:
                $this->print_thumbnailGrid();
                break;
            case self::LAYOUT_CAPTION:
                $this->print_caption();
                break;
        }
    }

    protected function print_thumbnailGrid($links = false)
    {
        $this->addUserInput();

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
            /* @var record_adapter $rec */
            if (++$icol >= $NDiapoW) {
                $icol = 0;
                if (++$irow >= $NDiapoH) {
                    $irow = 0;
                    $ipage++;
                    $this->pdf->AddPage();
                }
            }

            $fimg = null;

            $wimg = $himg = $ImgSize;

            $subdef = null;

            if ($rec->has_subdef($this->thumbnailName)) {
                $subdef = $rec->get_subdef($this->thumbnailName);
            }

            if ($subdef == null || ($subdef != null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()) && $rec->has_subdef('thumbnail'))) {
                $subdef = $rec->get_subdef('thumbnail');
            }

            if ($subdef == null ||
                ($subdef !== null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()))) {
                $fimg = sprintf('%s/assets/common/images/icons/substitution/%s.png',
                    $this->app['root.path']."/www",
                    str_replace('/', '_', $rec->getMimeType())
                );

                if ($size = @getimagesize($fimg)) {
                    $wmm = (int) $size[0] ;
                    $hmm = (int) $size[1] ;
                    if ($wmm > $hmm) {
                        $himg = $wimg * $hmm / $wmm;
                    } else {
                        $wimg = $himg * $wmm / $hmm;
                    }
                }

            } else {
                $fimg = $subdef->getRealPath();
                if (!$this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::NOWATERMARK)
                    && $subdef->get_type() == \media_subdef::TYPE_IMAGE) {
                    $fimg = \recordutils_image::watermark($this->app, $subdef);
                }

                if ($subdef->get_height() > 0 && $subdef->get_width() > 0) {
                    if ($subdef->get_width() > $subdef->get_height()) {
                        $himg = $wimg * $subdef->get_height() / $subdef->get_width();
                    } else {
                        $wimg = $himg * $subdef->get_width() / $subdef->get_height();
                    }
                }
            }

            if ($fimg) {
                $x = $lmargin + ($icol * $DiapoW);
                $y = $tmargin + ($irow * $DiapoH);
                $this->pdf->SetDrawColor(0);
                $this->pdf->Rect($x, $y, $DiapoW, $DiapoH, "D");

                $this->pdf->SetXY($x, $y + 1);
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 10);

                if ($links) {
                    $lk = $this->pdf->AddLink();
                    $this->pdf->SetLink($lk, 0, ($this->pdf->getPage() - 1) + ($npages - $ipage) + $rec->getNumber());
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

                $downloadLink = $rec->get_title(['encode'=> record_adapter::ENCODE_FOR_URI]);


                if ($this->canDownload && !empty($this->downloadSubdef) && $rec->has_subdef($this->downloadSubdef)
                    && (($rec->get_subdef($this->downloadSubdef)->get_name() != 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDPREVIEW))
                        ||
                        ($rec->get_subdef($this->downloadSubdef)->get_name() == 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDHD)))
                ) {
                    $sd = $rec->get_subdef($this->downloadSubdef);
                    if ($sd->is_physically_present()) {
                        $url = $this->getDownloadUrl($sd);
                        $downloadLink = sprintf('<a style="text-decoration: none;" href="%s">%s</a>', $url, $rec->get_title(['encode'=> record_adapter::ENCODE_FOR_HTML]));
                    }
                }

                $this->pdf->MultiCell($DiapoW, $TitleH, $downloadLink, '0', 'C', false, 1, '', '', true, 0, true);

                $this->pdf->Circle($x + 6, $y + $DiapoH - 6, 5, 0, 360, "F", [], [200, 200, 200]);

                // center num in the cercle
                switch (strlen($rec->getNumber())) {
                    case 1:
                        $xNum = $x + 4;
                        break;
                    case 2:
                        $xNum = $x + 3;
                        break;
                    case 3:
                        $xNum = $x + 2;
                        break;
                    default:
                        $xNum = $x + 1;
                }
                $this->pdf->SetXY($xNum, $y + $DiapoH - 10);
                $this->pdf->Write('8', $rec->getNumber());
            }
        }
        $this->pdf->SetLeftMargin($oldMargins['left']);
    }

    protected function print_thumbnailList()
    {
        $this->addUserInput();

        $this->pdf->AddPage();
        $oldMargins = $this->pdf->getMargins();

        $lmargin = $oldMargins['left'];
        $rmargin = $oldMargins['right'];

        $this->pdf->SetLeftMargin($lmargin + 55);

        $ndoc = 0;
        /* @var record_adapter $rec */
        foreach ($this->records as $rec) {
            $subdef = null;

            if ($rec->has_subdef($this->thumbnailName)) {
                $subdef = $rec->get_subdef($this->thumbnailName);
            }

            if ($subdef == null || ($subdef != null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()) && $rec->has_subdef('thumbnail'))) {
                $subdef = $rec->get_subdef('thumbnail');
            }

            $wimg = $himg = 50;

            $fimg = null;

            if ($subdef == null ||
                ($subdef !== null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()))) {
                $fimg = sprintf('%s/assets/common/images/icons/substitution/%s.png',
                    $this->app['root.path']."/www",
                    str_replace('/', '_', $rec->getMimeType())
                );

                if ($size = @getimagesize($fimg)) {
                    $wi = (int) $size[0] ;
                    $hi = (int) $size[1] ;
                }
            } else {
                $fimg = $subdef->getRealPath();
                $wi = $subdef->get_width();
                $hi = $subdef->get_height();
            }

            // 1px = 3.77952 mm
            $finalWidth = round($wi / 3.779528, 2);
            $finalHeight = round($hi / 3.779528, 2);

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

            if ($this->canDownload && !empty($this->downloadSubdef) && $rec->has_subdef($this->downloadSubdef)
                && (($rec->get_subdef($this->downloadSubdef)->get_name() != 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDPREVIEW))
                    ||
                    ($rec->get_subdef($this->downloadSubdef)->get_name() == 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDHD)))
            ) {
                $sd = $rec->get_subdef($this->downloadSubdef);
                if ($sd->is_physically_present()) {
                    $url = $this->getDownloadUrl($sd);
                    $downloadLink = sprintf('<a style="text-decoration: none;" href="%s">%s</a>', $url, $this->app->trans("print:: download"));

                    $this->pdf->writeHTML($downloadLink, true, false, false, true);
                }
            }

            $this->pdf->SetY($this->pdf->GetY() + 2);
            if ($this->showRecordInfo) {
                $this->showRecordInfoBloc($rec);
                $this->pdf->Write(6, "\n");
            }

            $r = $g = $b = 0;
            if (!empty($this->fieldTitleColor)) {
                list($r, $g, $b) = sscanf($this->fieldTitleColor, "#%02x%02x%02x");
            }

            foreach ($rec->get_caption()->get_fields() as $field) {
                /* @var $field caption_field */

                if (!empty($this->fieldTitleColor)) {
                    $this->pdf->SetTextColor($r, $g, $b);
                }

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
                $this->pdf->Write(5, $field->get_databox_field()->get_label($this->app['locale']) . " : ");

                $this->pdf->SetTextColor(0);
                $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
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

    protected function print_caption()
    {
        $this->addUserInput();

        $this->pdf->AddPage();
        $oldMargins = $this->pdf->getMargins();

        $lmargin = $oldMargins['left'];
        $rmargin = $oldMargins['right'];

        /* @var record_adapter $rec */
        foreach ($this->records as $rec) {
            $title = "record : " . $rec->get_title(['encode'=> record_adapter::ENCODE_NONE]);

            $y = $this->pdf->GetY();
            if($this->pdf->getPageHeight() - $y < 30){ // height of the footer is 15
                $this->pdf->AddPage();
                $y = $oldMargins['top'];
            }

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
            $this->pdf->SetY($y + 2);

            if ($this->canDownload && !empty($this->downloadSubdef) && $rec->has_subdef($this->downloadSubdef)
                && ((($rec->get_subdef($this->downloadSubdef)->get_name() != 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDPREVIEW))
                    ||
                    ($rec->get_subdef($this->downloadSubdef)->get_name() == 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDHD))) )
            ) {
                $sd = $rec->get_subdef($this->downloadSubdef);
                if ($sd->is_physically_present()) {
                    $url = $this->getDownloadUrl($sd);
                    $downloadLink = sprintf('<a style="text-decoration: none;" href="%s">%s</a>', $url, $this->app->trans("print:: download"));

                    $this->pdf->writeHTML($downloadLink, true, false, false, true);
                }
            }

            $this->pdf->SetY($this->pdf->GetY() + 2);
            if ($this->showRecordInfo) {
                $this->showRecordInfoBloc($rec);
                $this->pdf->Write(6, "\n");
            }

            foreach ($rec->get_caption()->get_fields() as $field) {
                if ($field->get_databox_field()->get_printable()) {
                    if (!empty($this->fieldTitleColor)) {
                        list($r, $g, $b) = sscanf($this->fieldTitleColor, "#%02x%02x%02x");
                        $this->pdf->SetTextColor($r, $g, $b);
                    }
                    $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
                    $this->pdf->Write(5, $field->get_databox_field()->get_label($this->app['locale']) . " : ");

                    $this->pdf->SetTextColor(0);
                    $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
                    $t = str_replace(
                        ["&lt;", "&gt;", "&amp;"]
                        , ["<", ">", "&"]
                        , strip_tags($field->get_serialized_values())
                    );
                    $this->pdf->Write(5, $t);

                    $this->pdf->Write(6, "\n");
                }                
            }
            $this->pdf->SetY($this->pdf->GetY() + 10);
        }
    }


    protected function print_preview($withtdm, $write_caption, $withfeedback)
    {
        $basket = null;

        $this->addUserInput();

        if($this->printer->is_basket()) {
            $basket = $this->printer->get_original_basket();

            if($withfeedback) {
                // first page : validation informations
                $this->pdf->AddPage();

                $this->pdf->SetY(20);
                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 14);
                $this->pdf->Cell(0, 0,
                    $this->app->trans("print_feedback:: Feedback on basket %name%", ['%name%'=>$basket->getName()]),
                    '', 1, 'C', false);

                $this->pdf->SetY($this->pdf->GetY()+10);

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Document generated on : ") . " ");
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                $this->pdf->Write(5, $this->formatDate(new \DateTime('now')));
                $this->pdf->Write(12, "\n");

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Feedback initiated by : ") . " ");
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                $this->pdf->Write(5, $this->getDisplayName($basket->getVoteInitiator()));
                $this->pdf->Write(6, "\n");

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Feedback initiated on : ") . " ");
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                $this->pdf->Write(5, $this->formatDate($basket->getVoteCreated()));
                $this->pdf->Write(6, "\n");

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Feedback expiring on : ") . " ");
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                $this->pdf->Write(5, $this->formatDate($basket->getVoteExpires()));
                $this->pdf->Write(12, "\n");

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $basket->isVoteFinished() ? $this->pdf->Write(5, $this->app->trans("print_feedback:: Feedback expired")) : $this->pdf->Write(5, $this->app->trans("print_feedback:: Feedback active"));
                $this->pdf->Write(12, "\n");

                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Participants : "));
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                foreach ($basket->getParticipants() as $participant) {
                    $this->pdf->Write(5, "\n - " . $this->getDisplayName($participant->getUser()));
                }
            }
        }

        if ($withtdm === true) {
            $this->print_thumbnailGrid(true);
        }

        foreach ($this->records as $krec => $rec) {
            /* @var record_adapter $rec */

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

            if ($basket) {
                $ord = $basket->getElementByRecord($this->app, $rec)->getOrd();
                $this->pdf->SetY($y);
                $this->pdf->SetX(10);
                $this->pdf->Cell(190, $h, $ord, "", 1, "C", 0);
            } else {
                $this->pdf->SetY($y);
                $this->pdf->write(7, $rec->getNumber(), '', false, 'C');
                $this->pdf->SetY($y2);
            }

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

            $subdef = null;

            if ($rec->has_subdef($this->previewName)) {
                /* @var record_adapter $rec */
                $subdef = $rec->get_subdef($this->previewName);
            }

            if ($subdef == null || ($subdef != null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()) && $rec->has_subdef('thumbnail'))) {
                $subdef = $rec->get_subdef('thumbnail');
            }

            // original height / original width x new width = new height
            $wimg = $himg = 150; // preview dans un carre de 150 mm

            if ($subdef == null ||
                ($subdef !== null && ($subdef->get_type() !== \media_subdef::TYPE_IMAGE || !$subdef->is_physically_present()))) {
                $f = sprintf('%s/assets/common/images/icons/substitution/%s.png',
                    $this->app['root.path']."/www",
                    str_replace('/', '_', $rec->getMimeType())
                );

                if ($size = @getimagesize($f)) {
                    $wi = (int) $size[0] ;
                    $hi = (int) $size[1] ;
                }
            } else {
                $f = $subdef->getRealPath();

                if (!$this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::NOWATERMARK)
                    && $subdef->get_type() == \media_subdef::TYPE_IMAGE) {
                    $f = \recordutils_image::watermark($this->app, $subdef);
                }
                $wi = $subdef->get_width();
                $hi = $subdef->get_height();
            }

            // 1px = 3.77952 mm
            $finalWidth = round($wi / 3.779528, 2);
            $finalHeight = round($hi / 3.779528, 2);
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

            if ($this->canDownload && !empty($this->downloadSubdef) && $rec->has_subdef($this->downloadSubdef)
                && ((($rec->get_subdef($this->downloadSubdef)->get_name() != 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDPREVIEW))
                    ||
                    ($rec->get_subdef($this->downloadSubdef)->get_name() == 'document' && $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($rec->getBaseId(), \ACL::CANDWNLDHD))))
            ) {
                $sd = $rec->get_subdef($this->downloadSubdef);
                if ($sd->is_physically_present()) {
                    $url = $this->getDownloadUrl($sd);
                    $downloadLink = sprintf('<a style="text-decoration: none;" href="%s">%s</a>', $url, $this->app->trans("print:: download"));

                    $this->pdf->writeHTML($downloadLink, true, false, false, true);
                }
            }

            if ($this->showRecordInfo) {
                $this->showRecordInfoBloc($rec);
            }

            $nf = 0;
            if ($basket && $basket->isVoteBasket()) {
                /** @var ValidationParticipant $participant */

                if ($nf > 0) {
                    $this->pdf->Write(6, "\n");
                }
                $this->pdf->Write(12, "\n");
                $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 12);
                $this->pdf->Write(5, $this->app->trans("print_feedback:: Votes :"));
                $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);

                $basketElement = $basket->getElementByRecord($this->app, $rec);

                $iparticipant = 0;
                foreach ($basket->getParticipants() as $participant) {
                    $this->pdf->Write(6, "\n");
//                    if ($iparticipant++ > 0) {
                        // $this->pdf->SetY($this->pdf->GetY()+1);
//                    }

                    try {
                        $basketElementVote = $basketElement->getUserVote($participant->getUser(), true);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $this->pdf->Write(5, '- ' . $this->getDisplayName($participant->getUser(), true). " : ");

                    $r = $basketElementVote->getAgreement();
                    $this->pdf->SetX(100);
                    if ($r === null) {
                        $this->pdf->Write(0, $this->app->trans("print_feedback:: non voté"));
                    }
                    else {
                        if($r) {
                            $this->pdf->SetTextColor(0, 127, 0);
                            $this->pdf->Write(0, $this->app->trans("print_feedback:: Oui"));
                        }
                        else {
                            $this->pdf->SetTextColor(200, 0, 0);
                            $this->pdf->Write(0, $this->app->trans("print_feedback:: Non"));
                        }
                        $this->pdf->SetTextColor(0);
                        $this->pdf->Write(0, "  (" . $this->formatDate($basketElementVote->getUpdated()) . ")");
                    }

                    if (($note = (string)($basketElementVote->getNote())) !== '') {
                        $this->pdf->SetFont(PhraseaPDF::FONT, 'I', 11);
                        $this->pdf->Write(5,"\n");
                        $this->pdf->SetX(100);
                        $this->pdf->MultiCell(95, 0, $note, '', "L", false);
                        $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
                    }

                    $nf++;
                }
            }

            if ($write_caption) {
                $this->pdf->Write(6, "\n");
                foreach ($rec->get_caption()->get_fields() as $field) {
                    /* @var $field caption_field */

                    if ($field->get_databox_field()->get_printable()) {
                        if ($nf > 0) {
                            $this->pdf->Write(6, "\n");
                        }

                        if (!empty($this->fieldTitleColor)) {
                            list($r, $g, $b) = sscanf($this->fieldTitleColor, "#%02x%02x%02x");
                            $this->pdf->SetTextColor($r, $g, $b);
                        }

                        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
                        $this->pdf->Write(5, $field->get_databox_field()->get_label($this->app['locale']) . " : ");

                        $this->pdf->SetTextColor(0);
                        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);

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
        }

        return;
    }

    private function showRecordInfoBloc(record_adapter $rec)
    {
        $r = $g = $b = 0;
        if (!empty($this->fieldTitleColor)) {
            list($r, $g, $b) = sscanf($this->fieldTitleColor, "#%02x%02x%02x");
            $this->pdf->SetTextColor($r, $g, $b);
        }
        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
        $this->pdf->Write(5, $this->app->trans("print_feedback:: record title: ") . " ");
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
        $this->pdf->Write(5, $rec->get_title(['encode'=> record_adapter::ENCODE_NONE]));
        $this->pdf->Write(6, "\n");

        if (!empty($this->fieldTitleColor)) {
            $this->pdf->SetTextColor($r, $g, $b);
        }
        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
        $this->pdf->Write(5, $this->app->trans("print_feedback:: record id: ") . " ");
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
        $this->pdf->Write(5, $rec->getRecordId());
        $this->pdf->Write(6, "\n");

        if (!empty($this->fieldTitleColor)) {
            $this->pdf->SetTextColor($r, $g, $b);
        }
        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
        $this->pdf->Write(5, $this->app->trans("print_feedback:: base name: ") . " ");
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
        $this->pdf->Write(5, $rec->getDatabox()->get_label($this->app['locale']));
        $this->pdf->Write(6, "\n");

        if (!empty($this->fieldTitleColor)) {
            $this->pdf->SetTextColor($r, $g, $b);
        }
        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
        $this->pdf->Write(5, $this->app->trans("print_feedback:: originale filename: ") . " ");
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
        $this->pdf->Write(5, $rec->get_original_name());
        $this->pdf->Write(6, "\n");

        if (!empty($this->fieldTitleColor)) {
            $this->pdf->SetTextColor($r, $g, $b);
        }
        $this->pdf->SetFont(PhraseaPDF::FONT, 'B', $this->descriptionFontSize);
        $this->pdf->Write(5, $this->app->trans("print_feedback:: document Uuid: ") . " ");
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont(PhraseaPDF::FONT, '', $this->descriptionFontSize);
        $this->pdf->Write(5, $rec->getUUID());
        $this->pdf->Write(6, "\n");
    }

    private function formatDate(\DateTime $date)
    {
        $locale = $this->app['locale'];

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

    private function getDisplayName($user, $short = false)
    {
        $displayName = '';

        if (trim($user->getLastName()) !== '' || trim($user->getFirstName()) !== '') {
            $displayName = $user->getFirstName() . ('' !== $user->getFirstName() && '' !== $user->getLastName() ? ' ' : '') . $user->getLastName() ;

            if ($short) {
                return $displayName;
            }
        }

        $email = trim($user->getEmail());

        if ($email === '') {
            $email = $user->getLogin();
        }

        if ($displayName !== '') {
            return $displayName . ", " . $email;
        } else {
            return $email;
        }
    }

    private function addUserInput()
    {
        if (!$this->isUserInputPrinted && (!empty($this->pdfTitle) || !empty($this->pdfDescription))) {
            $this->pdf->AddPage();

            $this->pdf->SetY(50);
            $this->pdf->SetFont(PhraseaPDF::FONT, 'B', 14);
            $this->pdf->Cell(0, 0,
                $this->pdfTitle,
                '', 1, 'C', false);

            $this->pdf->SetY($this->pdf->GetY() + 15);

            $this->pdf->SetFont(PhraseaPDF::FONT, '', 12);
            $this->pdf->writeHTML($this->pdfDescription);

            $this->isUserInputPrinted = true;
        }
    }

    private function getDownloadUrl(\media_subdef $subdef)
    {
        $url = (string)$this->urlGenerator->generate($this->app->getAuthenticatedUser(), $subdef, $this->printer->getUrlTtl())."?download=1";
        $infos = pathinfo($subdef->getRealPath());

        if ($this->printer->getTitleAsDownloadName()) {
            $filename = mb_strtolower(mb_substr($subdef->get_record()->get_title(['removeExtension' => true, 'encode'=> record_adapter::ENCODE_FOR_URI]), 0, self::$maxFilenameLength), 'UTF-8');
        } else {
            $originalName = $subdef->get_record()->get_original_name(true);
            $originalName = empty($originalName) ? $subdef->get_record()->getId() : $originalName;
            $filename = $subdef->get_name() == 'document' ? $originalName : $originalName . '_' . $subdef->get_name() ;
        }

        $url = $url . "&filename=" . urlencode($filename) . '.' . $infos['extension'];

        return $url;
    }
}
