<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require($registry->get('GV_RootPath') . 'lib/vendor/tcpdf/tcpdf.php');

define('ZFONT', 'freesans');

# Pour Affichage du viewname dans le bandeau en haut a gauche
$printViewName = FALSE; // viewname = base
$printlogosite = TRUE;


$presentationpage = false;

$request = http_request::getInstance();
$parm = $request->get_parms('ACT'
        , 'form'
        , 'lay'
        , 'lst'
        , 'callclient'
); // , "SSTTID", false);

$lng = Session_Handler::get_locale();

$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();

$usr_id = $session->get_usr_id();

if ($parm['ACT'] == 'LOAD')
{
  ?>
  <html lang="<?php echo $session->get_I18n(); ?>">
    <head>
      <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
      <base target="_self">
      <script type="text/javascript">
        function loaded()
        {
          self.focus();
          var w = window.dialogArguments ? window.dialogArguments : self.opener;
          if(w.document.forms['<?php echo $parm['form'] ?>'].lay.length == undefined)
          {
            document.forms[0].lay.value = w.document.forms['<?php echo $parm['form'] ?>'].lay.value;
          }
          else
          {
            for(i=0; i<w.document.forms['<?php echo $parm['form'] ?>'].lay.length; i++)
            {
              if(w.document.forms['<?php echo $parm['form'] ?>'].lay[i].checked)
              {
                document.forms[0].lay.value = w.document.forms['<?php echo $parm['form'] ?>'].lay[i].value;
                break;
              }
            }
          }
          document.forms[0].lst.value = w.document.forms['<?php echo $parm['form'] ?>'].lst.value;
  <?php
  if ($parm['callclient'] != '1')
  {
    ?>
          w.close();
    <?php
  }
  ?>
      document.forms[0].submit();
    }
      </script>
    </head>
    <body onload="loaded();">
      <form action="./printpage_pdf.php" method="POST">
        <input type="hidden" name="ACT" value="PRINT" />
        <input type="hidden" name="lng" value="<?php echo $lng ?>" />
        <input type="hidden" name="usr" value="<?php echo $usr_id ?>" />
        <input type="hidden" name="lay" value="??" />
        <input type="hidden" name="lst" value="??" />
      </form>
    </body>
  </html>
  <?php
  die();
}  // $parm['ACT']=='LOAD'
// ------- ici si ACT=PRINT

class PDF extends TCPDF
{

  function Header()
  {

  }

  function Footer()
  {
//    $ml = $this->lMargin;
//    $mr = $this->rMargin;

    $ml = $this->SetLeftMargin(0);
    $mr = $this->SetRightMargin(0);

//Positionnement e 1,5 cm du bas
    $this->SetY(-15);
//Police Arial italique 8
    $this->SetFont(ZFONT, 'I', 8);
//Numero de page centre
    $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');

    $this->SetFont(ZFONT, '', 8);
    $w = $this->GetStringWidth('Printed by');
    $this->SetFont(ZFONT, 'B', 8);
    $w += $this->GetStringWidth(' Phraseanet');

    $this->SetXY(-$w - $mr - 5, -15);

    $this->SetFont(ZFONT, '', 8);
    $this->Write(8, 'Printed by');
    $this->SetFont(ZFONT, 'B', 8);
    $this->Write(8, ' Phraseanet');
  }

}

$tot_record = 0;
$tot_hd = 0;
$tot_prev = 0;

$regid = NULL;
$printReg = FALSE;
$child = 0;

$pdf = null;
$lst = explode(";", $parm["lst"]);
$list = array();
foreach ($lst as $k => $basrec)
{
  if (count($basrec = explode("_", $basrec)) !== 2)
    continue;
  $record = new record_adapter($basrec[0], $basrec[1]);

  switch ($parm["lay"])
  {
    case "preview":
    case "previewCaption":
    case "previewCaptionTdm":
    default:
      try
      {
        $subdef = $record->get_subdef('subdef');
        if (!$subdef->is_physically_present())
          continue;

        if ($subdef->get_type() !== media_subdef::TYPE_IMAGE)
          continue;

        $subdef = $record->get_subdef('thumbnail');
        if (!$subdef->is_physically_present())
          continue;

        if ($subdef->get_type() !== media_subdef::TYPE_IMAGE)
          continue;
      }
      catch (Exception $e)
      {
        continue;
      }
      break;
    case "thumbnailList":
    case "thumbnailGrid":
      try
      {
        $subdef = $record->get_subdef('thumbnail');
        if (!$subdef->is_physically_present())
          continue;

        if ($subdef->get_type() !== media_subdef::TYPE_IMAGE)
          throw new Exception('Not suitable');
      }
      catch (Exception $e)
      {
        continue;
      }
      break;
  }

  $record->set_number(count($list) + 1);

  $session->get_logger($record->get_databox())
          ->log($record, Session_Logger::EVENT_PRINT, $parm["lay"], '');

  $list[] = $record;
}

