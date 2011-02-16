<?php
class record_image extends record
{
	private function wrap($fontSize, $angle, $fontFace, $string, $width)
	{
		$ret = array();
		$testbox = imagettfbbox($fontSize, $angle, $fontFace, 'Op');	// str 'Op' used to calculate linespace
		$height = abs($testbox[1] - ($dy = $testbox[7]));
		
		foreach(explode("\n", $string) as $lig)
		{
			if($lig == '')
			{
				$ret[] = '';
			}
			else
			{
				$buff = '';				
				foreach(explode(' ', $lig) as $wrd)
				{
					$test = $buff . ($buff?' ':'') . $wrd;
					$testbox = imagettfbbox($fontSize, $angle, $fontFace, $test);
					if( abs($testbox[2]-$testbox[0]) > $width )
					{
						if($buff=='')
						{
							$ret[] = $test;
						}
						else
						{
							$ret[] = $buff;
							$buff = $wrd;
						}
					}
					else
					{
						$buff = $test;
					}
				}
				if($buff != '')
					$ret[] = $buff;
			}
		}
		return(array('l'=>$ret, 'h'=>$height, 'dy'=>$dy));
	}
	
	
	public static function stamp($bas, $rec, $hd=false)
	{
		$debug = false;
//		define('STAMPLOG', '/home/gaulier/workspace/v22/tmp/stamp.log');
//		define('STAMPLOG', '/home/gaulier/Documents/exports/stamp.log');
		
//		if($debug)
//			file_put_contents(STAMPLOG, "");
		
		if(GV_imagick == '' )
			return false;

		$conn = connection::getInstance();
		
		$ph_session = phrasea::bases();
		
		$sbas_id = phrasea::sbasFromBas($bas);
		
		if(!isset($sbas_id))
			return false;
			
		$connSbas = connection::getInstance($sbas_id);
		
		if(!$connSbas)
			return false;
		
		$sdname = 'preview';
		if($hd)
			$sdname = 'document';
		
		$sql = "SELECT path, file, mime, type, xml, prefs FROM subdef s, record r, coll c WHERE r.record_id='".$connSbas->escape_string($rec)."' AND r.record_id = s.record_id AND name='".$connSbas->escape_string($sdname)."' AND c.coll_id=r.coll_id";
		
		$sxprefs = $sxxml = $domprefs = FALSE;
		if($rs = $connSbas->query($sql))
		{
			if($row = $connSbas->fetch_assoc($rs))
			{
				
				$domprefs = new DOMDocument();
				if( !($domprefs->loadXML($row['prefs'])) )
					$domprefs = FALSE;
					
				$sxxml    = simplexml_load_string($row['xml']);
				$file = array(
						'type'=>$row['type']
						,'path'=>p4string::addEndSlash($row['path'])
						,'file'=>$row['file']
						,'mime'=>$row['mime']
					);
			}
			$connSbas->free_result($rs);
		}
		
		if($domprefs===FALSE || $sxxml===FALSE)
			return false;
			
		$xpprefs = new DOMXPath($domprefs);
		
		$pathIn       = $file['path'].$file['file'];
		$pathOut      = $file['path'].'stamp_'.$file['file'];
		$pathTmpStamp = GV_RootPath.'tmp/'.time().'-stamptmp_'.$file['file'];
		
		if(!is_file($pathIn))
			return false;

		if($file['type'] != 'image' || $xpprefs->query('/baseprefs/stamp')->length == 0)
		{
			return $pathIn;
		}
			
		$vars = $xpprefs->query('/baseprefs/stamp/*/var');
		for($i=0; $i<$vars->length; $i++)
		{
			$varval = '';
			$n = $vars->item($i);
			switch(strtoupper($n->getAttribute('name')))
			{
				case 'DATE':
					if( !($format = $n->getAttribute('format')) )
						$format = 'Y/m/d H:i:s';
					$varval = date($format);
					@unlink($pathOut);			// since date is included, invalidate cache
					break;
				case 'RECORD_ID':
					$varval = $rec;
					break;
			}
			$n->parentNode->replaceChild($domprefs->createTextNode($varval), $n);
		}

		// ------------- CACHING !
		if(is_file($pathOut))
			return $pathOut;
			
		$fields = $xpprefs->query('/baseprefs/stamp/*/field');
		for($i=0; $i<$fields->length; $i++)
		{
			$fldval = '';
			$n = $fields->item($i);
			$fieldname = $n->getAttribute('name');
			
			$x = $sxxml->description->{$fieldname};
			if(is_array($x))
			{
				foreach($x as $v)
					$fldval .= ($fldval?'; ':'') . (string)$v ;
			}
			else
			{
				$fldval .= ($fldval?'; ':'') . (string)$x ;
			}
			$n->parentNode->replaceChild($domprefs->createTextNode($fldval), $n);
		}
		
		$domprefs->normalizeDocument();
		
		$collname = "";
		foreach($ph_session["bases"] as $base)
		{
			foreach($base["collections"] as $coll)
			{
				if($coll["base_id"]==$bas)
				{
					$collname = $coll["name"] ;
				}	
			}
		}

		if( !($tailleimg = @getimagesize($pathIn)) )
			return false;
			
	
		$image_width  = $tailleimg[0];
		$image_height = $tailleimg[1];
		
		$text_xpos  = 0;
		$text_width = $image_width;
		
		$logofile = GV_RootPath . 'config/stamp/' . $bas;
		$logopos  = null;
		$imlogo   = null;	// gd image
		$logo_phywidth = $logo_phyheight = 0;	// physical size
		$logo_reswidth = $logo_resheight = 0;	// resized size

		if( is_array($logosize = @getimagesize($logofile)) )
		{
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : %s\n", __LINE__, var_export($logosize, true)), FILE_APPEND);
			$logo_reswidth  = $logo_phywidth  = $logosize[0];
			$logo_resheight = $logo_phyheight = $logosize[1];

			$v = $xpprefs->query('/baseprefs/stamp/logo');
			if($v->length > 0)
			{
				$logopos = @strtoupper($v->item(0)->getAttribute('position'));
				if( ($logowidth = trim($v->item(0)->getAttribute('width'))) != '')
				{
					if(substr($logowidth, -1) == '%')
						$logo_reswidth = (int)($logowidth * $image_width / 100);
					else
						$logo_reswidth = (int)$logowidth;
					$logo_resheight = (int)($logo_phyheight * ($logo_reswidth/$logo_phywidth));
				}
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : logo_reswidth=%s   logo_phywidth=%s   logo_resheight=%s   logo_phyheight=%s \n", __LINE__, $logo_reswidth, $logo_phywidth, $logo_resheight, $logo_phyheight), FILE_APPEND);
				
				if(($logopos == 'LEFT' || $logopos == 'RIGHT') && $logo_phywidth > 0 && $logo_phyheight > 0)
				{
					switch($logosize['mime'])
					{
						case 'image/gif':
							$imlogo = @imagecreatefromgif($logofile);
							break;
						case 'image/png':
							$imlogo = @imagecreatefrompng($logofile);
							break;
						case 'image/jpeg':
						case 'image/pjpeg':
							$imlogo = @imagecreatefromjpeg($logofile);
							break;
					}
					if($imlogo)
					{
						if($logo_reswidth > $image_width/2)
						{
							// logo too large, resize please
							$logo_reswidth  = (int)($image_width/2);
							$logo_resheight = (int)($logo_phyheight * ($logo_reswidth/$logo_phywidth));
						}
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : logo_reswidth=%s   logo_phywidth=%s   logo_resheight=%s   logo_phyheight=%s \n", __LINE__, $logo_reswidth, $logo_phywidth, $logo_resheight, $logo_phyheight), FILE_APPEND);
						
						$text_width -= ($text_xpos = $logo_reswidth);
					}
				}
			}
		}
		$txth = 0;
		$txtblock = array();
		$texts = $xpprefs->query('/baseprefs/stamp/text');
		$fontsize = "100%";
		for($i=0; $i<$texts->length; $i++)
		{
			if( ($tmpfontsize = trim($texts->item($i)->getAttribute('size'))) != '' )
			{
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : %s * %s\n", __LINE__, $tmpfontsize, $image_width), FILE_APPEND);
				if(substr($tmpfontsize, -1) == '%')
					$tmpfontsize = (int)($tmpfontsize * $image_width / 4000);
				else
					$tmpfontsize = (int)$tmpfontsize;
				//if($tmpfontsize > 300)
				//	$tmpfontsize = 300;
				$fontsize = $tmpfontsize;
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : %s\n", __LINE__, $fontsize), FILE_APPEND);
			}
//			if(!$hd)
//			{
//				$fontsize = $fontsize * 0.2;
//			}
			
			if($fontsize < 2)
				$fontsize = 2;
			elseif($fontsize > 300)
				$fontsize = 300;
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : %s\n", __LINE__, $fontsize), FILE_APPEND);
				
			$txtline = $texts->item($i)->nodeValue;
		
			if($txtline != '')
			{
				$txtlines = record_image::wrap($fontsize, 0, GV_RootPath.'lib/stamper/arial.ttf', $txtline, $text_width);

				foreach($txtlines['l'] as $txtline)
				{
					$txtblock[] = array('x'=>$text_xpos, 'dy'=>$txtlines['dy'], 'w'=>$text_width, 'h'=>$txtlines['h'], 't'=>$txtline, 's'=>$fontsize);//, 'b'=>$txtbox);
					$txth += $txtlines['h'];
				}
			}
		}
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : txtblock=%s \n", __LINE__, var_export($txtblock, true)), FILE_APPEND);
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : txth=%s \n", __LINE__, $txth), FILE_APPEND);
	
		$stampheight = max($logo_resheight, $txth);
		
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : stampheight=%s \n", __LINE__, $stampheight), FILE_APPEND);
		
		$im = imagecreatetruecolor($image_width, $stampheight);
		
		$white = imagecolorallocate($im, 255, 255, 255);
		imagefilledrectangle($im, 0,0 , $image_width, $stampheight, $white);
		imagecolordeallocate($im, $white);

//if($debug)
//	imagejpeg($im, '/home/gaulier/Documents/exports/stamp.1.jpg', 80);
		
		if($imlogo)
		{
			if($logo_reswidth != $logo_phywidth)
			{
				imagecopyresampled($im, $imlogo,
									0, 0,				//	dst_x, dst_y
									0, 0,				//	src_x, src_y
									$logo_reswidth,		//	dst_w
									$logo_resheight,	//	dst_h
									$logo_phywidth,		//	src_w
									$logo_phyheight		//	src_h
									);
			}
			else
			{
				imagecopy($im, $imlogo,
									0, 0,				//	dst_x, dst_y
									0, 0,				//	src_x, src_y
									$logo_phywidth,		//	src_w
									$logo_phyheight		//	src_h
									);
								}
		}
//if($debug)
//	imagejpeg($im, '/home/gaulier/Documents/exports/stamp.2.jpg', 80);
		
		if(count($txtblock) >= 0)
		{
			$black = imagecolorallocate($im, 0, 0, 0);
			$txt_ypos = 0; //$txtblock[0]['h'];
			foreach($txtblock as $block)
			{
				imagettftext($im, $block['s'], 0, $block['x'], $txt_ypos-$block['dy'], $black, GV_RootPath.'lib/stamper/arial.ttf', $block['t']);
				$txt_ypos += $block['h'];
			}
			imagecolordeallocate($im, $black);
		}
		imagejpeg($im, $pathTmpStamp, 80);

//if($debug)
//	imagejpeg($im, '/home/gaulier/Documents/exports/stamp.3.jpg', 80);
		
		imagedestroy($im);
		
		$newh = $image_height + $stampheight;

		
//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : newh=%s \n", __LINE__, $newh), FILE_APPEND);
		
		$cmd  = GV_imagick ;
		$cmd .= ' -extent "'.$image_width.'x'.$newh.'" -draw "image SrcOver 0,'.$image_height.' '.$image_width.','.$stampheight.' 
\''.$pathTmpStamp.'\'"';
		
		$cmd.= " \"".$pathIn ."\""; #  <<-- le doc original
		$cmd.= " \"".$pathOut."\"";  # <-- le doc stampe

//if($debug)
//	file_put_contents(STAMPLOG, sprintf("%d : cmd=%s \n", __LINE__, $cmd), FILE_APPEND);
		
/*		
		$descriptorspec = array(0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
								1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
								2 => array("pipe", "w") // stderr is a file to write to
								);
		$process = proc_open($cmd, $descriptorspec, $pipes);
		if (is_resource($process))
		{   
			fclose($pipes[0]);
			$err="";
			while (!feof($pipes[1])) 
				$out = fgets($pipes[1], 1024);
			fclose($pipes[1]);
			while (!feof($pipes[2]))								 
				$err .= fgets($pipes[2], 1024);
			fclose($pipes[2]);
		   	$return_value = proc_close($process);
		}
*/
		exec($cmd);
		
		unlink($pathTmpStamp);

		if(is_file($pathOut))
			return $pathOut;
		
		return false;
	}
					
	
	
