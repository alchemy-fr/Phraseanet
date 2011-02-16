<?php
$debug = false;
////////////////////////////////////////// STAMPER !!! /////////////////////////////////////////////
function stamper(&$baseprefs, &$collprefs, &$prop, &$xml, $docidx)
{
  global $debug, $flog,$parm;

	//fwrite($flog, sprintf("into stamper\n"));

	if(!isset($collprefs->stamp))
		return;
		
	
	putenv('GDFONTPATH=' . realpath('./stamper/'));
	$font = 'arial';

	$sxml = simplexml_load_string($xml["xml"]);

	foreach($prop["docs"] as $k=>$doc)
	{
		printf("hotfolderfile : %s\n", $doc["hotfolderfile"]);
		if($imag_original = @imagecreatefromjpeg($doc["hotfolderfile"]))
		{
			imageantialias($imag_original, false);
			
			$larg_act = imagesx($imag_original);
			$haut_act = imagesy($imag_original);
			
			if($larg_act>$haut_act)
			{
				$larg = $larg_act;
				$haut = $haut_act;
				$rot = false;
			}
			else
			{
				$larg = $haut_act;
				$haut = $larg_act;
				$rot = true;
			}
		
			if(($larg > 200) && ($txtim = imagecreatetruecolor($larg, $maxtxth=$haut)))	// le bandeau ne peut �tre plus 'haut' que l'image !
			{
				imageantialias($txtim, false);
				
				// Create some colors
				$white = imagecolorallocate($txtim, 255, 255, 255);
				$black = imagecolorallocate($txtim, 0, 0, 0);
				$red = imagecolorallocate($txtim, 255, 0, 0);
				
				$rvb = array(255,255,255);
				if(isset($collprefs->stamp["color"]))
				{
					$zzz = explode(";", $collprefs->stamp["color"]);
					if(count($zzz)==3 && 0+$zzz[0]>=0 && 0+$zzz[0]<=255 && 0+$zzz[1]>=0 && 0+$zzz[1]<=255 && 0+$zzz[2]>=0 && 0+$zzz[2]<=255)
						$rvb = $zzz;
				}
				$bkcolor = imagecolorallocate($txtim, 0+$rvb[0], 0+$rvb[1], 0+$rvb[2]);
				
				
				imagefilledrectangle($txtim, 0, 0, $larg, $maxtxth, $bkcolor);
				
				$line_top = 0;
				$textsize = 10;
				foreach($collprefs->stamp->line as $stampline)
				{
					if($line_top >= $maxtxth)	// �a deborde du bandeau !
						break;
						
					// pour chaque ligne de stamp, on calcule la hauteur max pour connaitre
					// la ligne de base pour la premiere ligne
					$maxtextsize = 4;
					foreach($stampline->field as $stampfield)
					{
						if(!isset($stampfield["content"]))
							continue;
						if(isset($stampfield["size"]))
						{
							$textsize =  (int)((string)$stampfield["size"]);
							if($textsize > $maxtextsize)
								$maxtextsize = $textsize;
						}
					}
					if($maxtextsize > 48)
						$maxtextsize = 48;
					$wsize = imagettfbbox($maxtextsize, 0, "arial", "qdfT");
					$stamplineheight = $wsize[1] - $wsize[7];


					$line_bottom = $line_top;
					$last_right = "0";
					foreach($stampline->field as $stampfield)
					{
						if(!isset($stampfield["content"]))
							continue;
							
						$left  = isset($stampfield["left"])  ? ((string)$stampfield["left"])  : $last_right;
						$right = isset($stampfield["right"]) ? ((string)$stampfield["right"]) : (string)$larg;
						if(isset($stampfield["size"]))
							$textsize =  (int)((string)$stampfield["size"]);
						if($textsize < 4)
							$textsize = 4;
						if($textsize > 48)
							$textsize = 48;
						
// printf("field : [%s ; %s] (%s)\n", $left, $right, (string)$stampfield["content"]);

						if(substr($left, -1, 1)=="%")
							$left = ($larg * (int)$left)/100;
						else
							$left = (int)$left;
						if($left < 0)						
							$left = $larg+$left;
						
						if(substr($right, -1, 1)=="%")
							$right = ($larg * (int)$right)/100;
						else
							$right = (int)$right;
						if($right < 0)						
							$right = $larg+$right;
							
						if($right > $left)
						{
							$text = (string)$stampfield["content"];
							if(substr($text, 0, 1) == "@")
							{
								if($sxml)
								{
									if($x = $sxml->xpath(substr($text, 1)))
									{
//printf("xpath:%s\n", $text);
//var_dump($x);
										$text = "";
										foreach($x as $w)
											$text .= ($text?" ; ":"") . (string)$w;
									}
									else
									{
										$text = "";
									}
								}
								else
								{
									$text = "";
								}
							}
							$box = just($txtim, $left, $line_top, $right-$left, $textsize, "arial", $black, $text, $stamplineheight);
							// imagerectangle($txtim, $box["left"], $box["top"], $box["right"], $box["bottom"], $red);
							if($box["bottom"] > $line_bottom)
								$line_bottom = $box["bottom"];
								
							$last_right = (string)$box["right"];
						}
					}
					$line_top = $line_bottom + 1;
				}
				$htext = $line_top;
				
				if(isset($collprefs->stamp->logo))
				{
					$xlogo = isset($collprefs->stamp->logo["x"]) ? (int)(string)($collprefs->stamp->logo["x"]) : 0;
					$ylogo = isset($collprefs->stamp->logo["y"]) ? (int)(string)($collprefs->stamp->logo["y"]) : 0;
					
					$file = GV_RootPath."config/minilogos/stamp_" . $baseprefs["id"] . "";
					if(!is_file($file))
						$file = GV_RootPath."config/minilogos/stamp_" . $parm["bid"] . "";
						
					$logo = NULL;
					if(($tmp = getimagesize($file)) !== false)
					{
						if($tmp[2]==1)	// GIF
							$logo = imagecreatefromgif($file);
						elseif($tmp[2]==3) // PNG
							$logo = imagecreatefrompng ($file);
						else 
							$logo = imagecreatefromjpeg($file);
					}					
					if($logo)
					{		
						imagecopy($txtim, $logo, $xlogo, $ylogo, 0, 0, imagesx($logo), imagesy($logo));
						if(imagesy($logo) > $htext)
							$htext = imagesy($logo);
						imagedestroy($logo);
					}
				}
				
				if($htext > $maxtxth)
					$htext = $maxtxth;
				$im = null;
				if($rot)
				{
					if($debug)
						print("!!!!!!!!!!!!!!!!! rotate !!!!!!!!!!!!!!!!!!!!!<br>\n");
					$txtim = imagerotate($txtim, 90, $white);
					if($im = imagecreatetruecolor($larg_act + $htext, $haut_act))
					{
						imageantialias($im, false);
						imagecopy($im, $imag_original, 0, 0, 0, 0, $larg_act, $haut_act);
						imagecopy($im, $txtim, $larg_act, 0, 0, 0, $htext, $haut_act);
					}
				}
				else
				{
					if($im = imagecreatetruecolor($larg_act, $haut_act + $htext))
					{
						imageantialias($im, false);
						imagecopy($im, $imag_original, 0, 0, 0, 0, $larg_act, $haut_act);
						imagecopy($im, $txtim, 0, $haut_act, 0, 0, $larg_act, $htext);
					}
				}
				
				$quality = 80;
				if(isset($collprefs->stamp["quality"]))
					$quality = (int)((string)$collprefs->stamp["quality"]);
				if($quality < 0)
					$quality = 0;
				else
					if($quality > 100)
						$quality = 100;
				if(!$debug && $im)
					imagejpeg($im, $doc["hotfolderfile"], $quality);
		
				imagedestroy($txtim);
				
				if($im)
					imagedestroy($im);
			}
			imagedestroy($imag_original);
		}
	}
}