$lst = $list;

$pdf = new PDF("P", "mm", "A4", true, 'UTF-8', false);

switch ($parm["lay"])
{
  case "preview":
    print_preview($pdf, $lst);
    break;
  case "previewCaption":
    print_preview($pdf, $lst);
    break;
  case "previewCaptionTdm":
    print_preview($pdf, $lst, true);
    break;
  case "thumbnailList":
    print_thumbnailList($pdf, $lst);
    break;
  case "thumbnailGrid":
    print_thumbnailGrid($pdf, $lst);
    break;
  default:
    $pdf = new PDF("P", "mm", "A4");
    $pdf->AddPage();
    $pdf->SetFont(ZFONT, 'B', 16);
    $pdf->Cell(40, 10, 'Rien a imprimer !');
    break;
}



$pdf->SetAuthor("Phraseanet");
$pdf->SetTitle("Phraseanet Print");
$pdf->SetDisplayMode("fullpage", "single");
$pdf->Close();
$pdf->Output();

function print_thumbnailGrid(PDF &$pdf, &$lst, $links=false)
{
  $appbox = appbox::get_instance();
  $registry = registry::get_instance();
  $user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

  $NDiapoW = 3;
  $NDiapoH = 4;

  $pdf->AddPage();

  $oldMargins = $pdf->getMargins();
  $tmargin = $oldMargins['top'];
  $lmargin = $oldMargins['left'];
  $bmargin = $oldMargins['bottom'];
  $rmargin = $oldMargins['right'];

  $pdf->SetLeftMargin($lmargin + 55);

  $clientW = $pdf->getPageWidth() - $lmargin - $rmargin;
  $clientH = $pdf->getPageHeight() - $tmargin - $bmargin;

  $DiapoW = floor($clientW / $NDiapoW);
  $DiapoH = floor($clientH / $NDiapoH);
  $TitleH = 5;
  $ImgSize = min($DiapoW, ($DiapoH - $TitleH)) - 5;

  $npages = ceil(count($lst) / ($NDiapoW * $NDiapoH));

  $irow = $ipage = 0;
  $icol = -1;
  foreach ($lst as $rec)
  {
    /* @var $rec record_adapter */
    if (++$icol >= $NDiapoW)
    {
      $icol = 0;
      if (++$irow >= $NDiapoH)
      {
        $irow = 0;
        $ipage++;
        $pdf->AddPage();
      }
    }
    $fimg = null;
    $himg = 0;

    $subdef = $rec->get_subdef('preview');

    $fimg = $subdef->get_pathfile();

    if (!$user->ACL()->has_right_on_base($rec->get_base_id(), "nowatermark")
            && $subdef->get_type() == media_subdef::TYPE_IMAGE)
      $fimg = recordutils_image::watermark($rec->get_base_id(), $rec->get_record_id());

    $wimg = $himg = $ImgSize;
    if ($subdef->get_height() > 0 && $subdef->get_width() > 0)
    {
      if ($subdef->get_width() > $subdef->get_height())
        $himg = $wimg * $subdef->get_height() / $subdef->get_width();
      else
        $wimg = $himg * $subdef->get_width() / $subdef->get_height();
    }

    if ($fimg)
    {
      $x = $lmargin + ($icol * $DiapoW);
      $y = $tmargin + ($irow * $DiapoH);
      $pdf->SetDrawColor(0);
      $pdf->Rect($x, $y, $DiapoW, $DiapoH, "D");

      $pdf->SetXY($x, $y + 1);
      $pdf->SetFont(ZFONT, '', 10);
      $t = $irow . '-' . $x;
      $t = $rec->get_title();

      $pdf->MultiCell($DiapoW, $TitleH, $t, '0', 'C', false);

      if ($links)
      {
        $lk = $pdf->AddLink();
        $pdf->SetLink($lk, 0, $npages + $rec->get_number());
        $pdf->Image($fimg, $x + (($DiapoW - $wimg) / 2), $TitleH + $y + (($DiapoH - $TitleH - $himg) / 2), $wimg, $himg, null, $lk);
      }
      else
      {
        $pdf->Image($fimg, $x + (($DiapoW - $wimg) / 2), $TitleH + $y + (($DiapoH - $TitleH - $himg) / 2), $wimg, $himg);
      }
    }
  }
  $pdf->SetLeftMargin($oldMargins['left']);
}

