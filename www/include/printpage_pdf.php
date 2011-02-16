<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require(GV_RootPath.'lib/tcpdf/tcpdf.php');

define('ZFONT', 'freesans');

###########################
###########################
# Pour Affichage du viewname dans le bandeau en haut a gauche
$printViewName	= FALSE ; // viewname = base
$printlogosite	= TRUE ;

###########################

$presentationpage = false;

$request = httpRequest::getInstance();
$parm = $request->get_parms('ACT'
					, 'form'
					, 'lay'
					, 'lst'
					, 'callclient'
					); // , "SSTTID", false);
					
$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		header('Location: /include/logout.php');
		exit();
	}
}
else{
	header("Location: /login/");
	exit();
}

if($parm['ACT']=='LOAD')
{	
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
	<base target="_self">
	<script type="text/javascript">
		function loaded()
		{
			self.focus();
			var w = window.dialogArguments ? window.dialogArguments : self.opener;
			if(w.document.forms['<?php echo $parm['form']?>'].lay.length == undefined)
			{
				document.forms[0].lay.value = w.document.forms['<?php echo $parm['form']?>'].lay.value;
			}
			else
			{
				for(i=0; i<w.document.forms['<?php echo $parm['form']?>'].lay.length; i++)
				{
					if(w.document.forms['<?php echo $parm['form']?>'].lay[i].checked)
					{
						document.forms[0].lay.value = w.document.forms['<?php echo $parm['form']?>'].lay[i].value;
						break;
					}
				}
			}
			document.forms[0].lst.value = w.document.forms['<?php echo $parm['form']?>'].lst.value;
			<?php
			if($parm['callclient']!='1')
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
			<input type="hidden" name="ses" value="<?php echo $ses_id?>" />
			<input type="hidden" name="lng" value="<?php echo $lng?>" />
			<input type="hidden" name="usr" value="<?php echo $usr_id?>" />
			<input type="hidden" name="lay" value="??" />
			<input type="hidden" name="lst" value="??" />
		</form>
	</body>
</html>
<?php
	die();
}		// $parm['ACT']=='LOAD'



// ------- ici si ACT=PRINT

class PDF extends TCPDF
{
	function Header()
	{
	}
	
	function Footer()
	{
//		$ml = $this->lMargin;
//		$mr = $this->rMargin;

		$ml = $this->SetLeftMargin(0);
		$mr = $this->SetRightMargin(0);
		
	    //Positionnement e 1,5 cm du bas
	    $this->SetY(-15);
	    //Police Arial italique 8
	    $this->SetFont(ZFONT, 'I', 8);
	    //Numero de page centre
	    $this->Cell(0, 10,'Page '.$this->PageNo(), 0, 0, 'C');
		
	    $this->SetFont(ZFONT, '', 8);
	    $w  = $this->GetStringWidth('Printed by');
	    $this->SetFont(ZFONT, 'B', 8);
	    $w += $this->GetStringWidth(' Phraseanet IV');
			
		// $this->SetXY(-$this->lMargin, -15);
		$this->SetXY(-$w-$mr-5, -15);
			
	    $this->SetFont(ZFONT, '', 8);
	    $this->Write(8, 'Printed by');
	    $this->SetFont(ZFONT, 'B', 8);
	    $this->Write(8,' Phraseanet IV');

//		$this->lMargin = $ml;
//		$this->rMargin = $mr;
	}
} 


// les variables
$tot_record = 0;
$tot_hd = 0;
$tot_prev = 0;	

$ph_session = null;


$regid = NULL;
$printReg = FALSE ;
$child=0;

$bases = array();
$colls = array();

if(!($ph_session = phrasea_open_session($ses_id, $usr_id)))
{
	die();
}

$usrRight = null;
$conn = connection::getInstance();

	$sql = "SELECT base_id,needwatermark FROM (usr natural join basusr ) WHERE usr.usr_id='" . $conn->escape_string($usr_id) ."'";
	 
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row;
		}	
		$conn->free_result($rs);
	}

$tsbas = array();