function errlog($msg)
{
  global $flog;
	$d = debug_backtrace();
	fwrite($flog, sprintf("file:%s, function:%s, line:%d\n : %s\n", $d[0]["file"], $d[1]["function"], $d[0]["line"], $msg));		
}

function just(&$im, $x0, $y0, $maxwidth, $size, $font, &$color, &$text, $stamplineheight)
{
  global $debug,$flog;

	$text = explode("\n", $text);

	// les dim de la box finale
	$xmin = $ymin = 99999;
	$xmax = $ymax = 0;

	// on calcule l'interlignage
	$wsize = imagettfbbox($size, 0, $font, "qdfT");
	$interlig = ceil(1.2*($wsize[1] - $wsize[7]));

	$y = 0;
	$ilig = 0;
	$maxh = 0;
	foreach($text as $lig)
	{
		if($debug)
			printf("<br>\nLIG : '%s'<br>\n<br>\n", $lig);
		$lig = explode(" ", $lig);
		$s = "";
		$x = 0;
		for($iword=0; $iword<count($lig); $iword++)
		{
			$word = ($iword>0 ? " ":"") . $lig[$iword];
			$wsize = imagettfbbox($size, 0, $font, $word);
			$ww = ($wsize[2]-$wsize[0]);
			$wh = ($wsize[1] - $wsize[7]);
			if($debug)
			{
				printf("X=%d ; Y=%d<br>\n'%s' :  ", $x, $y, $word);
//				foreach($wsize as $z)
//					printf(" %d ", $z);
				printf("   w=%d ; h=%d<br>\n", $ww, $wh );
			}
			if($x + $ww <= $maxwidth)
			{
				// le mot tient
				$s .= $word;
				$x += $ww;
				if($wh > $maxh)
					$maxh = $wh;
			}
			else
			{
				// le mot deborde, on print la ligne precedente
				if($s)
				{
					if($debug)
						printf("print lig %d : '%s'<br>\n<br>\n", $ilig,$s);
					
					if($ilig==0)
					{
						// la premiere ligne est en haut, sans interlignage
						$y = $stamplineheight;
					}
					
					$wsize = imagettftext($im, $size, 0, $x0, $y0+$y, $color, $font, $s);
					
					if($wsize[6] < $xmin)
						$xmin = $wsize[6];
					if($wsize[7] < $ymin)
						$ymin = $wsize[7];
					if($wsize[2] > $xmax)
						$xmax = $wsize[2];
					if($wsize[3] > $ymax)
						$ymax = $wsize[3];
					
					$y += $interlig;
		
					$ilig++;
				}
				
				// debute la ligne suivante
				$s = $lig[$iword];	// pas d'espace en debut de ligne : il faut recalculer
				$wsize = imagettfbbox($size, 0, $font, $s);
				$x = ($wsize[2]-$wsize[0]);
			}
		}
		// on print la derniere ligne
		if($s)
		{
			if($debug)
				printf("print lig %d : '%s'<br>\n<br>\n", $ilig, $s);

			if($ilig==0)
			{
				// la premiere ligne est en haut, sans interlignage
				$y = $stamplineheight;
			}
			$wsize = imagettftext($im, $size, 0, $x0, $y0+$y, $color, $font, $s);
			$s = "";
			
			if($wsize[6] < $xmin)
				$xmin = $wsize[6];
			if($wsize[7] < $ymin)
				$ymin = $wsize[7];
			if($wsize[2] > $xmax)
				$xmax = $wsize[2];
			if($wsize[3] > $ymax)
				$ymax = $wsize[3];
				
			$y += $interlig;

			$ilig++;
		}
		
	}
	return(array("left"=>$xmin, "top"=>$ymin, "right"=>$xmax, "bottom"=>$ymax));
}


/////////////////////////////////////////////////////////////////////////////////////////////////////

?>