	public static function watermark($bas,$rec,$hd=false)
	{
		$conn = connection::getInstance();
		
		$ph_session = phrasea::bases();
		
		$sbas_id = phrasea::sbasFromBas($bas);
		
		if(!isset($sbas_id))
			return false;
		
		$connSbas = connection::getInstance($sbas_id);
		
		if(!$connSbas)
			return false;
		
		$sql = "SELECT path, file, mime, type, xml FROM subdef s, record r WHERE r.record_id='".$connSbas->escape_string($rec)."' AND r.record_id = s.record_id AND name='preview'";
		
		if($rs = $connSbas->query($sql))
		{
			if($row = $connSbas->fetch_assoc($rs))
			{
				$file = array(
						'type'=>$row['type']
						,'path'=>p4string::addEndSlash($row['path'])
						,'file'=>$row['file']
						,'mime'=>$row['mime']
						,'xml'=>$row['xml']
					);
			}
			$connSbas->free_result($rs);
		}
		
		if(!isset($file))
			return false;

		$pathIn = $file['path'].$file['file'];
		
		$pathOut = $file['path'].'watermark_'.$file['file'];

		if(!is_file($pathIn))
			return false;

		if(is_file($pathOut))
			return $pathOut;
			
			
		if(trim(GV_pathcomposite) != "" && file_exists(GV_RootPath.'config/wm/'.$bas)) // si il y a un WM		
		{
			$cmd  = GV_pathcomposite . " ";
			$cmd .= GV_RootPath.'config/wm/'.$bas . " ";
			$cmd .= " \"".$pathIn ."\" "; #  <<-- la preview original
			$cmd .= " -strip -watermark 90% -gravity center ";
			$cmd .= " \"".$pathOut."\"";  # <-- la preview temporaire

			
			$descriptorspec = array(0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
																1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
																2 => array("pipe", "w") // stderr is a file to write to
																);
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process))
			{   
				fclose($pipes[0]);
				$err="";
				while (!feof($pipes[1])) 
					$out = fgets($pipes[1], 1024);
				fclose($pipes[1]);
				while (!feof($pipes[2]))								 
					$err .= fgets($pipes[2], 1024);
				fclose($pipes[2]);
			   	$return_value = proc_close($process);
			}	
		}
		elseif(GV_imagick!="" )
		{
			$collname = "";
			foreach($ph_session["bases"] as $base)
			{
				foreach($base["collections"] as $coll)
				{
					if($coll["base_id"]==$bas)
					{
						$collname = $coll["name"] ;
					}	
					
				}
			}
			$cmd = GV_imagick ;
			$tailleimg = @getimagesize($pathIn); 					
			$max =  ($tailleimg[0]> $tailleimg[1]?$tailleimg[0]: $tailleimg[1]);
			
			$tailleText = (int)($max/30);
			
			if($tailleText<8)
				$tailleText=8;
			
			if($tailleText>12)
				$decalage=2;
			else
				$decalage=1;
		
			$cmd .= " -fill white -draw \"line 0,0 ".$tailleimg[0].",".$tailleimg[1]."\"";
			$cmd .= " -fill black -draw \"line 1,0 ".($tailleimg[0]+1).",".($tailleimg[1])."\"";
			
			$cmd .= " -fill white -draw \"line ".$tailleimg[0].",0 0,".$tailleimg[1]."\"";
			$cmd .= " -fill black -draw \"line ".($tailleimg[0]+1).",0 1,".($tailleimg[1])."\"";
			
			$cmd .= " -fill white -gravity NorthWest -pointsize $tailleText -draw \"text 0,0 '$collname'\"";
			$cmd .= " -fill black -gravity NorthWest -pointsize $tailleText -draw \"text $decalage,1 '$collname'\"";
			
			$cmd .= " -fill white -gravity center -pointsize $tailleText -draw \"text 0,0 '$collname'\"";
			$cmd .= " -fill black -gravity center -pointsize $tailleText -draw \"text $decalage,1 '$collname'\"";
			
			$cmd .= " -fill white -gravity SouthEast -pointsize $tailleText -draw \"text 0,0 '$collname'\"";
			$cmd .= " -fill black -gravity SouthEast -pointsize $tailleText -draw \"text $decalage,1 '$collname'\"";
			
			
			$cmd.= " \"".$pathIn ."\""; #  <<-- la preview original
			$cmd.= " \"".$pathOut."\"";  # <-- la preview temporaire
			
			$descriptorspec = array(0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
																1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
																2 => array("pipe", "w") // stderr is a file to write to
																);
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process))
			{   
				fclose($pipes[0]);
				$err="";
				while (!feof($pipes[1])) 
					$out = fgets($pipes[1], 1024);
				fclose($pipes[1]);
				while (!feof($pipes[2]))								 
					$err .= fgets($pipes[2], 1024);
				fclose($pipes[2]);
			   	$return_value = proc_close($process);
				 
			}				
		}

		if(is_file($pathOut))
			return $pathOut;
		
		return false;
	}
}