foreach($ph_session["bases"] as $kbase=>$base)
{
	$ph_session["bases"][$kbase]['_ftitle'] = null;
	if($sxstruct = simplexml_load_string($base['xmlstruct']))
	{
		foreach($sxstruct->description->children() as $fn=>$f)
		{
			if($f['thumbTitle']=='1')
			{
				$ph_session["bases"][$kbase]['_ftitle'] = $fn;
			}
		}
	}
	
	$bases["b".$base["base_id"]] = $kbase;
	
	foreach($base["collections"] as $kcoll=>$coll)
	{
		$colls["c".$coll["base_id"]] = array("b"=>$kbase, "c"=>$kcoll);
		
		$tsbas['b'.$coll["base_id"]] = &$ph_session["bases"][$kbase];
	}	
}


$pdf = null;
$lst = explode(";", $parm["lst"]);
if(count($lst > 0))
{
	foreach($lst as $k=>$basrec)
	{
		if(count($basrec = explode("_", $basrec))==2)
		{
			$lst[$k] = array("bid"=>$basrec[0], "rid"=>$basrec[1]);
			
			$coll_id = $basrec[0];
			
			answer::logEvent(phrasea::sbasFromBas($basrec[0]),$basrec[1],'print',$parm["lay"],'');
		}
		else
		{
			unset($lst[$k]);
		}
	}
	

	
	$pdf = new PDF("P", "mm", "A4", true, 'UTF-8', false);
	
//	if(file_exists($GV_imgFirstPagePrint))
//	{
//		if( $size = @getimagesize($GV_imgFirstPagePrint) )
//		{
//			$pdf->AddPage();
//			$presentationpage = true ;
//			$wmm = (int)$size[0]*25.4/72;
//			$hmm = (int)$size[1]*25.4/72;
//			if($wmm>190)
//			{
//				$coeff = $wmm/190;
//				$wmm = (int)$wmm/$coeff;
//				$hmm = (int)$hmm/$coeff;
//			}
//			if($hmm>270)
//			{
//				$coeff = $hmm/270;
//				$wmm = (int)$wmm/$coeff;
//				$hmm = (int)$hmm/$coeff;
//			}
//			$top  = (150-($hmm/2))/2 ;
//			
//			$left = (105-($wmm/2)) ;
//			$pdf->Image($GV_imgFirstPagePrint, $left , $top , $wmm , $hmm );
//		} 
//	}
	switch($parm["lay"])
	{
		case "preview":
			getObjectAndCaption($lst, "preview", false);
			print_preview($pdf, $lst);
			break;
		case "previewCaption":
			getObjectAndCaption($lst, "preview", true);
			print_preview($pdf, $lst);
			break;
		case "previewCaptionTdm":
			getObjectAndCaption($lst, "preview", true); 
			print_preview($pdf, $lst, true);
			break;
		case "thumbnailList":
	  		getObjectAndCaption($lst, "thumbnail", true);
	  		print_thumbnailList($pdf, $lst);
	  		break;
		case "thumbnailGrid":
			getObjectAndCaption($lst, "thumbnail", false);
			print_thumbnailGrid($pdf, $lst);
			break;
		default:
			$pdf = null;
			break; 	
	}
}







if(!$pdf)
{
	$pdf = new PDF("P", "mm", "A4");
	$pdf->AddPage();
	$pdf->SetFont(ZFONT,'B',16);
	$pdf->Cell(40,10,'Rien � imprimer !');
}
$pdf->SetAuthor("Phraseanet IV by Alchemy");
$pdf->SetTitle("Phraseanet IV print");
$pdf->SetDisplayMode("fullpage", "single");
$pdf->Close();
$pdf->Output();