function print_thumbnailList(PDF &$pdf, &$lst)
{
  $pdf->AddPage();
  $oldMargins = $pdf->getMargins();

  $tmargin = $oldMargins['top'];
  $lmargin = $oldMargins['left'];
  $bmargin = $oldMargins['bottom'];
  $rmargin = $oldMargins['right'];

  $pdf->SetLeftMargin($lmargin + 55);

  $ndoc = 0;
  $lastpage = $pdf->PageNo();
  foreach ($lst as $rec)
  {
    /* @var $rec record_adapter */
    $subdef = $rec->get_subdef('thumbnail');

    $fimg = $subdef->get_pathfile();
    $wimg = $himg = 50;

    if ($subdef->get_width() > $subdef->get_height())
      $himg = round($wimg * $subdef->get_height() / $subdef->get_width());
    else
      $wimg = round($himg * $subdef->get_width() / $subdef->get_height());

    $himg = 0;

    if ($pdf->GetY() > $pdf->getPageHeight() - (6 + $himg + 20))
      $pdf->AddPage();

    $title = "record : " . $rec->get_title();

    $y = $pdf->GetY();

    $t = phrasea::bas_names($rec->get_base_id());
    $pdf->SetFont(ZFONT, '', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetLeftMargin($lmargin);
    $pdf->SetRightMargin($rmargin);
    $pdf->SetX($lmargin);
    $pdf->SetY($y);

    $pdf->out = false;
    $pdf->MultiCell(140, 4, $title, "LTR", "L", 1);
    $y2 = $pdf->GetY();
    $h = $y2 - $y;
    $pdf->out = true;
    $pdf->SetX($lmargin);
    $pdf->SetY($y);
    $pdf->Cell(0, $h, "", "LTR", 1, "R", 1);
    $pdf->SetX($lmargin);
    $pdf->SetY($y);
    $pdf->Cell(0, 4, $t, "", 1, "R");
    $pdf->SetX($lmargin);
    $pdf->SetY($y);
    $pdf->MultiCell(140, 4, $title, "", "L");
    $pdf->SetX($lmargin);
    $pdf->SetY($y = $y2);

    $pdf->SetLeftMargin($lmargin + 55);
    $pdf->SetY($y + 2);

    if ($fimg)
    {
      $y = $pdf->GetY();
      $pdf->Image($fimg, $lmargin, $y, $wimg, $himg);
      $pdf->SetY($y);
    }

    $nf = 0;
    $pdf->SetX($lmargin + 55);
    $p0 = $pdf->PageNo();
    $y0 = $pdf->GetY();
    foreach ($rec->get_caption()->get_fields() as $field)
    {
      /* @var $field caption_field */

      $pdf->SetFont(ZFONT, 'B', 12);
      $pdf->Write(5, $field->get_name() . " : ");

      $pdf->SetFont(ZFONT, '', 12);
      $pdf->Write(5, $field->get_value(true));

      $pdf->Write(6, "\n");
      $nf++;
    }
    if ($pdf->PageNo() == $p0 && ($pdf->GetY() - $y0) < $himg)
      $pdf->SetY($y0 + $himg);
    $ndoc++;
  }
  $pdf->SetLeftMargin($lmargin);
}

function print_preview(PDF &$pdf, &$lst, $withtdm=false)
{
  $appbox = appbox::get_instance();
  $registry = registry::get_instance();
  $user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
  global $printViewName;

  global $printlogosite, $presentationpage;

  if ($withtdm === true)
  {
    $tmppdf = new PDF("P", "mm", "A4");
    print_preview($tmppdf, $lst, "CALCPAGES"); // recursif pour calculer les no de page des previews

    print_thumbnailGrid($pdf, $lst, true);
  }

  foreach ($lst as $krec => $rec)
  {
    /* @var $rec record_adapter */

    $pdf->AddPage();

    if ($withtdm === "CALCPAGES")
    {
      if ($presentationpage)
        $rec->set_number($pdf->PageNo() + 1);
      else
        $rec->set_number($pdf->PageNo());
    }
    $lmargin = $pdf->GetX();
    $tmargin = $pdf->GetY();
    $himg = 0;
    $y = 0;
    $miniConv = NULL;

    $LEFT__TEXT = "";
    $LEFT__IMG = NULL;
    $RIGHT_TEXT = "";
    $RIGHT_IMG = NULL;

    $LEFT__IMG = $registry->get('GV_RootPath') . "config/minilogos/logopdf_" . $rec->get_sbas_id() . ".jpg";
    if (!is_file($LEFT__IMG))
    {
      $databox = $rec->get_databox();
      $str = $databox->get_sxml_structure();
      $vn = (string) ($str->pdfPrintLogo);
      if (($vn * 1) == 1)
      {
        $LEFT__TEXT = $databox->get_viewname();
      }
    }

    $collection = collection::get_from_base_id($rec->get_base_id());

    $vn = "";
    if ($str = simplexml_load_string($collection->get_prefs()))
      $vn = (string) ($str->pdfPrintappear);

    if ($vn == "" || $vn == "1")
    {
      $RIGHT_TEXT = phrasea::bas_names($rec->get_base_id());
    }
    elseif ($vn == "2")
    {
      $RIGHT_IMG = $registry->get('GV_RootPath') . "config/minilogos/" . $rec->get_base_id();
    }

    $xtmp = $pdf->GetX();
    $ytmp = $pdf->GetY();

    $pdf->SetFont(ZFONT, '', 12);
    $pdf->SetFillColor(220, 220, 220);
    $y = $pdf->GetY();
    $pdf->MultiCell(95, 7, $LEFT__TEXT, "LTB", "L", 1);
    $y2 = $pdf->GetY();
    $h = $y2 - $y;
    $pdf->SetY($y);
    $pdf->SetX(105);
    $pdf->Cell(95, $h, $RIGHT_TEXT, "TBR", 1, "R", 1);

    if ($LEFT__TEXT == "" && is_file($LEFT__IMG))
    {
      if ($size = @getimagesize($LEFT__IMG))
      {
        $wmm = (int) $size[0] * 25.4 / 72;
        $hmm = (int) $size[1] * 25.4 / 72;
        if ($hmm > 6)
        {
          $coeff = $hmm / 6;
          $wmm = (int) $wmm / $coeff;
          $hmm = (int) $hmm / $coeff;
        }
        $pdf->Image($LEFT__IMG, $xtmp + 0.5, $ytmp + 0.5, $wmm, $hmm);
      }
    }

    if ($RIGHT_IMG != NULL && is_file($RIGHT_IMG))
    {
      if ($size = @getimagesize($RIGHT_IMG))
      {

        if ($size[2] == '1')
        {
          if (!isset($miniConv[$RIGHT_IMG]))
          {
            $tmp_filename = tempnam('minilogos/', 'gif4fpdf');
            $img = imagecreatefromgif($RIGHT_IMG);
            imageinterlace($img, 0);
            imagepng($img, $tmp_filename);
            rename($tmp_filename, $tmp_filename . '.png');
            $miniConv[$RIGHT_IMG] = $tmp_filename . '.png';
            $RIGHT_IMG = $tmp_filename . '.png';
          }
          else
            $RIGHT_IMG = $miniConv[$RIGHT_IMG];

          $wmm = (int) $size[0] * 25.4 / 72;
          $hmm = (int) $size[1] * 25.4 / 72;
          if ($hmm > 6)
          {
            $coeff = $hmm / 6;
            $wmm = (int) $wmm / $coeff;
            $hmm = (int) $hmm / $coeff;
          }
          $tt = 0;
          if ($hmm < 6)
            $tt = (6 - $hmm) / 2;
          $pdf->Image($RIGHT_IMG, 200 - 0.5 - $wmm, $ytmp + 0.5 + $tt);
        }
        else
        {
          $wmm = (int) $size[0] * 25.4 / 72;
          $hmm = (int) $size[1] * 25.4 / 72;
          if ($hmm > 6)
          {
            $coeff = $hmm / 6;
            $wmm = (int) $wmm / $coeff;
            $hmm = (int) $hmm / $coeff;
          }
          $pdf->Image($RIGHT_IMG, 200 - 0.5 - $wmm, $ytmp + 0.5);
        }
      }
    }

    $y = $pdf->GetY() + 3;

    $subdef = $rec->get_subdef('preview');

    $f = $subdef->get_pathfile();

    if (!$user->ACL()->has_right_on_base($rec->get_base_id(), "nowatermark")
            && $subdef->get_type() == media_subdef::TYPE_IMAGE)
      $f = recordutils_image::watermark($rec->get_base_id(), $rec->get_record_id());

    $wimg = $himg = 150; // preview dans un carre de 150 mm
    if ($subdef->get_width() > 0 && $subdef->get_height() > 0)
    {
      if ($subdef->get_width() > $subdef->get_height())
        $himg = $wimg * $subdef->get_height() / $subdef->get_width();
      else
        $wimg = $himg * $subdef->get_width() / $subdef->get_height();
    }
    $pdf->Image($f, $lmargin, $y, $wimg, $himg);

    if ($miniConv != NULL)
    {
      foreach ($miniConv as $oneF)
        unlink($oneF);
    }
    $pdf->SetXY($lmargin, $y += ( $himg + 5));

    $nf = 0;
    foreach ($rec->get_caption()->get_fields() as $field)
    {
      /* @var $field caption_field */
      if ($nf > 0)
        $pdf->Write(6, "\n");

      $pdf->SetFont(ZFONT, 'B', 12);
      $pdf->Write(5, $field->get_name() . " : ");

      $pdf->SetFont(ZFONT, '', 12);
      $t = str_replace(array("&lt;", "&gt;", "&amp;"), array("<", ">", "&"), $field->get_value(true));
      $pdf->Write(5, $t);

      $nf++;
    }
  }

  return;
}