function print_thumbnailGrid(&$pdf, &$lst, $withlinks=false)
{
  global $ph_session, $bases, $colls;

	$NDiapoW = 3;
	$NDiapoH = 4;

	$pdf->AddPage();
	
	$oldMargins = $pdf->getMargins();
/*	
	$lmargin = $pdf->lMargin;
	$rmargin = $pdf->rMargin;
	$tmargin = $pdf->tMargin;
	$bmargin = $pdf->bMargin;
*/
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
	$ImgSize = min($DiapoW, ($DiapoH-$TitleH))-5;

	$npages = ceil(count($lst) / ($NDiapoW*$NDiapoH));
	
	$irow = $ipage = 0;
	$icol = -1;
	foreach($lst as $rec)
	{
		if(++$icol >= $NDiapoW)
		{
			$icol = 0;
			if(++$irow >= $NDiapoH)
			{
				$irow = 0;
				$ipage++;
				$pdf->AddPage();
			}
		}
		$fimg = null;
		$himg = 0;
		
		$preview = "preview";
		if(isset($rec["sd"]["thumbnailGIF"]))
			$preview = "thumbnail";
			
		if($rec["sd"])
		{
 			if(isset($rec["sd"][$preview]))
 			{
 				$fimg = $rec["sd"][$preview]["path"] . $rec["sd"][$preview]["file"];
				$wimg = $himg = $ImgSize; // thumbnail dans un carre 
				if($rec["sd"][$preview]["height"]>0 && $rec["sd"][$preview]["width"]>0)
				{
					if($rec["sd"][$preview]["width"] > $rec["sd"][$preview]["height"])
						$himg = $wimg * $rec["sd"][$preview]["height"] / $rec["sd"][$preview]["width"];
					else
						$wimg = $himg * $rec["sd"][$preview]["width"] / $rec["sd"][$preview]["height"];
				}
 			}
		}

		if($fimg)
		{
			$x = $lmargin+($icol*$DiapoW);
			$y = $tmargin+($irow*$DiapoH);
			$pdf->SetDrawColor(0);
			$pdf->Rect($x, $y, $DiapoW, $DiapoH, "D");
			// $pdf->Rect($x, $y, $DiapoW, $TitleH, "D");
			
			$pdf->SetXY($x, $y+1);
			$pdf->SetFont(ZFONT, '', 10);
			$t = $irow . '-' . $x;
			$t = $rec['_title'];
			//$pdf->SetFillColor(255,0,0);
			$pdf->MultiCell($DiapoW, $TitleH, $t, '0', 'C', false);

			if($withlinks)
			{
				$lk = $pdf->AddLink();
				$pdf->SetLink($lk, 0, $npages+$rec["pdfpage"]);
				$pdf->Image($fimg, $x+(($DiapoW-$wimg)/2), $TitleH+$y+(($DiapoH-$TitleH-$himg)/2), $wimg, $himg, null, $lk);
			}
			else
			{
				$pdf->Image($fimg, $x+(($DiapoW-$wimg)/2), $TitleH+$y+(($DiapoH-$TitleH-$himg)/2), $wimg, $himg);
			}
		}
	}
	$pdf->SetLeftMargin($oldMargins['left']);
/*	
	$pdf->lMargin = $lmargin;
	$pdf->rMargin = $rmargin;
	$pdf->tMargin = $tmargin;
	$pdf->bMargin = $bmargin;
*/
}


function print_thumbnailList(&$pdf, &$lst)
{
  global $ph_session, $bases, $colls;

	$pdf->AddPage();
	$oldMargins = $pdf->getMargins();
/*	
	$lmargin = $pdf->lMargin;
	$rmargin = $pdf->rMargin;
	$tmargin = $pdf->tMargin;
	$bmargin = $pdf->bMargin;
*/
	$tmargin = $oldMargins['top'];
	$lmargin = $oldMargins['left'];
	$bmargin = $oldMargins['bottom'];
	$rmargin = $oldMargins['right'];

	$pdf->SetLeftMargin($lmargin + 55);

	$ndoc = 0;
	$lastpage = $pdf->PageNo();
	foreach($lst as $rec)
	{
		$fimg = null;
		$himg = 0;
		
		$preview = "preview";
		if(isset($rec["sd"]["thumbnailGIF"]))
			$preview = "thumbnail";
		
		if($rec["sd"])
		{
 			if(isset($rec["sd"][$preview]))
 			{
 				$fimg = $rec["sd"][$preview]["path"] . $rec["sd"][$preview]["file"];
				$wimg = $himg = 50; // thumbnail dans un carre de 50 mm
				if($rec["sd"][$preview]["height"]>0 && $rec["sd"][$preview]["height"]>0)
				{
					if($rec["sd"][$preview]["width"] > $rec["sd"][$preview]["height"])
						$himg = $wimg * $rec["sd"][$preview]["height"] / $rec["sd"][$preview]["width"];
					else
						$wimg = $himg * $rec["sd"][$preview]["width"] / $rec["sd"][$preview]["height"];
				}
 			}
		}
		
		if($pdf->GetY() > $pdf->fh - (6 + $himg + 20))
			$pdf->AddPage();
			
		$sxe = null;
		$title = "";
		if(isset($rec["xml"]) && $sxe = simplexml_load_string($rec["xml"]))
		{
			$title = "record : " . $rec["rid"];
		}
					
		// on affiche le nom de la collection
		$y = $pdf->GetY();
		if(isset($colls["c" . $rec["bid"]]))
		{
			$coll = $colls["c" . $rec["bid"]];
			$t = $ph_session["bases"][$coll["b"]]["collections"][$coll["c"]]["name"];
			$pdf->SetFont(ZFONT,'',10);
			$pdf->SetFillColor(220, 220, 220);
			$pdf->SetLeftMargin($lmargin);
			$pdf->SetRightMargin($rmargin);
			$pdf->SetX($lmargin);
			$pdf->SetY($y);

			if($title)
			{
				$pdf->out = false;
				$pdf->MultiCell(140 , 4, $title, "LTR", "L", 1);
				$y2 = $pdf->GetY();
				$h = $y2-$y;
				$pdf->out = true;
				$pdf->SetX($lmargin);
				$pdf->SetY($y);
				$pdf->Cell(0, $h, "", "LTR", 1, "R", 1);
				$pdf->SetX($lmargin);
				$pdf->SetY($y);
				$pdf->Cell(0, 4, $t, "", 1, "R");
				$pdf->SetX($lmargin);
				$pdf->SetY($y);
				$pdf->MultiCell(140 , 4, $title, "", "L");
				$pdf->SetX($lmargin);
				$pdf->SetY($y = $y2);
			}
			else
			{
				$pdf->SetX($lmargin);
				$pdf->Cell(0, 4, $t, "LTR", 1, "R", 1);
				$y = $pdf->GetY();
			}
		
			$pdf->SetLeftMargin($lmargin + 55);
		}
		$pdf->SetY($y+2);
		
		if($fimg)
		{
			$y = $pdf->GetY();
			$pdf->Image($fimg, $lmargin, $y, $wimg, $himg);
			$pdf->SetY($y);
		}
		
		if($sxe)
		{
			$nf = 0;
			$pdf->SetX($lmargin + 55);
			$p0 = $pdf->PageNo();
			$y0 = $pdf->GetY();
			foreach($sxe->description->children() as $fn=>$fv)
			{
				// if($nf > 0)
					
				$pdf->SetFont(ZFONT,'B',12);
				$pdf->Write(5, (string)$fn . " : ");

				$pdf->SetFont(ZFONT,'',12);
				$t = trim((string)$fv);
				$pdf->Write(5, $t);
				
				$pdf->Write(6, "\n");
				$nf++;
			}
			if($pdf->PageNo()==$p0 && ($pdf->GetY()-$y0)<$himg)
				$pdf->SetY($y0+$himg);
		}
		$ndoc++;
	}
	$pdf->SetLeftMargin($lmargin);
}

function print_preview(&$pdf, &$lst, $withtdm=false)
{
  global $ph_session, $bases, $colls,$printViewName;

  global $printlogosite,$presentationpage;

	if($withtdm===true)
	{
		$tmppdf = new PDF("P", "mm", "A4");
		print_preview($tmppdf, $lst, "CALCPAGES");	// recursif pour calculer les no de page des previews
		
		print_thumbnailGrid($pdf, $lst, true);
	}
	foreach($lst as $krec=>$rec)
	{
		$pdf->AddPage();
		
		if($withtdm === "CALCPAGES")
		{
			if($presentationpage)
				$lst[$krec]["pdfpage"] = $pdf->PageNo() + 1;
			else 	
				$lst[$krec]["pdfpage"] = $pdf->PageNo();
		}
		$lmargin = $pdf->GetX();
		$tmargin = $pdf->GetY();
		$himg = 0;
		$y = 0 ;
		$miniConv = NULL;
			
		if($rec["sd"])
		{
			$LEFT__TEXT = "" ;	$LEFT__IMG  = NULL ;
			$RIGHT_TEXT = "" ;	$RIGHT_IMG  = NULL ;
			
			$LEFT__IMG = GV_RootPath."config/minilogos/logopdf_".phrasea::sbasFromBas($rec["bid"]).".jpg";			
			if(!is_file($LEFT__IMG))
			{
				$coll = $colls["c" . $rec["bid"]];
				$t = $ph_session["bases"][$coll["b"]]["xmlstruct"] ;
				if($str =  simplexml_load_string($t))
					$vn = (string)($str->pdfPrintLogo);		
				if(($vn*1)==1)
				{
					// on calcule le text de gauche (sbas viewname)
					if(isset($colls["c" . $rec["bid"]]))
					{
						$coll = $colls["c" . $rec["bid"]];
						$LEFT__TEXT = $ph_session["bases"][$coll["b"]]["viewname"];
					}
				}				
			}
			
			// pour entete de droite
			$coll = $colls["c" . $rec["bid"]];
			$prefs = $ph_session["bases"][$coll["b"]]["collections"][$coll["c"]]["prefs"];
			$vn="";
			if($str =  simplexml_load_string($prefs))
				$vn = (string)($str->pdfPrintappear);		
			if($vn=="" || $vn=="1")			
			{
				if(isset($colls["c" . $rec["bid"]]))
				{
					$coll = $colls["c" . $rec["bid"]];
					$RIGHT_TEXT = $ph_session["bases"][$coll["b"]]["collections"][$coll["c"]]["name"];
				}
			}
			elseif($vn=="2")
			{
				$RIGHT_IMG =  GV_RootPath."config/minilogos/".$rec["bid"] ;				
			}
			
			$xtmp = $pdf->GetX();
			$ytmp = $pdf->GetY();
			
			$pdf->SetFont(ZFONT,'',12);
			$pdf->SetFillColor(220, 220, 220);				
			$y = $pdf->GetY();
			$pdf->MultiCell(95 , 7, $LEFT__TEXT, "LTB", "L", 1);
			$y2 = $pdf->GetY();
			$h = $y2-$y;
			$pdf->SetY($y);
			$pdf->SetX(105);
			$pdf->Cell(95, $h, $RIGHT_TEXT , "TBR", 1, "R", 1);
			
			if($LEFT__TEXT=="" && is_file($LEFT__IMG) )
			{
				if( $size = @getimagesize($LEFT__IMG) )
				{
					$wmm = (int)$size[0]*25.4/72;
					$hmm = (int)$size[1]*25.4/72;					 
					if($hmm>6)
					{
						$coeff = $hmm/6;
						$wmm = (int)$wmm/$coeff;
						$hmm = (int)$hmm/$coeff;
					}
					$pdf->Image($LEFT__IMG, $xtmp+0.5, $ytmp+0.5 , $wmm , $hmm );
				}	
			}
			
			if($RIGHT_IMG!=NULL && is_file($RIGHT_IMG))
			{
				if( $size = @getimagesize($RIGHT_IMG) )
				{
					 
					if ($size[2] == '1') 
					{ 
						if( !isset($miniConv[$RIGHT_IMG]) )
						{
							$tmp_filename = tempnam('minilogos/', 'gif4fpdf' ); 
							$img = imagecreatefromgif($RIGHT_IMG); 
							imageinterlace($img,0); 						
							imagepng($img, $tmp_filename); 
							rename($tmp_filename, $tmp_filename.'.png'); 
							$miniConv[$RIGHT_IMG] = $tmp_filename.'.png';
							$RIGHT_IMG = $tmp_filename.'.png';  
							
						}
						else
							$RIGHT_IMG = $miniConv[$RIGHT_IMG];  
						
						$wmm = (int)$size[0]*25.4/72;
						$hmm = (int)$size[1]*25.4/72;					 
						if($hmm>6)
						{
							$coeff = $hmm/6;
							$wmm = (int)$wmm/$coeff;
							$hmm = (int)$hmm/$coeff;
						} 
						$tt = 0;
						if($hmm<6)
							$tt = (6-$hmm)/2;
						$pdf->Image($RIGHT_IMG, 200-0.5-$wmm, $ytmp+0.5+$tt );
						 
					} 
					else 
					{
						$wmm = (int)$size[0]*25.4/72;
						$hmm = (int)$size[1]*25.4/72;					 
						if($hmm>6)
						{
							$coeff = $hmm/6;
							$wmm = (int)$wmm/$coeff;
							$hmm = (int)$hmm/$coeff;
						}  
						$pdf->Image($RIGHT_IMG, 200-0.5-$wmm, $ytmp+0.5 );						 
					}
				}	
			}
			
			$y = $pdf->GetY() + 3;
			
			$preview = "preview";
			if(isset($rec["sd"]["thumbnailGIF"]))
				$preview = "thumbnail";
			
			if(isset($rec["sd"][$preview]))
			{
				$f = $rec["sd"][$preview]["path"] . $rec["sd"][$preview]["file"];
				
				$wimg = $himg = 150; // preview dans un carre de 150 mm
				if($rec["sd"][$preview]["height"]>0 && $rec["sd"][$preview]["width"]>0)
				{
					if($rec["sd"][$preview]["width"] > $rec["sd"][$preview]["height"])
						$himg = $wimg * $rec["sd"][$preview]["height"] / $rec["sd"][$preview]["width"];
					else
						$wimg = $himg * $rec["sd"][$preview]["width"] / $rec["sd"][$preview]["height"];
				}
				$pdf->Image($f, $lmargin, $y, $wimg, $himg);
			}
			else
			{
				if(isset($rec["sd"]["thumbnail"]))
				{
					$f = $rec["sd"]["thumbnail"]["path"] . $rec["sd"]["thumbnail"]["file"];
					$wimg = $rec["sd"]["thumbnail"]["width"] / $pdf->k;
					$himg = $rec["sd"]["thumbnail"]["height"] / $pdf->k;
					
					$pdf->Image($f, $lmargin, $y);
				}
			}
		}
		if( $miniConv!=NULL )
		{
			foreach($miniConv as $oneF)
				unlink($oneF);
		}
		$pdf->SetXY($lmargin, $y += ($himg + 5));
		
		if(isset($rec["xml"]) && $sxe = simplexml_load_string($rec["xml"]))
		{
			$nf = 0;
			foreach($sxe->description->children() as $fn=>$fv)
			{
				if($nf > 0)
					$pdf->Write(6, "\n");
					
				$pdf->SetFont(ZFONT,'B',12);
				$pdf->Write(5, (string)$fn . " : ");

				$pdf->SetFont(ZFONT,'',12);
				$t = str_replace(array("&lt;", "&gt;", "&amp;"), array("<", ">", "&"), trim((string)$fv));
				$pdf->Write(5, $t);
				
				$nf++;
			}
		}
	}
	return;
}


function getObjectAndCaption(&$lst, $obj, $getCaption)
{
  // global $ph_session;
  global $parm;
  global $usrRight;
  global $ph_session;
  global $tsbas;
  global $ses_id, $usr_id;
	foreach($lst as $krec=>$basrec)
	{
		$lst[$krec]['_title'] = 'record: ' . $basrec["rid"];
		$bid = $basrec["bid"];
		$caption = phrasea_xmlcaption($ses_id, $bid, $basrec["rid"]);
		if($getCaption)
			$lst[$krec]["xml"] = $caption;
		
		if( ($sxcaption = simplexml_load_string($caption)) )
		{
			if(($ftitle = $tsbas['b'.$bid]['_ftitle']) != null)
				$lst[$krec]['_title'] = (string)($sxcaption->description->{$ftitle});
			else
				$lst[$krec]['_title'] = (string)($sxcaption->doc['originalname']);
		}
				
			
		$lst[$krec]["sd"] = phrasea_subdefs($ses_id, $basrec["bid"], $basrec["rid"]);
		
		if(! isset($usrRight[ $basrec["bid"]]["needwatermark"]) || $usrRight[ $basrec["bid"]]["needwatermark"]=="1")
		{
			$path = p4string::addEndSlash($lst[$krec]["sd"]["preview"]["path"]) .  $lst[$krec]["sd"]["preview"]["file"];
			
			$lst[$krec]["sd"]["preview"]["path"] = $path ;
			$lst[$krec]["sd"]["preview"]["file"] = '';
		}
	}
}
?>