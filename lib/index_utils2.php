<?php

function get_xml(&$baseprefs, &$propfile, $readmeta=true)
{
	global $msg;

	$domxml = new DOMDocument('1.0', 'UTF-8');
	$domxml->standalone = true;
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;

	$element = $domxml->createElement('record');
	
	$domrec = $domxml->appendChild($element);
	$domrec->setAttribute('recordid', $propfile['recordid']);

	$element = $domxml->createElement('description');
	$domdesc = $domrec->appendChild($element);

	$debug = false;

	$tfields = array();

	$cwd = getcwd();

	if($readmeta)
	{
		
		$propfile['doctype'] = giveMeDocType($propfile['mime']);
		switch($propfile['doctype'])
		{
			case 'image' :
				get_fields_from_jpg($baseprefs, $propfile, $tfields);
				break;
			case 'application/pdf';
				get_fields_from_jpg($baseprefs, $propfile, $tfields);
				get_fields_from_pdf($baseprefs, $propfile, $tfields);
			break;
			case 'video':
				get_fields_from_jpg($baseprefs, $propfile, $tfields);
				break;
			case 'audio':
				get_fields_from_jpg($baseprefs, $propfile, $tfields);
				break;
			default:
				get_fields_from_unknown($baseprefs, $propfile, $tfields);
				break;
		}
		chdir($cwd);
	}


	$tfields['tf-recordid']  = array(($propfile['recordid']));
	$tfields['tf-mimetype']  = array(($propfile['mime']));
	$tfields['tf-size']      = array(($propfile['size']));
	$tfields['tf-filepath']  = array(($propfile['subpath']));
	$tfields['tf-parentdir'] = array(($propfile['parentdirectory']));
	$tfields['tf-filename']  = array(($propfile['originalname']));

	$tfields['tf-extension'] = isset($propfile['extension']) ? array(($propfile['extension'])) : array();
	$tfields['tf-width']     = isset($propfile['width'])     ? array(($propfile['width']))     : array();
	$tfields['tf-height']    = isset($propfile['height'])    ? array(($propfile['height']))    : array();
	$tfields['tf-bits']      = isset($propfile['bits'])      ? array(($propfile['bits']))      : array();
	$tfields['tf-channels']  = isset($propfile['channels'])  ? array(($propfile['channels']))  : array();

	if($stat = stat($propfile['hotfolderfile']))
	{
		$tfields['tf-ctime'] = array(utf8_encode(date('Y/m/d H:i:s', $stat['ctime'])));
		$tfields['tf-mtime'] = array(utf8_encode(date('Y/m/d H:i:s', $stat['mtime'])));
		$tfields['tf-atime'] = array(utf8_encode(date('Y/m/d H:i:s', $stat['atime'])));
	}
	if(isset($propfile['appletLastModified']))
	{
		$x = ($propfile['appletLastModified']);	// milisecondes since epoch (64 bits)
		$x = substr($x, 0, strlen($x)-3);		// division / 1000

		$tfields['tf-mtime'] = array(utf8_encode(date('Y/m/d H:i:s', $x)));
	}

	$tfields['tf-archivedate']  = array(utf8_encode(date('Y/m/d H:i:s', time())));
	$tfields['tf-editdate']     = array(utf8_encode(date('Y/m/d H:i:s', time())));
	$tfields['tf-chgdocdate']   = array(utf8_encode(date('Y/m/d H:i:s', time())));
	//	$tfields['tf-chgcoldate']   = array(date('Y/m/d H:i:s', time()));
	//	$tfields['tf-chgstatdate']  = array(date('Y/m/d H:i:s', time()));

	$errcode = 0;
	$errmsg = '';

	// tous les champs de la structure
	if(isset($baseprefs->description[0]))
	{
		foreach($baseprefs->description->children() as $fname=>$fpref)
		{
			// $src	   = (string)$fpref['src'];	// l'attribut 'src' du champ
			$src	   = mb_strtolower((string)$fpref['src']);	// l'attribut 'src' du champ
			$typ 	   = mb_strtolower((string)$fpref['type']);	// l'attribut 'type' du champ
			$distinct  = mb_strtolower((string)$fpref['distinct']);	// l'attribut 'distinct' du champ
			$multi     = (int)((string)$fpref['multi']);				// l'attribut 'multi' du champ
				
			if($src!='' && isset($tfields[$src]))
			{
				// un champ iptc peut etre multi-value, on recoit donc toujours un tableau comme valeur
				$tmpval = array();
				foreach($tfields[$src] as $val)
				{
					// on remplace les caracteres de controle (tous < 32 sauf 9,10,13)
					$val = kill_ctrlchars($val);

					if($typ == 'date')
					{
						$val = str_replace(array('-', ':', '/', '.'), array(' ', ' ', ' ', ' '), $val);
						$ip_date_yyyy = 0;
						$ip_date_mm   = 0;
						$ip_date_dd   = 0;
						$ip_date_hh   = 0;
						$ip_date_nn   = 0;
						$ip_date_ss   = 0;
						switch(sscanf($val, '%d %d %d %d %d %d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss))
						{
							case 1:
								$val = sprintf('%04d/00/00 00:00:00', $ip_date_yyyy);
								break;
							case 2:
								$val = sprintf('%04d/%02d/00 00:00:00', $ip_date_yyyy, $ip_date_mm);
								break;
							case 3:
								$val = sprintf('%04d/%02d/%02d 00:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd);
								break;
							case 4:
								$val = sprintf('%04d/%02d/%02d %02d:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh);
								break;
							case 5:
								$val = sprintf('%04d/%02d/%02d %02d:%02d:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn);
								break;
							case 6:
								$val = sprintf('%04d/%02d/%02d %02d:%02d:%02d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss);
								break;
							default:
								$val = '0000/00/00 00:00:00';
						}
					}
						
					if(!in_array($val, $tmpval))
						$tmpval[] = $val;
				}
				
				foreach($tmpval as $val)
				{
					$domfld = $domdesc->appendChild($domxml->createElement($fname));
					$domfld->appendChild($domxml->createTextNode($val));
				}
			}
		}
	}

	$element = $domxml->createElement('doc');
	
	$domdoc = $domrec->appendChild($element);

	$docProps = array('size', 'originalname', 'channels', 'bits', 'mime', 'width', 'height', 'frameRate', 'duration', 'audiocodec', 'videocodec', 'pixelformat', 'bitrate', 'videobitrate', 'audiobitrate', 'audiosamplerate', 'video_aspect');

	foreach($docProps as $k)
	{
		if(isset($propfile[$k]))
		{
			$domdoc->setAttribute($k, $propfile[$k]);
		}
	}

	// ici on a un record/description DOM provenant du DOCUMENT principal (ex:iptc)
	// on va eventuellement merger avec un fichier xml de description externe

	// printf("------ DOMDOC : --------\n%s\n----------------\n", $domxml->saveXML());

	$statBit = null;
	$sxcaption = null;
	if(isset($propfile['hotfoldercaptionfile']) &&   $propfile['hotfoldercaptionfile'])
	{
		// on a une description xml en plus a lire dans un fichier externe
		if($domcaption = @DOMDocument::load($propfile['hotfoldercaptionfile']))
		{
			if($domcaption->documentElement->tagName == 'description')	// il manque 'record' (�a commence par 'description') : on repare
			{
				$newdomcaption = new DOMDocument('1.0', 'UTF-8');
				$newdomcaption->standalone = true;
				$newdomrec = $newdomcaption->appendChild($newdomcaption->createElement('record'));
				$newdomrec->appendChild($newdomcaption->importNode($domcaption->documentElement, true));
				$sxcaption = simplexml_load_string($newdomcaption->saveXML());
			}
			else
			{
				$sxcaption = simplexml_load_file($propfile['hotfoldercaptionfile']);
			}
			if($inStatus = $sxcaption->status)
			{
				if($inStatus && $inStatus != '')
				{
					$statBit = $inStatus;
				}
			}
		}

		if($sxcaption)
		{
			// printf("------NEED MERGE - sxcaption : --------\n%s\n----------------\n", $sxcaption->asXML() );
			// on merge avec le xml en provenance du fichier principal
			xml_merge($baseprefs, $domdesc, $sxcaption);
		}
	}

	keepdistinct($baseprefs, $domxml);


	if($debug)
		echo "\nOn a trouve un status bit dans le xml : ".$statBit." de longueur ".strlen($statBit)." caracteres\n";

	return(array('xml'=>$domxml, 'status'=>$statBit, 'exif'=>NULL ));
}


function read_meta(&$baseprefs, &$propfile, $domxml)
{
	global $msg;

	$xp = new DOMXPath($domxml);

	$debug = false;

	$tfields = array();

	$cwd = getcwd();

	$propfile['doctype'] = giveMeDocType($propfile['mime']);

	switch($propfile['doctype'])
	{
		case 'image' :
			get_fields_from_jpg($baseprefs, $propfile, $tfields);
			break;
		case 'document';
			get_fields_from_jpg($baseprefs, $propfile, $tfields);
			get_fields_from_pdf($baseprefs, $propfile, $tfields);
			break;
		case 'video':
			get_fields_from_jpg($baseprefs, $propfile, $tfields);
			break;
		case 'audio':
			get_fields_from_jpg($baseprefs, $propfile, $tfields);
			break;
		default:
			get_fields_from_unknown($baseprefs, $propfile, $tfields);
			break;
	}
	chdir($cwd);

	$tfields['tf-mimetype']  = array(utf8_encode($propfile['mime']));
	$tfields['tf-size']      = array(utf8_encode($propfile['size']));
	$tfields['tf-extension'] = isset($propfile['extension']) ? array(utf8_encode($propfile['extension'])) : array();
	$tfields['tf-width']     = isset($propfile['width'])     ? array(utf8_encode($propfile['width']))     : array();
	$tfields['tf-height']    = isset($propfile['height'])    ? array(utf8_encode($propfile['height']))    : array();
	$tfields['tf-bits']      = isset($propfile['bits'])      ? array(utf8_encode($propfile['bits']))      : array();
	$tfields['tf-channels']  = isset($propfile['channels'])  ? array(utf8_encode($propfile['channels']))  : array();

//printf("tfields : %s\n", var_export($tfields, true));
// printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($tfields, true));	
	
	$desc = $xp->query('/record/description');
	if($desc->length==1)
	$domdesc = $desc->item(0);

	$errcode = 0;
	$errmsg = '';

	// tous les champs de la structure
	if(isset($baseprefs->description[0]))
	{
		foreach($baseprefs->description->children() as $fname=>$fpref)
		{
			// $src	   = (string)$fpref['src'];	// l'attribut 'src' du champ
			$src	   = mb_strtolower((string)$fpref['src']);	// l'attribut 'src' du champ
			$typ 	   = mb_strtolower((string)$fpref['type']);	// l'attribut 'type' du champ
			$distinct  = mb_strtolower((string)$fpref['distinct']);	// l'attribut 'distinct' du champ
			$multi     = (int)((string)$fpref['multi']);				// l'attribut 'multi' du champ
				
			if($src!='' && isset($tfields[$src]))
			{
				// delete old value(s)
				//printf("deleting nodes '%s'\n", $fname);
				$old = $xp->query($fname, $domdesc);
				foreach($old as $f)
				$domdesc->removeChild($f);
					
				//printf("apr�s delete : %s\n", $domxml->saveXML());

				// un champ iptc peut etre multi-value, on re�oit donc toujours un tableau comme valeur

				// delete duplicates values from multi-values
				$tmpval = array();
				foreach($tfields[$src] as $val)
				{
					// on remplace les caracteres de controle (tous < 32 sauf 9,10,13)
					$val = trim(kill_ctrlchars($val));
					if($typ == 'date')
					{
						$val = str_replace(array('-', ':', '/', '.'), array(' ', ' ', ' ', ' '), $val);
						$ip_date_yyyy = 0;
						$ip_date_mm   = 0;
						$ip_date_dd   = 0;
						$ip_date_hh   = 0;
						$ip_date_nn   = 0;
						$ip_date_ss   = 0;
						switch(sscanf($val, '%d %d %d %d %d %d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss))
						{
							case 1:
								$val = sprintf('%04d/00/00 00:00:00', $ip_date_yyyy);
								break;
							case 2:
								$val = sprintf('%04d/%02d/00 00:00:00', $ip_date_yyyy, $ip_date_mm);
								break;
							case 3:
								$val = sprintf('%04d/%02d/%02d 00:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd);
								break;
							case 4:
								$val = sprintf('%04d/%02d/%02d %02d:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh);
								break;
							case 5:
								$val = sprintf('%04d/%02d/%02d %02d:%02d:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn);
								break;
							case 6:
								$val = sprintf('%04d/%02d/%02d %02d:%02d:%02d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss);
								break;
							default:
								$val = '0000/00/00 00:00:00';
						}
					}
					if(!in_array($val, $tmpval))
						$tmpval[] = $val;
				}
				
				foreach($tmpval as $val)
				{
					$domfld = $domdesc->appendChild($domxml->createElement($fname));
					$domfld->appendChild($domxml->createTextNode($val));
				}
				//printf("apr�s insert : %s\n", $domxml->saveXML());

			}
		}
	}
// printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($domxml->saveXML(), true));	
	
	keepdistinct($baseprefs, $domxml);

// printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($domxml->saveXML(), true));	
	
	$doc = $xp->query('/record/doc');
	if($doc->length==1)
		$domdoc = $doc->item(0);

	$docProps = array('size', 'originalname', 'channels', 'bits', 'mime', 'width', 'height', 'frameRate', 'duration', 'audiocodec', 'videocodec', 'pixelformat', 'bitrate', 'videobitrate', 'audiobitrate', 'audiosamplerate', 'video_aspect');
	
	foreach($docProps as $k)
	{
		if(isset($propfile[$k]))
		{
			$domdoc->setAttribute($k, $propfile[$k]);
		}
	}

	$lexml = $domxml->saveXML();

	unset($domxml);

	return(array('xml'=>$lexml, 'exif'=>NULL, 'errcode'=>$errcode, 'errmsg'=>$errmsg ));
}


// jy 20060802 : supprime les doublons des champs multivalues marques 'distinct'
function keepdistinct(&$sxstruct, &$domdesc)
{
	$debug = false;
	if($debug)
		printf("before keepdistinct(...) : \n%s\n", $domdesc->saveXML());
	$domdesc->normalize();
	$nodestodelete = array();
	foreach($sxstruct->description->children() as $fname=>$fpref)
	{
		if($fpref['multi'])
		{
			$nodes2add = array();

			$separator = mb_strtolower((string)$fpref['separator']);	// l'attribut 'separator' du champ
			if(strpos($separator, ';')===false)	// le ';' est le separator par defaut, et un separator obligatoire
				$separator .= ';';

			if(strlen($separator) > 1)
			{
				// s'il y'a plusieurs delimiters, on transforme en regexp pour utiliser split
				$separator = preg_split('//', $separator, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				$separator = '/\\'.implode('|\\', $separator) . '/';
			}

			if($debug)
			{
				printf("separator : '$separator'\n");
				printf("<b>%s</b> : %s \n", __LINE__, htmlentities($domdesc->saveXML()));
			}
			$flds = $domdesc->getElementsByTagName($fname);
			for($i=0; $i<$flds->length; $i++)
			{
				$val = trim($flds->item($i)->nodeValue);
				if(strlen($separator) == 1)
					$tval = explode($separator, $val);
				else
					$tval = preg_split($separator, $val);

				foreach($tval as $val)
				{
					if( ($val=trim($val)) != '')
						$nodes2add[] = array('v'=>$val, 'ref'=>$flds->item($i));
				}
				$flds->item($i)->setAttribute('todelete', '1');
			}
			if($debug)
			{
				printf("<b>%s</b> : %s \n", __LINE__, var_export($nodes2add, true));
			}
			foreach($nodes2add as $node)
			{
				$nn = $domdesc->createElement($fname);
				$nn->appendChild($domdesc->createTextNode($node['v']));
				$node['ref']->parentNode->insertBefore($nn, $node['ref']);
			}
		}

		if($fpref['distinct'] == '1')
		{
			$flds = $domdesc->getElementsByTagName($fname);
			for($i=0; $i<$flds->length; $i++)
			{
				for($j=$i+1; $j<$flds->length; $j++)
				{
					if(trim($flds->item($j)->nodeValue) == trim($flds->item($i)->nodeValue))
						$flds->item($j)->setAttribute('todelete', '1');
				}
			}
		}
		// !!! ne pas supprimer directement a partir de la nodelist (pb iterator)
		$flds = $domdesc->getElementsByTagName($fname);
		for($i=0; $i<$flds->length; $i++)
		{
			if($flds->item($i)->getAttribute('todelete') == '1')
			{
				$nodestodelete[] = $flds->item($i);
				if($flds->item($i)->previousSibling->nodeValue == "\x0a\t\t")
					$nodestodelete[] = $flds->item($i)->previousSibling;
			}
		}
	}
	foreach($nodestodelete as $f)
		$f->parentNode->removeChild($f);
	if($debug)
		printf("after keepdistinct(...) : \n%s\n", $domdesc->saveXML());
}

function xml_merge(&$baseprefs, &$domdesc, &$sxcaption)
{
	$domdoc = $domdesc->ownerDocument ;
	foreach($sxcaption->description->children() as $fn=>$fld)
	{
		$fv = trim((string)$fld);
		if(isset($baseprefs->description->$fn))
		{
			// le champ de la fiche xml existe bien dans la structure
			// printf("ext caption %s(%s) : %s\n", (string)$fn, $baseprefs->description->$fn, $fv);
			// est-ce qu'il existe dans la description actuelle
			$dnl = $domdesc->getElementsByTagName($fn);

 			$multi = ( $baseprefs->description->{$fn}['multi']=='1' );

			// jy 20060802 : les dates dans un fichier xml doivent �tre iso, ou le format precise DANS LE CHAMP DE LA FICHE
			// (et non plus dans la structure)
			if($baseprefs->description->{$fn}['type']=='date')
			{
				if($fld['format'])
				{
					$fv = p4date::dateToIsodate($fv, $fld['format']);
				}
			}

			// $fv = str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $fv);

			// AS : ajout de multi le 20050125
			if(!$multi && $dnl->length > 0)
			{
				// oui : on l'ecrase ou on le complete ?
				$xmloverdoc = true;		// !!! a placer dans les prefs !!!
				if($xmloverdoc)
				{
					$domfld = $domdoc->createElement($fn);
					$domfld->appendChild($domdoc->createTextNode($fv));
					$domdesc->replaceChild($domfld, $dnl->item(0));
				}
				else
				{
					// on le complete
					$domfld = $dnl->item(0)->appendChild($domdoc->createTextNode($fv));
				}
			}
			else
			{
				// non : ajoute le champ
				// printf("fv:$fv\n");
				$domfld = $domdesc->appendChild($domdoc->createElement($fn));
				$domfld->appendChild($domdoc->createTextNode($fv));
			}
				
		}
	}
}


function get_fields_from_pdf(&$baseprefs, &$propfile, &$tfields)
{

	$PDF_TEXT_REF = 'pdf-text';

	$system = p4utils::getSystem();
	if(!(isset($propfile['hotfoldercaptionfile'])))
	{
		$cmd = '';
		$tmpfile = GV_RootPath.'tmp/pdf-extract'.time().mt_rand(00000,99999);
		if($system == 'DARWIN' || $system == 'LINUX')
		{
				$cmd  = GV_pdftotext.' -f 1 -l '.GV_pdfmaxpages . ' -raw -enc UTF-8 -eol unix -q '.str_replace(' ','\ ',addslashes($propfile['hotfolderfile'])). ' '.$tmpfile;
		}
		else	// WINDOWS
		{
				$cmd  = GV_pdftotext.' -f 1 -l '.GV_pdfmaxpages . ' -raw -enc UTF-8 -eol unix -q '.str_replace(' ','\ ',addslashes($propfile['hotfolderfile'])). ' '.$tmpfile;
		}

		if ($cmd)
		{
			$s = shell_exec($cmd);

			if(file_exists($tmpfile))
			{
				$tfields['pdf-text'] = array(file_get_contents($tmpfile));
				unlink($tmpfile);
			}
		}
	}
}


function get_fields_from_unknown(&$baseprefs, &$propfile, &$tfields)
{
	return;
}

function get_fields_from_jpg(&$baseprefs, &$propfile, &$tfields)
{
	if($size = getimagesize($propfile['hotfolderfile'], $info))
	{
		$propfile['width'] = $size[0];
		$propfile['height'] = $size[1];
		if(array_key_exists('bits', $size))
			$propfile['bits'] = $size['bits'];
		if(array_key_exists('channels', $size))
			$propfile['channels'] = $size['channels'];
	}
	
	$cmd = NULL;

	$system = p4utils::getSystem();

	if($system == 'DARWIN' )
	{
		$cmd = GV_exiftool.' -X -n -fast ' . escapeshellarg($propfile['hotfolderfile']) . '';
	}
	else if ( $system == 'LINUX')
	{
		$cmd = GV_exiftool.' -X -n -fast ' . escapeshellarg($propfile['hotfolderfile']) . '';
	}
	else	// WINDOWS
	{
		if(chdir(GV_RootPath.'tmp/'))
		{
			$cmd = 'start /B /LOW ' . GV_exiftool.' -X -n -fast ' . escapeshellarg($propfile['hotfolderfile']) . '';
		}
	}
	if($cmd)
	{
		$s = @shell_exec($cmd);
		if($s!='')
		{
	
			$domrdf = new DOMDocument();
			$domrdf->recover = true;
			$domrdf->preserveWhiteSpace = false;
			
			if($domrdf->loadXML($s))
			{
				
				$xptrdf = new DOMXPath($domrdf);

				$xptrdf->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#') ;
				
				$pattern = "(xmlns:([a-zA-Z-_0-9]+)=[']{1}(https?:[/{2,4}|\\{2,4}][\w:#%/;$()~_?/\-=\\\.&]*)[']{1})";
				preg_match_all($pattern, $s, $matches, PREG_PATTERN_ORDER, 0);

				foreach($matches[2] as $key=>$value)
				{
					$xptrdf->registerNamespace($matches[1][$key], $value) ;
				}	
				
				
				$macchars  = "\x81\x82\x83\x84\x86\x87\x88\x89\x8A\x8B\x8D\x8E\x8F";			// 8x except 85,  8C
				$macchars .= "\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9D\x9E\x9F";	// 9x except 9C
				$macchars .= "\xBC\xBD\xBE";
		
				$descriptionNode = @$xptrdf->query('/rdf:RDF/rdf:Description')->item(0);
				if($descriptionNode)
				{
					for($x = $descriptionNode->firstChild; $x; $x=$x->nextSibling)
					{
						if($x->nodeType==XML_ELEMENT_NODE)
						{
							switch($x->nodeName)
							{
								case 'Composite:ImageSize':
									if( (count($_t = explode('x', $x->textContent))) == 2 )
									{
										$propfile['width']  = 0 + $_t[0];
										$propfile['height'] = 0 + $_t[1];
									}
									break;
								case 'ExifIFD:ExifImageWidth':
									if(!array_key_exists('width', $propfile))
										$propfile['width'] = 0 + $x->textContent;
									break;
								case 'ExifIFD:ExifImageHeight':
									if(!array_key_exists('height', $propfile))
										$propfile['height'] = 0 + $x->textContent;
									break;
								case 'File:ColorComponents':
								case 'IFD0:SamplesPerPixel':
									if(!array_key_exists('channels', $propfile))
										$propfile['channels'] = 0 + $x->textContent;
									break;
								case 'File:BitsPerSample':
								case 'IFD0:BitsPerSample':
									if(!array_key_exists('bits', $propfile))
										$propfile['bits'] = 0 + $x->textContent;
									break;
							}
							if( count($_t = explode(':', $x->nodeName)) == 2 )
							{
								switch($_t[1])
								{
									case 'ImageWidth':
										if(!array_key_exists('width', $propfile))
											$propfile['width'] = 0 + $x->textContent;
										break;
									case 'ImageHeight':
										if(!array_key_exists('height', $propfile))
											$propfile['height'] = 0 + $x->textContent;
										break;
								}
							}
						}
					}
				}
				
				$x = @$xptrdf->query('/rdf:RDF/rdf:Description/XMP-exif:ImageUniqueID');

				if($x && $x->length>0)
				{		
					$x = $x->item(0);
					
					$encoding = strtolower($x->getAttribute('rdf:datatype').$x->getAttribute('et:encoding'));
					$base64_encoded = (strpos($encoding,'base64') !== false);
			
					if( ($v = $x->firstChild) && $v->nodeType==XML_TEXT_NODE)
					{
						$value = $base64_encoded ? base64_decode($v->nodeValue) : $v->nodeValue;
						$utf8value = guessCharset($value, $macchars);
						$propfile['UniqueID'] = $utf8value;
					}
				}
					
				foreach($baseprefs->description->children() as $fname=>$fpref)
				{
					$src    = (string)$fpref['src'];	// l'attribut 'src' du champ
					$srclow = mb_strtolower($src);
					if(!$src)
						continue;
		
					$x = @$xptrdf->query($src);
					if(!$x || $x->length!=1)
					{
						continue;
					}
					
					$tfields[$fname] = array();
					$x = $x->item(0);
					
					//double check -- exiftool uses et:encoding in version prior 7.71
					$encoding = strtolower($x->getAttribute('rdf:datatype').$x->getAttribute('et:encoding'));
					$base64_encoded = (strpos($encoding,'base64') !== false);
					
					$bag = $xptrdf->query('rdf:Bag', $x);
					if($bag && $bag->length==1)
					{
						$li = $xptrdf->query('rdf:li', $bag->item(0));
						if($li->length > 0)
						{
							$tfields[$fname] = array();
							for($ili=0; $ili<$li->length; $ili++)
							{
								$value = $base64_encoded ? base64_decode($li->item($ili)->nodeValue) : $li->item($ili)->nodeValue;
								$utf8value = trim(guessCharset($value, $macchars));
								$tfields[$srclow][] = $utf8value;
							}
						}
					}
					else
					{
						if( ($v = $x->firstChild) && $v->nodeType==XML_TEXT_NODE)
						{
							$value = $base64_encoded ? base64_decode($v->nodeValue) : $v->nodeValue;
							$utf8value = guessCharset($value, $macchars);
							$tfields[$srclow] = array($utf8value);
						}
					}
				}
			}
		}
	}
	
	if(isset($propfile['doctype']) && in_array($propfile['doctype'], array('audio', 'video')))
	{
		$extended_props = getVideoInfos($propfile['hotfolderfile']);
		$propfile = array_merge($propfile, $extended_props);
	}
	return;
}

function guessCharset($s, &$macchars)
{
	if(mb_convert_encoding(mb_convert_encoding($s, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32') == $s)
	{
		$mac = mb_convert_encoding($s, 'windows-1252', 'UTF-8');
		for($i=strlen($mac); $i; )
		{
			if(strpos($macchars, $mac[--$i]) !== false)
				return(iconv('MACINTOSH', 'UTF-8', $mac));
		}
		return($s);
	}
	else
	{
		for($i=strlen($s); $i; )
		{
			if(strpos($macchars, $s[--$i]) !== false)
				return(iconv('MACINTOSH', 'UTF-8', $s));
		}
		return(iconv('windows-1252', 'UTF-8', $s));
	}
}

function microtime_float()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}


function kill_ctrlchars($s)		// ok en utf8 !
{
	static $a_in=null;
	static $a_out=null;
	if($a_in===null)
	{
		$a_in=array();
		$a_out=array();
		for($cc=0; $cc<32; $cc++)
		{
			if($cc!=10 && $cc!=13 && $cc!=9)
			{
				$a_in[] = chr($cc);
				$a_out[] = '_';
			}
		}
	}
	return(str_replace($a_in, $a_out, $s));
}

/**
 *remplace les {{Y}}, {{m}}, {{d}}... dans un path (pour ventiler dans le repositories)
 * @param	string	$path	le path a corriger
 * @param 	int		$date	la date en unixtimestamp
 * @return	string	le path corrige
 */


function makeSubdefs($sbas_id, $hdDoc)
{

	$zdate = time();

	$debug = false;

	$ret = array('subdefs'=>array());

	//avalaible subdefs
	$AvSubdefs = databox::get_subdefs($sbas_id);

	if($debug)
	{
		echo "Subdefs disponibles : \n";
		var_dump($AvSubdefs);
	}

	$ret = array();
	$ret['subdefs'] = array();


	$what = isset($AvSubdefs[$hdDoc['type']]) ? $hdDoc['type'] : ( isset($AvSubdefs['image']) ? 'image' : false);
	if($debug)
		echo "\nJe deduis le type de document : $what d'apres ".$hdDoc['type']."\n\n";

	if($what !== false)
	{
		$subdefs = $AvSubdefs[$what];

		if($debug)
			echo "\nJe deduis le groupe de subdefs : \n" . var_export($subdefs, true) . "\n";

		$HDFile  = p4string::addEndSlash($hdDoc['path']) . $hdDoc['file'] ;
		$HDprops = getHDprops($HDFile, $what);

		$ret['document'] = $HDprops;

		$HDprops['recordid'] = $hdDoc['record_id'];
		$HDprops['mime'] = $hdDoc['mime'];
		// $HDprops['Orientation'] = '?';

		if($debug)
			echo "\nHDprops : \n" . var_export($HDprops, true) . "\n";

		$tryMakeSubdef = false;


		if($debug)
		{
			echo "\nOn genere les subdefs... pour '$what'\n";
		}
		$i=1;
		
		foreach($subdefs as $subdef)
		{
			$physdpath = (string)($subdef->path);

			$physdpath = p4string::addEndSlash(p4::dispatch($physdpath));
			if(!is_dir($physdpath))
			p4::fullmkdir($physdpath);


			$ret['subdefs'][$i] = subdefDispatcher($what		// 'image', 'video' or 'audio'
			, $HDFile	// path to document
			, $subdef	// subdef settings from struct (simplexml)
			, $physdpath	// where to create the subdef
			, $HDprops		// recordid, width, height, mime, CMYK etc...
			);
				
			// complete le resultat
			if($ret['subdefs'][$i] !== false)
			{
				$ret['subdefs'][$i]['record_id'] = $hdDoc['record_id'] ;
				$ret['subdefs'][$i]['name']    = (string)($subdef->attributes()->name) ;
				$ret['subdefs'][$i]['path']    = $physdpath ;
				$ret['subdefs'][$i]['inbase']  = 1 ;

				$baseurl = '';

				if( ($baseurl = trim((string)($subdef->baseurl))) )
				$baseurl = p4string::addEndSlash($baseurl).substr($physdpath, strlen(p4string::addEndSlash((string)($subdef->path))));
					
				$ret['subdefs'][$i]['baseurl'] = $baseurl ;
				$ret['subdefs'][$i]['size']    = @filesize(p4string::addEndSlash($physdpath) . $ret['subdefs'][$i]['file']) ;
			}

			$i++;
		}
		/*
		 */
		unset($HDprops);
		unset($subdefs);
	}
	if($debug)
		print("\nfin de make_subdef(...)\n");

	if($debug)
	{
		echo "\nOn a comme retour \n";
		var_dump($ret);
	}

	unset($what);
	unset($AvSubdefs);

	return($ret);
}


function subdefDispatcher($what, $originalDoc, $sd, $physdpath, $prop)
{
	$makeSubdef = false;
	switch($what)
	{
		case 'video':
			$makeSubdef = make1VideoSubdef($originalDoc, $sd, $physdpath, $prop);
			break;

		case 'audio':
			$makeSubdef = make1AudioSubdef($originalDoc, $sd, $physdpath, $prop);
			break;
				
		case 'document':
			$makeSubdef = make1DocumentSubdef($originalDoc, $sd, $physdpath, $prop);
			break;

		case 'image':
		default:
			$makeSubdef = make1subdef($originalDoc, $sd, $physdpath, $prop);
			break;

		case 'flash':
			$makeSubdef = make1flashsubdef($originalDoc, $sd, $physdpath, $prop);
			break;
	}
	return $makeSubdef;
}

function getHDprops($hdPath,$type)
{
	switch($type)
	{
		case 'video':
		case 'audio':
			$return = getVideoInfos($hdPath);
			break;

		case 'image':
		default:
			$return = getImageInfos($hdPath);
			break;
	}
	return $return;
}

function getVideoInfos($hdPath)
{
	$retour = array('width'=>0, 'height'=>0, 'CMYK'=>false);

	$datas = exiftool::get_fields($hdPath, array('Duration', 'Image Width', 'Image Height'));
	$duration = 0;
	
	if($datas['Duration'])
	{
		$data = explode('_', trim($datas['Duration']));
		$data = explode(':', $data[0]);
		
		$factor = 1;
		while($segment = array_pop($data))
		{
			$duration += $segment*$factor;
			$factor *=60;
		}
	}
	$width = $height = false;
	if($datas['Image Width'])
	{
		if((int)$datas['Image Width'] > 0)
			$width = $datas['Image Width'];
	}
	if($datas['Image Height'])
	{
		if((int)$datas['Image Height'] > 0)
			$height = $datas['Image Height'];
	}
	
	
	$cmd = GV_mplayer.' -identify ' . escapeshellarg($hdPath) . '  -ao null -vo null -frames 0 | grep ^ID_';
	$docProps = array(
	 	'ID_VIDEO_WIDTH'	 => 'width',
	 	'ID_VIDEO_HEIGHT'	 => 'height',
	 	'ID_VIDEO_FPS'		 => 'frameRate',
//	 	'ID_LENGTH'			 => 'duration',
	 	'ID_AUDIO_CODEC'	 => 'audiocodec',
	 	'ID_VIDEO_CODEC'	 => 'videocodec',
	 	'ID_VIDEO_BITRATE'	 => 'videobitrate',
	 	'ID_AUDIO_BITRATE'	 => 'audiobitrate',
	 	'ID_AUDIO_RATE'		 => 'audiosamplerate',
	 	'ID_VIDEO_ASPECT'	 => 'video_aspect'
	);

        $stdout = shell_exec($cmd);

	$propfile = array();
	
	$stdout = explode("\n",$stdout);
	foreach($stdout as $property)
	{
		$props = explode('=',$property);
		
		
		if(array_key_exists($props[0], $docProps))
			$propfile[$docProps[$props[0]]] = $props[1];
	}
	
	$propfile['duration'] = $duration;
	if($width)
		$propfile['width'] = $width;
	if($height)
		$propfile['height'] = $height;
	$retour = array_merge($retour,$propfile);
	
	return $retour;
}

function getImageInfos($hdPath)
{
	$retour = array('width'=>0, 'height'=>0, 'CMYK'=>false);
	if( ($tempHD = getimagesize($hdPath)) )
	{
		if(isset($tempHD['channels']) && $tempHD['channels']==4)
		$retour['CMYK'] = true;
		$retour['width'] = $tempHD[0];
		$retour['height'] = $tempHD[1];
	}
	if($ex = @exif_read_data($hdPath, 'FILE') )
	{
		if(array_key_exists('Orientation', $ex))
		$retour['Orientation'] = $ex['Orientation'];
	}
	return $retour;
}


function isRaw($mime)
{
	$raws = array(

		'3fr' 	=> 'image/x-tika-hasselblad'
		,'arw' 	=> 'image/x-tika-sony'
		,'bay' 	=> 'image/x-tika-casio'
		,'cap' 	=> 'image/x-tika-phaseone'
		,'cr2' 	=> 'image/x-tika-canon'
		,'crw' 	=> 'image/x-tika-canon'
		,'dcs' 	=> 'image/x-tika-kodak'
		,'dcr' 	=> 'image/x-tika-kodak'
		,'dng' 	=> 'image/x-tika-dng'
		,'drf' 	=> 'image/x-tika-kodak'
		,'erf' 	=> 'image/x-tika-epson'
		,'fff' 	=> 'image/x-tika-imacon'
		,'iiq' 	=> 'image/x-tika-phaseone'
		,'kdc' 	=> 'image/x-tika-kodak'
		,'k25' 	=> 'image/x-tika-kodak'
		,'mef' 	=> 'image/x-tika-mamiya'
		,'mos' 	=> 'image/x-tika-leaf'
		,'mrw' 	=> 'image/x-tika-minolta'
		,'nef' 	=> 'image/x-tika-nikon'
		,'nrw' 	=> 'image/x-tika-nikon'
		,'orf' 	=> 'image/x-tika-olympus'
		,'pef' 	=> 'image/x-tika-pentax'
		,'ppm' 	=> 'image/x-portable-pixmap'
		,'ptx' 	=> 'image/x-tika-pentax'
		,'pxn' 	=> 'image/x-tika-logitech'
		,'raf' 	=> 'image/x-tika-fuji'
		,'raw' 	=> 'image/x-tika-panasonic'
		,'r3d' 	=> 'image/x-tika-red'
		,'rw2' 	=> 'image/x-tika-panasonic'
		,'rwz'	=> 'image/x-tika-rawzor'
		,'sr2' 	=> 'image/x-tika-sony'
		,'srf' 	=> 'image/x-tika-sony'
		,'x3f' 	=> 'image/x-tika-sigma');

		if(in_array($mime, $raws))
			return true;
		return false;
}

function giveMimeExt($doc, $OrDoc=null)
{
	// printf("--------------\n %s \n--------------\n", $doc);
	if($OrDoc == null)
	$OrDoc = $doc;

	static $mimeTypes = array(
	'ai' 	=> 'application/postscript'
	,'3gp' 	=> 'video/3gpp'
	,'aif' 	=> 'audio/aiff'
	,'aiff' => 'audio/aiff'
	,'asf' 	=> 'video/x-ms-asf'
	,'asx' 	=> 'video/x-ms-asf'
	,'avi' 	=> 'video/avi'
	,'bmp' 	=> 'image/bmp'
	,'bz2'	=> 'application/x-bzip'
		
	,'3fr' 	=> 'image/x-tika-hasselblad'
	,'arw' 	=> 'image/x-tika-sony'
	,'bay' 	=> 'image/x-tika-casio'
	,'cap' 	=> 'image/x-tika-phaseone'
	,'cr2' 	=> 'image/x-tika-canon'
	,'crw' 	=> 'image/x-tika-canon'
	,'dcs' 	=> 'image/x-tika-kodak'
	,'dcr' 	=> 'image/x-tika-kodak'
	,'dng' 	=> 'image/x-tika-dng'
	,'drf' 	=> 'image/x-tika-kodak'
	,'erf' 	=> 'image/x-tika-epson'
	,'fff' 	=> 'image/x-tika-imacon'
	,'iiq' 	=> 'image/x-tika-phaseone'
	,'kdc' 	=> 'image/x-tika-kodak'
	,'k25' 	=> 'image/x-tika-kodak'
	,'mef' 	=> 'image/x-tika-mamiya'
	,'mos' 	=> 'image/x-tika-leaf'
	,'mrw' 	=> 'image/x-tika-minolta'
	,'nef' 	=> 'image/x-tika-nikon'
	,'nrw' 	=> 'image/x-tika-nikon'
	,'orf' 	=> 'image/x-tika-olympus'
	,'pef' 	=> 'image/x-tika-pentax'
	,'ppm' 	=> 'image/x-portable-pixmap'
	,'ptx' 	=> 'image/x-tika-pentax'
	,'pxn' 	=> 'image/x-tika-logitech'
	,'raf' 	=> 'image/x-tika-fuji'
	,'raw' 	=> 'image/x-tika-panasonic'
	,'r3d' 	=> 'image/x-tika-red'
	,'rw2' 	=> 'image/x-tika-panasonic'
	,'rwz'	=> 'image/x-tika-rawzor'
	,'sr2' 	=> 'image/x-tika-sony'
	,'srf' 	=> 'image/x-tika-sony'
	,'x3f' 	=> 'image/x-tika-sigma'
		
	,'css' 	=> 'text/css'
	,'doc' 	=> 'application/msword'
	,'docx' => 'application/msword'
	,'eps' 	=> 'application/postscript'
	,'exe' 	=> 'application/x-msdownload'
	,'flv' 	=> 'video/x-flv'
	,'gif' 	=> 'image/gif'
	,'gz' 	=> 'application/x-gzip'
	,'htm' 	=> 'text/html'
	,'html' => 'text/html'
	,'jpeg' => 'image/jpeg'
	,'jpg' 	=> 'image/jpeg'
	,'m3u' 	=> 'audio/x-mpegurl'
	,'mid' 	=> 'audio/mid'
	,'midi' => 'audio/mid'
	,'mkv' 	=> 'video/matroska'
	,'mp3' 	=> 'audio/mpeg'
	,'mp4'	=> 'video/mp4'
	,'vob'	=> 'video/mpeg'
	,'mp2p'	=> 'video/mpeg'
	,'mpeg' => 'video/mpeg'
	,'mpg' 	=> 'video/mpeg'
	,'ods' 	=> 'application/vnd.oasis.opendocument.spreadsheet'
	,'odt' 	=> 'application/vnd.oasis.opendocument.text'
	,'odp' 	=> 'application/vnd.oasis.opendocument.presentation'
	,'ogg' 	=> 'audio/ogg'
	,'pdf' 	=> 'application/pdf'
	,'pls' 	=> 'audio/scpls'
	,'png' 	=> 'image/png'
	,'pps' 	=> 'application/vnd.ms-powerpoint'
	,'ppt' 	=> 'application/vnd.ms-powerpoint'
	,'pptx' => 'application/vnd.ms-powerpoint'
	,'psd' 	=> 'image/psd'
	,'ra' 	=> 'audio/x-pn-realaudio'
	,'ram' 	=> 'audio/x-pn-realaudio'
	,'rm' 	=> 'application/vnd.rn-realmedia'
	,'rtf' 	=> 'application/msword'
	,'rv' 	=> 'video/vnd.rn-realvideo'
	,'swf' 	=> 'application/x-shockwave-flash'
	,'tar' 	=> 'application/x-tar'
	,'tif' 	=> 'image/tiff'
	,'txt' 	=> 'text/plain'
	,'wav' 	=> 'audio/wav'
	,'wma' 	=> 'audio/x-ms-wma'
	,'wmv' 	=> 'video/x-ms-wmv'
	,'wmx' 	=> 'video/x-ms-wmx'
	,'xls' 	=> 'application/excel'
	,'xlsx'	=> 'application/excel'
	,'xml' 	=> 'text/xml'
	,'xsl' 	=> 'text/xsl'
	,'zip' 	=> 'application/zip'
	);

	$type_pj = '';

	$debug = false;

	if(function_exists('finfo_open'))
	{
		$magicfile = NULL;
		if(is_file('/usr/share/misc/magic'))
		{
			$magicfile = '/usr/share/misc/magic';
		}
		elseif(is_file('/usr/share/misc/magic.mgc'))
		{
			$magicfile = '/usr/share/misc/magic.mgc';
		}
		elseif(is_file(GV_RootPath.'www/include/magic'))
		{
			$magicfile = GV_RootPath.'www/include/magic';
		}

		if(($finfo = @finfo_open(FILEINFO_MIME, $magicfile)) !== false)//, GV_RootPath.'include/magic'); // Retourne le type mime
		{
			$type_pj =  finfo_file($finfo,$doc);
			finfo_close($finfo);
		}
		elseif(($finfo = @finfo_open(FILEINFO_MIME, NULL)) !== false)
		{
			$type_pj =  finfo_file($finfo,$doc);
			finfo_close($finfo);
		}

		if($debug)
			echo "fileinfo OK et renvoie ".$type_pj."\n";
	}
	$mime ='';

	$pi = pathinfo($OrDoc);

	if(!isset($pi['extension']))
		$pi['extension'] = '';

	$ext = mb_strtolower($pi['extension']);

	$mime = $type_pj;

	if(trim($type_pj) == '')
	{
		if($ext!='' && isset($mimeTypes[$ext]))
		{
			$mime = $mimeTypes[$ext];
		}
		elseif($ext=='')
		{
			$gis = getimagesize($doc) ;
			if($gis['mime']!='')
				$mime = $gis['mime'];
		}
	}

	if($debug)
		echo "mime_content_type OK et renvoie ".mime_content_type($doc)."\n";
		
	if($mime=='' || $mime==NULL )
		$mime=mime_content_type($doc) ;

	if(( $pos = strpos($mime,'; charset=')) !== false )
	{
		$mime = substr($mime,0,$pos);
	}

	if($mime == 'application/pdf' && $ext == 'ai')
		$mime = 'image/vnd.adobe.illustrator';
	elseif($mime == 'text/plain' && $ext == 'mkv')
		$mime = 'video/matroska';
	elseif(in_array($mime,array('application/octet-stream','image/tiff','application/vnd.ms-office','application/zip')) && isset($mimeTypes[$ext]))
		$mime = $mimeTypes[$ext];
	elseif($mime == '' && $ext == 'm4v')
		$mime = 'video/x-m4v';

	return array('mime'=>$mime, 'ext'=>$ext);
}


if(!function_exists('mime_content_type'))
{
	function mime_content_type($f)
	{
		$ext2mime = array(
					  'dwg'=>'application/acad'  // Fichiers AutoCAD
		, 'ccad'=>'application/clariscad'  // Fichiers ClarisCAD
		, 'drw'=>'application/drafting'  // Fichiers MATRA Prelude drafting
		, 'dxf'=>'application/dxf'  // Fichiers AutoCAD
		, 'unv'=>'application/i-deas'  // Fichiers SDRC I-deas
		, 'igs'=>'application/iges'  // Format d'echange CAO IGES
		, 'iges'=>'application/iges'  // Format d'echange CAO IGES
		, 'bin'=>'application/octet-stream'  // Fichiers binaires non interpretes
		, 'oda'=>'application/oda'  // Fichiers ODA
		, 'pdf'=>'application/pdf'  // Fichiers Adobe Acrobat
		, 'ai'=>'application/postscript'  // Fichiers PostScript
		, 'eps'=>'application/postscript'  // Fichiers PostScript
		, 'ps'=>'application/postscript'  // Fichiers PostScript
		, 'prt'=>'application/pro_eng'  // Fichiers ProEngineer
		, 'rtf'=>'application/rtf'  // Format de texte enrichi
		, 'set'=>'application/set'  // Fichiers CAO SET
		, 'stl'=>'application/sla'  // Fichiers stereolithographie
		, 'dwg'=>'application/solids'  // Fichiers MATRA Solids
		, 'step'=>'application/step'  // Fichiers de donnees STEP
		, 'vda'=>'application/vda'  // Fichiers de surface
		, 'mif'=>'application/x-mif'  // Fichiers Framemaker
		, 'dwg'=>'application/x-csh'  // Script C-Shell (UNIX)
		, 'dvi'=>'application/x-dvi'  // Fichiers texte dvi
		, 'hdf'=>'application/hdf'  // Fichiers de donnees
		, 'latex'=>'application/x-latex'  // Fichiers LaTEX
		, 'nc'=>'application/x-netcdf'  // Fichiers netCDF
		, 'cdf'=>'application/x-netcdf'  // Fichiers netCDF
		, 'dwg'=>'application/x-sh'  // Script Bourne Shell
		, 'tcl'=>'application/x-tcl'  // Script Tcl
		, 'tex'=>'application/x-tex'  // fichiers Tex
		, 'texinfo'=>'application/x-texinfo'  // Fichiers eMacs
		, 'texi'=>'application/x-texinfo'  // Fichiers eMacs
		, 't'=>'application/x-troff'  // Fichiers Troff
		, 'tr'=>'application/x-troff'  // Fichiers Troff
		, 'troff'=>'application/x-troff'  // Fichiers Troff
		, 'man'=>'application/x-troff-man'  // Fichiers Troff/macro man
		, 'me'=>'application/x-troff-me'  // Fichiers Troff/macro ME
		, 'ms'=>'application/x-troff-ms'  // Fichiers Troff/macro MS
		, 'src'=>'application/x-wais-source'  // Source Wais
		, 'bcpio'=>'application/x-bcpio'  // CPIO binaire
		, 'cpio'=>'application/x-cpio'  // CPIO Posix
		, 'gtar'=>'application/x-gtar'  // Tar GNU
		, 'shar'=>'application/x-shar'  // Archives Shell
		, 'sv4cpio'=>'application/x-sv4cpio'  // CPIO SVR4n
		, 'sc4crc'=>'application/x-sv4crc'  // CPIO SVR4 avec CRC
		, 'tar'=>'application/x-tar'  // Fichiers compresses tar
		, 'man'=>'application/x-ustar'  // Fichiers compresses tar Posix
		, 'man'=>'application/zip'  // Fichiers compresses ZIP
		, 'au'=>'audio/basic'  // Fichiers audio basiques
		, 'snd'=>'audio/basic'  // Fichiers audio basiques
		, 'aif'=>'audio/x-aiff'  // Fichiers audio AIFF
		, 'aiff'=>'audio/x-aiff'  // Fichiers audio AIFF
		, 'aifc'=>'audio/x-aiff'  // Fichiers audio AIFF
		, 'wav'=>'audio/x-wav'  // Fichiers audio Wave
		, 'man'=>'image/gif'  // Images gif
		, 'ief'=>'image/ief'  // Images exchange format
		, 'jpg'=>'image/jpeg'  // Images Jpeg
		, 'jpeg'=>'image/jpeg'  // Images Jpeg
		, 'jpe'=>'image/jpeg'  // Images Jpeg
		, 'tiff'=>'image/tiff'  // Images Tiff
		, 'tif'=>'image/tiff'  // Images Tiff
		, 'cmu'=>'image/x-cmu-raster'  // Raster cmu
		, 'pnm'=>'image/x-portable-anymap'  // Fichiers Anymap PBM
		, 'pbm'=>'image/x-portable-bitmap'  // Fichiers Bitmap PBM
		, 'pgm'=>'image/x-portable-graymap'  // Fichiers Graymap PBM
		, 'ppm'=>'image/x-portable-pixmap'  // Fichiers Pixmap PBM
		, 'rgb'=>'image/x-rgb'  // Image RGB
		, 'xbm'=>'image/x-xbitmap'  // Images Bitmap X
		, 'xpm'=>'image/x-xpixmap'  // Images Pixmap X
		, 'man'=>'image/x-xwindowdump'  // Images dump X Window
		, 'zip'=>'multipart/x-zip'  // Fichiers archive zip
		, 'gz'=>'multipart/x-gzip'  // Fichiers archive GNU zip
		, 'gzip'=>'multipart/x-gzip'  // Fichiers archive GNU zip
		, 'htm'=>'text/html'  // Fichiers HTML
		, 'html'=>'text/html'  // Fichiers HTML
		, 'txt'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'g'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'h'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'c'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'cc'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'hh'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'm'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'f90'=>'text/plain'  // Fichiers texte sans mise en forme
		, 'rtx'=>'text/richtext'  // Fichiers texte enrichis
		, 'tsv'=>'text/tab-separated-value'  // Fichiers texte avec separation des valeurs
		, 'etx'=>'text/x-setext'  // Fichiers texte Struct
		, 'mpeg'=>'video/mpeg'  // Videos MPEG
		, 'mpg'=>'video/mpeg'  // Videos MPEG
		, 'mpe'=>'video/mpeg'  // Videos MPEG
		, 'qt'=>'video/quicktime'  // Videos QuickTime
		, 'mov'=>'video/quicktime'  // Videos QuickTime
		, 'avi'=>'video/msvideo'  // Videos Microsoft Windows
		, 'movie'=>'video/x-sgi-movie'  // Videos MoviePlayer
		);
		$ret = 'application/octet-stream';
		$ext = mb_strtolower(substr($f, stripos($f, '.')+1));
		if(array_key_exists($ext, $ext2mime))
		$ret = $ext2mime[$ext];
		//		printf("%s : %s : %s<br/>\n", $f, $ext, $ret);
		return($ret);
	}
}


function giveMeDocType($mime)
{
	$return = false;
	switch($mime)
	{
		case 'image/png':
		case 'image/gif':
		case 'image/bmp':
		case 'image/x-ms-bmp':
		case 'image/jpeg':
		case 'image/pjpeg':
		case 'image/psd':
		case 'image/photoshop':
		case 'image/vnd.adobe.photoshop':
		case 'image/ai':
		case 'image/illustrator':
		case 'image/vnd.adobe.illustrator':
		case 'image/tiff':
		case 'image/x-photoshop':
		case 'application/postscript':
		case 'image/x-tika-canon':
		case 'image/x-tika-casio':
		case 'image/x-tika-dng':
		case 'image/x-tika-epson':
		case 'image/x-tika-fuji':
		case 'image/x-tika-hasselblad':
		case 'image/x-tika-imacon':
		case 'image/x-tika-kodak':
		case 'image/x-tika-leaf':
		case 'image/x-tika-logitech':
		case 'image/x-tika-mamiya':
		case 'image/x-tika-minolta':
		case 'image/x-tika-nikon':
		case 'image/x-tika-olympus':
		case 'image/x-tika-panasonic':
		case 'image/x-tika-pentax':
		case 'image/x-tika-phaseone':
		case 'image/x-tika-rawzor':
		case 'image/x-tika-red':
		case 'image/x-tika-sigma':
		case 'image/x-tika-sony':
		case 'image/x-portable-pixmap':
				
			$return = 'image';
			break;

		case 'video/mpeg':
		case 'video/mp4':
		case 'video/x-ms-wmv':
		case 'video/x-ms-wmx':
		case 'video/avi':
		case 'video/mp2p':
		case 'video/mp4':
		case 'video/x-ms-asf':
		case 'video/quicktime':
		case 'video/matroska':
		case 'video/x-msvideo':
		case 'video/x-ms-video':
		case 'video/x-flv':
		case 'video/avi':
		case 'video/3gpp':
		case 'video/x-m4v':
		case 'application/vnd.rn-realmedia':
			$return = 'video';
			break;

		case 'audio/aiff':
		case 'audio/aiff':
		case 'audio/x-mpegurl':
		case 'audio/mid':
		case 'audio/mid':
		case 'audio/mpeg':
		case 'audio/ogg':
		case 'audio/mp4':
		case 'audio/scpls':
		case 'audio/vnd.rn-realaudio':
		case 'audio/x-pn-realaudio':
		case 'audio/wav':
		case 'audio/x-wav':
		case 'audio/x-ms-wma':
			$return = 'audio';
			break;

			// default et aussi les fichiers ou on ne sais pas faire de thumbnail/preview
		case 'text/plain':
		case 'application/msword':
		case 'application/access':
		case 'application/pdf':
		case 'application/excel':
		case 'application/vnd.ms-powerpoint':
		case 'application/vnd.oasis.opendocument.formula':
		case 'application/vnd.oasis.opendocument.text-master':
		case 'application/vnd.oasis.opendocument.database':
		case 'application/vnd.oasis.opendocument.formula':
		case 'application/vnd.oasis.opendocument.chart':
		case 'application/vnd.oasis.opendocument.graphics':
		case 'application/vnd.oasis.opendocument.presentation':
		case 'application/vnd.oasis.opendocument.speadsheet':
		case 'application/vnd.oasis.opendocument.text':
			$return = 'document';
			break;

		case 'application/x-shockwave-flash':
			$return = 'flash';
			break;

		default:
			$return = 'unknown';
			break;
	}
	if(GV_debug)
	{
		// echo "\n detection doctype : $return\n";
	}
	
	return $return;
}



function make1flashsubdef($infile, $sd, $physdpath, $infos)
{

	$debug = false;

	if($debug)
	{
		echo "make 1 flash\n";
	}

	$return_value = false;

	if(!GV_swf_extract && !GV_swf_render)
	{
		return false;
	}

	if($debug)
	{
		echo " oui swf exctrat\n";
	}

	$file_extracted = false;

	if(GV_swf_extract)
	{
		$cmd = GV_swf_extract.' '.escapeshellarg($infile);

		$nullfile = '/dev/null';

		$descriptors = array();
		$descriptors[1] = array('pipe', 'w');
		$descriptors[2] = array('pipe', 'w'); // stderr is a file to write to

		$pipes = array();
		$process = proc_open($cmd, $descriptors, $pipes);

		$err = $stdout = '';

		$id = false;
		if (is_resource($process))
		{
			while (!feof($pipes[2]))
			$err .= fgets($pipes[2], 1024);
			fclose($pipes[2]);
			while (!feof($pipes[1]))
			$stdout .= fgets($pipes[1], 1024);
			fclose($pipes[1]);
			proc_close($process);
				
				
			$lines = explode("\n",$stdout);
			foreach($lines as $l)
			{
				if(substr(trim($l),0,4) == '[-j]')
				{
					$id = ' -j '.array_pop(explode('-',array_pop(explode(' ',trim($l)))));
					$ext = '.jpg';
					break;
				}
				if(substr(trim($l),0,4) == '[-p]')
				{
					$id = ' -p '.array_pop(explode('-',array_pop(explode(' ',trim($l)))));
					$ext = '.png';
					break;
				}

			}
				
		}
		if($id)
		{

			if($debug)
			{
				echo "jai bien le type de fichier : $id\n";
			}
				
			$infile_tmp = GV_RootPath.'tmp/temp_flash_'.time().mt_rand(1000,9999).$ext;
			exec(GV_swf_extract.$id.' '.escapeshellarg($infile).' -o '.escapeshellarg($infile_tmp));
				
			if(file_exists($infile_tmp))
			$file_extracted = true;

			if($debug)
			{
				echo "execution fichier temporaire\n";
			}
		}
	}

	if(GV_swf_render && !$file_extracted)
	{
		$infile_tmp = GV_RootPath.'tmp/temp_flash_'.time().mt_rand(1000,9999).'.png';

		$cmd = GV_swf_render.' -l '.escapeshellarg($infile).' -o '.escapeshellarg($infile_tmp);
		exec($cmd);

		if(file_exists($infile_tmp))
		$file_extracted = true;
	}

	if(!$file_extracted)
	return false;


	if(($subdefs = make1subdef($infile_tmp,$sd, $physdpath, $infos)) != false)
	{
		unlink($infile_tmp);
		return $subdefs;
	}

	unlink($infile_tmp);
	return false;
}


/**
 * cree UNE subdef
 */
function make1subdef($infile, $sd, $physdpath, $infos)
{
	$debug = false;

	$return_value = false;

	$system = p4utils::getSystem();
	
	$newname = $infos['recordid'] . '_' . (string)$sd->attributes()->name . '.jpg';
	$outfile = $physdpath . $newname;

	$sdsize = (int)($sd->size);

	$dpi = (int)($sd->dpi);
	if($dpi <= 0 || $dpi > 32767)
	$dpi = null;

	$quality = (int)($sd->quality);
	if($quality <= 0 || $quality > 100)
	$quality = 75;

	$strip = (string)$sd->strip;
	$strip = $strip=='' || p4field::isyes($strip);		// vrai par defaut

	$engine = '';

	if($sdsize > 15)
	{
		$err='';

		// supprimer la precedente subdef pour pouvoir tester plus bas la creation d'un nouveau fichier
		@unlink($outfile);

		//------------------------------------------------------------
		// on essaye SIPS ?
		//------------------------------------------------------------
		if($system == 'DARWIN' && !$infos['CMYK'] )
		{
			if($debug)
			echo "\n- Try SIPS\n";

			$engine = 'SIPS';

			if($infos['width']>0 && $infos['height']>0 && $infos['width']<$sdsize && $infos['height']<$sdsize)
			{
				// the doc is smaller than the wanted subdef, so adjust $sdsize
				$sdsize = max($infos['width'], $infos['height']);
			}
			$cmd  = 'sips';
			$cmd .= ' -s format jpeg';
			$cmd .= ' -s formatOptions '.$quality;
			$cmd .= ' -Z '.$sdsize;
			if($dpi)
			$cmd .= ' -s dpiHeight '.$dpi.' -s dpiWidth '.$dpi;
			if(isset($infos['Orientation']))
			{
				switch($infos['Orientation'])
				{
					case 3:		// 180 pour corriger
						$cmd .= ' -r 180';
						break;
					case 6:		// -90 trigo pour corriger
						$cmd .= ' -r 90';
						break;
					case 8:		// 90 trigo pour corriger
						$cmd .= ' -r 270';
						break;
				}
			}

			$cmd .= "'".$infile."' --out '" . $outfile . "'";
				
			if($debug)
				echo "\ncmd : ".$cmd."\n";

			$nullfile = '/dev/null';
				
			$descriptors = array();
			$descriptors[1] = array('file', $nullfile, 'a+t');
			$descriptors[2] = array('pipe', 'w'); // stderr is a file to write to

			$pipes = array();
			$process = proc_open($cmd, $descriptors, $pipes);
			if (is_resource($process))
			{
				while (!feof($pipes[2]))
				$err .= fgets($pipes[2], 1024);
				fclose($pipes[2]);
				proc_close($process);
				if($err!='')
				{
					if($debug)
						echo "\n- SIPS_ERR : \n$err\n\n";

					//  $prop["docs"][0]["msgError"].="\nSIPS\n------\n".$err."\n";
					if( file_exists($outfile) )
						unlink($outfile);
				}
			}
		}

		// Try to extract from raw datas
		if(isRaw($infos['mime']) && (!file_exists($outfile)) || $err!='')
		{

			$engine = 'EXIFTOOL';
			$tmpFiles = array();
				
			if($system == 'WINDOWS')
				$cmd = 'start /B /WAIT /LOW ' . GV_exiftool;
			else
				$cmd = GV_exiftool;
				
			$thisFile = $tmpFiles[] = GV_RootPath.'tmp/'.time().'-PI';
				
			$cmd .= ' -b -PreviewImage "'.$infile .'" > "'. $thisFile .'"';
				
			exec($cmd);
				
			if($system == 'WINDOWS')
				$cmd = 'start /B /WAIT /LOW ' . GV_exiftool;
			else
				$cmd = GV_exiftool;
				
			$thisFile = $tmpFiles[] = GV_RootPath.'tmp/'.time().'-JP';
				
			$cmd .= ' -b -JpgFromRaw "'.$infile .'" > "'. $thisFile .'"';
				
			exec($cmd);
				
			$refSize = 0;
			$tmpFile = false;

			foreach($tmpFiles as $file)
			{
				if(is_file($file) && filesize($file) > 0)
				{
					if(filesize($file) > $refSize)
					{
						$tmpFile = $file;
						$refSize = filesize($file);
					}
					else
						unlink($file);
				}
				else
					unlink($file);
			}
				
				
			if(is_file($tmpFile) && filesize($tmpFile) > 0)
			{

				$sdsize = (int)$sd->size;
				$recalc = getimagesize($tmpFile);

				if(isset($recalc[0]) && isset($recalc[1]))
				{
					$infos['width'] = $recalc[0];
					$infos['height'] = $recalc[1];
				}

				if($infos['width']>0 && $infos['height']>0 && $infos['width']<$sdsize && $infos['height']<$sdsize)
				{
					$sdsize = max($infos['width'], $infos['height']);
				}

				if($system == 'WINDOWS')
					$cmd = 'start /B /WAIT /LOW ' . GV_imagick;
				else
					$cmd = GV_imagick;
					
				$cmd .= ' -colorspace RGB -flatten -alpha Off -quiet';

				if($strip)
					$cmd .= ' -strip';

				$cmd .= ' -quality ' . $quality . ' -resize ' . $sdsize . 'x' . $sdsize;

				if($dpi)
					$cmd .= ' -density '.$dpi.'x'.$dpi.' -units PixelsPerInch';

				if($infos['mime']=='application/pdf' || $infos['mime']=='application/postscript')
					$cmd .= ' -geometry ' . $sdsize.'x'.$sdsize;

				if(isset($infos['Orientation']))
				{
					switch($infos['Orientation'])
					{
						case 3:		// 180 pour corriger
							$cmd .= ' -rotate 180';
							break;
						case 6:		// -90 trigo pour corriger
							$cmd .= ' -rotate 90';
							break;
						case 8:		// 90 trigo pour corriger
							$cmd .= ' -rotate -90';
							break;
					}
				}

				// attention, au cas ou il y aurait des espaces dans le path, il faut des quotes
				// windows n'accepte pas les simple quotes
				// pour mac les quotes pour les noms de fichiers sont indispensables car si il y a un espace -> ca plante
				if(in_array($infos['mime'],array('image/tiff','application/pdf','image/psd','image/vnd.adobe.photoshop','image/photoshop','image/ai','image/illustrator','image/vnd.adobe.illustrator')))
					$cmd .= ' "'.$tmpFile .'[0]" "'. $outfile .'"';
				else
					$cmd .= ' "'.$tmpFile .'" "'. $outfile .'"';

				exec($cmd);
				unlink($tmpFile);
			}
		}
		

		//------------------------------------------------------------
		// on essaye IMAGEMAGICK ?
		//------------------------------------------------------------
		if( ( !file_exists($outfile) || $err!='' ) && GV_imagick!='')// && !$infos['CMYK']
		{

			if($debug)
			echo "\n- Try IMAGICK\n";

			$sdsize = (int)$sd->size;

			$engine = 'IMAGEMAGICK';
				
			if(!isRaw($infos['mime']) && $infos['width']>0 && $infos['height']>0 && $infos['width']<$sdsize && $infos['height']<$sdsize)
			{
				$sdsize = max($infos['width'], $infos['height']);
			}

			if($system == 'WINDOWS')
				$cmd = 'start /B /WAIT /LOW ' . GV_imagick;
			else
				$cmd = GV_imagick;
				
			$cmd .= ' -colorspace RGB -flatten -alpha Off -quiet';

			if($strip)
				$cmd .= ' -strip';

			$cmd .= ' -quality ' . $quality . ' -resize ' . $sdsize . 'x' . $sdsize;

			if($dpi)
				$cmd .= ' -density '.$dpi.'x'.$dpi.' -units PixelsPerInch';

			$cmd .= ' -background white';
			
			if($infos['mime']=='application/pdf' || $infos['mime']=='application/postscript')
				$cmd .= ' -geometry ' . $sdsize.'x'.$sdsize;

			if(isset($infos['Orientation']))
			{
				switch($infos['Orientation'])
				{
					case 3:		// 180 pour corriger
						$cmd .= ' -rotate 180';
						break;
					case 6:		// -90 trigo pour corriger
						$cmd .= ' -rotate 90';
						break;
					case 8:		// 90 trigo pour corriger
						$cmd .= ' -rotate -90';
						break;
				}
			}

			// attention, au cas ou il y aurait des espaces dans le path, il faut des quotes
			// windows n'accepte pas les simple quotes
			// pour mac les quotes pour les noms de fichiers sont indispensables car si il y a un espace -> ca plante
			$array = array('image/tiff', 'application/pdf','image/psd','image/vnd.adobe.photoshop','image/photoshop','image/ai','image/illustrator','image/vnd.adobe.illustrator');
			if( in_array($infos['mime'], $array ) )
				$cmd .= ' "'.$infile .'[0]" "'. $outfile .'"';
			else
				$cmd .= ' "'.$infile .'" "'. $outfile .'"';
// printf("%s\n", $cmd);
			$res = exec($cmd);
			if($debug)
				echo "execution commande $cmd\n";
		}

		//------------------------------------------------------------
		// on essaye GD ?
		//------------------------------------------------------------
		if((!file_exists($outfile)) || $err!='')
		{
			if($debug)
			echo "\n- Try GD\n";

			$engine = 'GD2';

			$imag_original = @imagecreatefromjpeg($infile);
			if($imag_original)
			{
				$w_doc = imagesx($imag_original);
				$h_doc = imagesy($imag_original);

				if($w_doc < $sdsize && $h_doc < $sdsize)
				{
					// doc is too small, don't resize
					$img_mini = imagecreatetruecolor($w_doc, $h_doc);
					imagecopy($img_mini, $imag_original, 0,0,0,0, $w_doc, $h_doc);
				}
				else
				{
					// cherche le max des 2 valeurs pour creer le coeff de resize
					if($w_doc > $h_doc)
					$h_sub = (int)(($h_doc/$w_doc) * ($w_sub = $sdsize));
					else
					$w_sub = (int)(($w_doc/$h_doc) * ($h_sub = $sdsize));
					$img_mini = imagecreatetruecolor($w_sub, $h_sub);
						
					$sdmethod = trim((string)($sd->method));
					if(strtoupper($sdmethod) == 'RESAMPLE')
					imagecopyresampled($img_mini, $imag_original, 0,0,0,0, $w_sub, $h_sub, $w_doc, $h_doc);
					else
					imagecopyresized($img_mini, $imag_original, 0,0,0,0, $w_sub, $h_sub, $w_doc, $h_doc);
				}

				if(isset($infos['Orientation']))
				{
					switch($infos['Orientation'])
					{
						case 3:		// 180e pour corriger
							$img_mini = imagerotate($img_mini, 180, 0);
							// printf("rot 180<br/>");
							break;
						case 6:		// -90 trigo pour corriger
							$img_mini = imagerotate($img_mini, 270, 0);
							$z = $w_sub;
							$w_sub = $h_sub;
							$h_sub = $z;
							// printf("rot 270 trigo<br/>");
							break;
						case 8:		// 90 trigo pour corriger
							$img_mini = imagerotate($img_mini, 90, 0);
							$z = $w_sub;
							$w_sub = $h_sub;
							$h_sub = $z;
							// printf("rot 90 trigo<br/>");
							break;
					}
				}
				imagejpeg($img_mini, $outfile, $quality);

				imagedestroy($img_mini);
				imagedestroy($imag_original);
			}
		}


		if(file_exists($outfile))
		{
			if(function_exists('chgrp')&& GV_filesGroup!=null)
			$r = chgrp($outfile, GV_filesGroup);
			p4::chmod($outfile);
				
			$tempHD = getimagesize($outfile);
				
			$return_value = array();
			$return_value['engine'] = $engine;
			$return_value['file'] = $newname;
			$return_value['width'] = $tempHD[0];
			$return_value['height'] = $tempHD[1];
			$mimeExt = giveMimeExt($outfile);
			$return_value['mime'] = $mimeExt['mime'];
		}
		else
		{
			$return_value = array();
			$return_value['engine'] = '?';
			$return_value['file'] = $newname;
			$return_value['width'] = 666;
			$return_value['height'] = 666;
			$return_value['mime'] = 'image/jpeg';
		}

	}
	return($return_value);
}

function pdf_from_document($infile)
{
	$debug = false;
	if(!GV_unoconv)
		return false;

	$tmp_file = GV_RootPath.'tmp/tmp_doc_'.time().mt_rand(10000,99999).'.pdf';

	$cmd = GV_unoconv.' --format=pdf --stdout '.escapeshellarg($infile).' > '.escapeshellarg($tmp_file);

	if(GV_debug)
	{
		echo "\nexecution commande : $cmd\n";
	}
	
	$nullfile = '/dev/null';

	$descriptors = array();
	$descriptors[1] = array('pipe', 'w');
	$descriptors[2] = array('pipe', 'w'); // stderr is a file to write to

	$pipes = array();
	$process = proc_open($cmd, $descriptors, $pipes);

	$err = $stdout = '';

	$id = false;
	if (is_resource($process))
	{
		while (!feof($pipes[2]))
		$err .= fgets($pipes[2], 1024);
		fclose($pipes[2]);
		while (!feof($pipes[1]))
		$stdout .= fgets($pipes[1], 1024);
		fclose($pipes[1]);
		proc_close($process);
	}

	if(!is_file($tmp_file) || filesize($tmp_file) == 0)
		return false;
	return $tmp_file;
}

function swf_from_document($infile, $outfile)
{
	
	$infile = pdf_from_document($infile);
	
	if(!$infile)
		return false;

	return swf_from_pdf($infile, $outfile, true);
}

function swf_from_pdf($infile, $outfile, $delete_infile = false)
{
	$debug = false;
	if(!GV_pdf2swf)
		return false;
	
        $system = p4utils::getSystem();

	if($system == 'WINDOWS')
		$cmd = GV_pdf2swf.' "'.$infile .'" "'. $outfile .'" -T 9 -f';
	else
		$cmd = GV_pdf2swf.' "'.$infile .'" "'. $outfile .'" -Q 300 -T 9 -f';

	if(GV_debug)
	{
		echo "\nexecution commande : $cmd\n";
	}
	
	$nullfile = '/dev/null';

	$descriptors = array();
	$descriptors[1] = array('pipe', 'w');
	$descriptors[2] = array('pipe', 'w'); // stderr is a file to write to

	$pipes = array();
	$process = proc_open($cmd, $descriptors, $pipes);

	$err = $stdout = '';

	$id = false;
	if (is_resource($process))
	{
		while (!feof($pipes[2]))
		$err .= fgets($pipes[2], 1024);
		fclose($pipes[2]);
		while (!feof($pipes[1]))
		$stdout .= fgets($pipes[1], 1024);
		fclose($pipes[1]);
		proc_close($process);
	}

	if($delete_infile)
		@unlink($infile);
	
	return $outfile;
}


function make1Documentsubdef($infile, $sd, $physdpath, $infos)
{
	$debug = false;

	if(GV_debug)
	{
		echo "\nPreparation des sous defintions Document\n";
	}
	
	$newname = $infos['recordid'] . '_' . (string)$sd->attributes()->name . '.jpg';
	$outfile = $physdpath . $newname;
	
	if($sd->attributes()->name == 'preview')
	{
		$newname = $infos['recordid'] . '_' . (string)$sd->attributes()->name . '.swf';
		$outfile = $physdpath . $newname;
		
		if($infos['mime'] == 'application/pdf')
		{
			if(swf_from_pdf($infile, $outfile))
			{
				$retour = array();
				$retour['width'] = 0;
				$retour['height'] = 0 ;
				$retour['mime'] = 'application/x-shockwave-flash';
				$retour['file'] = $newname ;
				return $retour;
			}
		}
		else
		{
			if(swf_from_document($infile, $outfile))
			{
				$retour = array();
				$retour['width'] = 0;
				$retour['height'] = 0 ;
				$retour['mime'] = 'application/x-shockwave-flash';
				$retour['file'] = $newname ;
				return $retour;
			}
		}
		
	}
	else
	{
		if($infos['mime'] == 'application/pdf')
		{
			$tmp_file = $infile;
			$delete = false;
		}
		else
		{
			$tmp_file = pdf_from_document($infile);
			$delete = true;
		}
		
		if($tmp_file)
		{
			$infos['mime'] = 'application/pdf';
			if(($subdefs = make1subdef($tmp_file,$sd, $physdpath, $infos)) != false)
			{
				if($delete)
					unlink($tmp_file);
				return $subdefs;
			}
		}
	}
	return false;
}

function make1AudioSubdef($infile, &$sd, $physdpath, &$infos)
{

	$debug = false;


	$retour = false;
	$sdtype = trim(mb_strtolower($sd->mediatype)); //MP3/JPG
	$sdname = $sd->attributes()->name;
	$sdtypeOK = array('audio','image');

	if($debug)
	echo "\n::::::::::::::::::::::::::::::::::::::::::::::::::\narrivee dans make1audiosubdef AVEC PARAMS : sdname - $sdname et sdtype - $sdtype \n:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::\n";
	if(!in_array($sdtype,$sdtypeOK))
	return $retour;

		
	if($sdtype == 'audio')
	{
		if($debug)
			echo "\nGENERATING " . $sdname . "  ".$sdtype."....\n";

		$newname = $infos['recordid'] . '_' . $sdname . '.mp3';
		$dest = $physdpath.$newname;
			
		//				$audioEnc = '';
		//				if(trim($srcAB) != '' && trim($srcAB) !='')
		//				{
		//					$okMp3BR = array('44100'=>true, '22050'=>true, '11025'=>true);
		//					if(!isset($srcAR) || trim($srcAR) == '' || !array_key_exists($srcAR,$okMp3BR))
		//						$srcAR = '44100';
		//
		//					if($srcAB == '0' || trim($srcAB)=='')
		//						$srcAB = '0';
		//
		//					$audioEnc = ' -ar ' . $srcAR . ' -ab ' . $srcAB .'k -acodec libmp3lame ';
		//				}
			
		$ffmpeg = trim(GV_ffmpeg)!=''?GV_ffmpeg:false;

		if($ffmpeg)
		{
			$cmd = $ffmpeg . ' -i \'' . $infile .'\' ' . $dest;

			if($debug)
				echo "\n\n\n\nEXECUTION COMMANDE ::::   ".$cmd."\n\n\n\n";

			$errArr = '';
			@exec($cmd, $errArr);

			$retour['width'] = 0 ;
			$retour['height'] = 0 ;
			$retour['mime'] = 'audio/mpeg'; //'image/jpeg' ;
			$retour['file'] = $newname ;
		}
	}

	if($sdtype == 'image')
	{
		if($debug)
			echo "\nGENERATING " . $sdname . "  ".$sdtype."....\n";
			
		$newname = $infos['recordid'] . '_' . $sdname . '.jpg';
		$dest = $physdpath.$newname;

		//On check si y'a pas une image integre au bouzi
		$out = null;
		$cmd = GV_exiftool.' -s -t '.$infile;
		exec($cmd,$out);
			
		$found = false;
		if($out)
		{
			foreach($out as $outP)
			{
				$infos = explode("\t", $outP);
				if(count($infos) == 2)
				{
					if($infos[0] == 'Picture')
					{
						if($debug)
						echo "\nUne emebedded pic est presente dans l'image, on l'extrait...\n";
							
						$cmd = GV_exiftool.' -b -Picture '.$infile.' > '.$dest;
						exec($cmd);
						if(is_file($dest))
						{
							if(filesize($dest)>0)
							{
								$retour['width'] = 0 ;
								$retour['height'] = 0 ;

								$sizes = null;
								$sizes = getimagesize($dest);
								if($sizes)
								{
									$retour['width'] = $sizes[0] ;
									$retour['height'] = $sizes[1] ;
								}
								$mimeOut = giveMimeExt($dest);
								$retour['mime'] = $mimeOut['mime']; //'image/jpeg' ;
								$retour['file'] = $newname ;
								$found = true;
							}
							else
							{
								@unlink($dest);
							}
						}
					}
				}
			}
		}
		if(!$found)
		{
			if(copy(GV_RootPath.'www/skins/icons/audio.png',$dest))
			{
				$retour['width'] = 128 ;
				$retour['height'] = 128 ;
				$retour['mime'] = 'image/jpeg';
				$retour['file'] = $newname ;
			}
		}
	}

	if($debug){
		echo "\n ce que l'on retourne :";
		var_dump($retour);
	}

	return $retour;
}




function make1VideoSubdef($infile, &$sd, $physdpath, &$infos)
{


	$debug = false;

	$system = p4utils::getSystem();
	
	$retour = false;
	$sdtype = trim(mb_strtolower($sd->mediatype));
	$sdname = $sd->attributes()->name;
	$sdtypeOK = array('gif','video','image');
	
	if($debug)
		echo "\n::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::\narrivee dans make1videosubdef AVEC PARAMS : sdname - $sdname et sdtype - $sdtype \n:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::\n";
	if(!in_array($sdtype,$sdtypeOK))
	{
		if($debug)
			echo "nothing avalaible\n";
		return $retour;
	}
	$ffmpeg = false;
		
	$ffmpeg = trim(GV_ffmpeg)!=''?GV_ffmpeg:false;
	
	$videoprops = getVideoInfos($infile);
	
	if($debug)
	{
		echo "voila les videoprops : \n";var_dump($videoprops);
	}

	
	$ratio_supposed = (int)$videoprops['width'] / (int)$videoprops['height'];
	$ratio = $ratio_get = (float)$videoprops['video_aspect'];
	
	if($ratio_get == 0)
	{
		$ratio = $ratio_supposed; 
	}
	if($ratio != $ratio_supposed)
	{
		$videoprops['width'] = $ratio * $videoprops['height'];
		if($debug)
			echo "correction des dimensions via aspect ratio\n";
	}
	
	
	if($debug)
		echo "Check de la width\n";
	$srcWidth =  get_multiple ($videoprops['width'],16);
	if($debug)
		echo "Check de la height\n";
	$srcHeight = get_multiple ($videoprops['height'],16);
	if($debug)
		echo "Check framerate\n";
	$srcFPS = $videoprops['frameRate'];
	if($debug)
		echo "Check audiobitrate\n";
	$srcAB = intval($videoprops['audiobitrate']/1000);
	if($debug)
		echo "Check audiop sample rate\n";
	$srcAR = $videoprops['audiosamplerate'];
		
		
	//calcul des dimensions de la subdef :
	$maxSize = get_multiple ($sd->size, 16, 'bottom');
			
	if($debug)
		echo "\nLa source est a  ".$srcWidth."x".$srcHeight."....\n";
	if($debug)
		echo "\nLa taille max est a  ".$maxSize."....\n";
		
	if($srcWidth>$maxSize || $srcHeight>$maxSize)
	{
		//une des dimensions est superieure
		if($srcWidth>=$srcHeight)//paysage
		{
			$newWidth = (int)$maxSize;
			$newHeight = get_multiple (round($newWidth * $srcHeight / $srcWidth), 16);
		}
		else
		{
			$newHeight = (int)$maxSize;
			$newWidth = get_multiple (round($newHeight * $srcWidth / $srcHeight), 16);
		}
	}
	else
	{
		$newHeight = get_multiple ($srcHeight,16);
		$newWidth = get_multiple ($srcWidth, 16);
	}
			
	if($debug)
		echo "\nLa sortie est   ".$newWidth."x".$newHeight."....\n";
			
			
			
			
	if($sdtype == 'video' && $ffmpeg)
	{
		
		$cwd = getcwd();
		chdir(GV_RootPath.'tmp/');
		
		if($debug)
			echo "\nGENERATING " . $sdname . "  ".$sdtype."....\n";

			
		
		$fps = 25;
		if(isset($sd->fps) && is_numeric((int)$sd->fps))
			$fps = (int)$sd->fps;
			
		$bit_rate = 1000;
		if(isset($sd->bitrate) && is_numeric((int)$sd->bitrate))
			$bit_rate = (int)$sd->bitrate;
		$nb_threads = 1;
		if(isset($sd->threads) && is_numeric((int)$sd->threads))
			$nb_threads = (int)$sd->threads;
	
		$v_codec = 'libx264';
		
		$v_codecs = array(
			'x264'		=> 'libx264',
			'h264'		=> 'libx264',
			'libx264'	=> 'libx264',
			'libh264'	=> 'h264',
			'flv'		=> 'flv',
			'flash'		=> 'flv'
		);
		
		if(isset($sd->vcodec))
		{
			$t_v_codec = (string)$sd->vcodec;
			if(array_key_exists($t_v_codec,$v_codecs))
				$v_codec = $v_codecs[$t_v_codec];
		}
		
		$a_codec = 'libfaac';
		
		$a_codecs = array(
			'faac'		=> 'libfaac',
			'libfaac'	=> 'libfaac',
			'mp3'		=> 'libmp3lame'
		);
		
		if(isset($sd->acodec))
		{
			$t_a_codec = (string)$sd->acodec;
			if(array_key_exists($t_a_codec,$a_codecs))
				$a_codec = $a_codecs[$t_a_codec];
		}
		
		if($v_codec == 'flv')
			$newname = $infos['recordid'] . '_' . $sdname . '.flv';
		else
			$newname = $infos['recordid'] . '_' . $sdname . '.mp4';
			
		$dest = $physdpath.$newname;
		$dest_pass1 = $dest.'-tmp.mp4';
		
		if($system == 'WINDOWS')
			$cmd_part1 = $ffmpeg . ' -y -i \'' . str_replace('/', "\\", $infile) .'\' ';
		else
			$cmd_part1 = $ffmpeg . ' -y -i \'' . $infile .'\' ';
			
		if(in_array($v_codec, array('libx264','h264')))
		{
			
			$cmd_part2 = ' -s '.$newWidth.'x'.$newHeight.' -r '.$fps.' -vcodec '.trim($v_codec).'  -b '.trim($bit_rate).'k -g 25 -bf 3'.
				' -threads '.$nb_threads.' -refs 6 -b_strategy 1 -coder 1 -qmin 10 -qmax 51 -sc_threshold 40 -flags +loop -cmp +chroma'.
				' -me_range 16 -subq 7 -i_qfactor 0.71 -qcomp 0.6 -qdiff 4 -directpred 3 -flags2 +dct8x8+wpred+bpyramid+mixed_refs'.
				' -trellis 1 -partitions +parti8x8+parti4x4+partp8x8+partp4x4+partb8x8 -acodec '.trim($a_codec).' -ab 92k ';	
			
			$cmd_pass1 = $cmd_part1 .' -pass 1 '.$cmd_part2.' -an '.$dest_pass1;
			$cmd_pass2 = $cmd_part1 .' -pass 2 '.$cmd_part2.' -ac 2 -ar 44100 '.$dest;
			
			if($debug)
				echo "\n\n\n\nEXECUTION COMMANDE ::::   ".$cmd_pass1."\n\n\n\n";
			
			
			if(is_file($dest))
				unlink($dest);

			$errArr = '';
			@exec($cmd_pass1, $errArr);
			if($debug)
				echo "\n\n\n\nEXECUTION COMMANDE ::::   ".$cmd_pass2."\n\n\n\n";
				
			$errArr = '';
			@exec($cmd_pass2, $errArr);
	
			if(is_file($dest_pass1))
				unlink($dest_pass1);
			make_mp4_progressive($dest);
		}
		else
		{
			$audioEnc = '';
			if(trim($srcAB) != '' && trim($srcAB) !='')
			{
				$okMp3BR = array('44100'=>true, '22050'=>true, '11025'=>true);
				if(!isset($srcAR) || trim($srcAR) == '' || !array_key_exists($srcAR,$okMp3BR))
				$srcAR = '44100';
					
				if($srcAB == '0' || trim($srcAB)=='')
				$srcAB = '0';
					
				$audioEnc = ' -ar ' . $srcAR . ' -ab ' . $srcAB .'k -acodec libmp3lame ';
			}


			$fps = 15;
			if(isset($sd->fps) && is_numeric((int)$sd->fps))
				$fps = (int)$sd->fps;

			if($system == 'WINDOWS')
				$cmd = $ffmpeg . ' -y -i \'' . str_replace('/', "\\", $infile) .'\' ';
			else
				$cmd = $ffmpeg . ' -y -i \'' . $infile .'\' ';
				
			$cmd .=	$audioEnc .
		        ' -f flv -nr 500 -s '.$newWidth.'x'.$newHeight.'' .
					' -r '.$fps.//$srcFPS.
		    	' -b 270k -me_range '.$srcFPS.' -i_qfactor 0.71 -g 500 ' . $dest ;
			
			
			if($debug)
				echo "\n\n\n\nEXECUTION COMMANDE ::::   ".$cmd."\n\n\n\n";
				
			$errArr = '';
			@exec($cmd, $errArr);
				
		}
			
		$retour['width'] = $newWidth ;
		$retour['height'] = $newHeight ;
		
		if($v_codec == 'flv')
			$retour['mime'] = 'video/x-flv'; //'image/jpeg' ;
		else
			$retour['mime'] = 'video/mp4'; //'image/jpeg' ;
			
		$retour['file'] = $newname ;
		
		chdir($cwd);
	}
			
	if($sdtype == 'image')
	{
		if($debug)
			echo "\nGENERATING " . $sdname . "  ".$sdtype."....\n";
			
		
		$newname = $infos['recordid'] . '_' . $sdname . '.jpg';
		$dest = $physdpath.$newname;

		$tmpDir = GV_RootPath.'tmp/'.'tmp'.time();
		p4::fullmkdir($tmpDir);
		$tmpDir = p4string::addEndSlash($tmpDir);
		
		$tmpFile = $tmpDir.$newname.'-tmp.jpg';
			
		$time_tot = $videoprops['duration'];
		
		$time_cut = round($videoprops['duration'] * 0.6);

		$time_cut = $time_cut < 1 ? 1 : $time_cut; 
		
		if($system == 'WINDOWS')
			$cmd = GV_ffmpeg.' -i '.str_replace('/', "\\", $infile).' -s '.$newWidth.'x'.$newHeight.' -vframes 1 -ss '.$time_cut.'  -f image2 '.$tmpFile;
		else
			$cmd = GV_ffmpeg.' -i '.$infile.' -s '.$newWidth.'x'.$newHeight.' -vframes 1 -ss '.$time_cut.'  -f image2 '.$tmpFile;
		$errArr = '';
		@exec($cmd, $errArr);
		
		echo "\nCommande executee : $cmd \n";

		if(file_exists($tmpFile))
		{
			$cmd = GV_pathcomposite.' -gravity SouthEast -quiet -compose over "'.GV_RootPath.'www/skins/icons/play.png" "'.$tmpFile.'" "'.$dest.'"';
	
			$errArr = '';
			exec($cmd, $errArr);
			unlink($tmpFile);
		
			echo "\nLE TEMP FILE : $tmpFile --- \n la cmd : $cmd \n";
		}
			
		if(is_dir($tmpDir))
			@rmdir($tmpDir);
		
		$retour['width'] = $newWidth ;
		$retour['height'] = $newHeight ;
		$retour['mime'] = 'image/jpeg'; //'image/jpeg' ;
		$retour['file'] = $newname ;
	}
		
		
	if($sdtype == 'gif')
	{
		if($debug)
			echo "\nGENERATING " . $sdname . "  ".$sdtype."....\n";

		//on calcule l'intervalle entre deux images clef et on va determiner combien d'image clef sauter pour chaque extrait
			
		$newname = $infos['recordid'] . '_' . $sdname . '.gif';
		$dest = $physdpath.$newname;
			
		$tmpDir = GV_RootPath.'tmp/'.'tmp'.time();
		p4::fullmkdir($tmpDir);
		$tmpDir = p4string::addEndSlash($tmpDir);



		if($system == 'WINDOWS')
			$cmd = GV_ffmpeg.' -s '.$newWidth.'x'.$newHeight.' -i '.str_replace('/', "\\", $infile).' -r 1 -f image2 '.$tmpDir.'images%05d.jpg';
		else
			$cmd = GV_ffmpeg.' -s '.$newWidth.'x'.$newHeight.' -i '.$infile.' -r 1 -f image2 '.$tmpDir.'images%05d.jpg';
		$errArr = '';
		@exec($cmd, $errArr);

		$files = array();

		if($hdir = opendir($tmpDir))
		{
			while($file = readdir($hdir))
			{
				if(!in_array($file, array('.','..')))
				{
					$files[$file] = $tmpDir.$file;
				}
			}
		}
		ksort($files);

		$n = count($files);

		$inter = round(count($files) / 10);


		$i = 0;
		foreach($files as $k=>$file)
		{
			if($i % $inter !== 0)
			{
				if(unlink($file))
				unset($files[$k]);
			}
			else
			{
				if($srcWidth!=$newWidth || $srcHeight!=$newHeight)
				{
					resizeImage($file,$newWidth,$newHeight);
					if($debug)
						echo "\nOn resize le GIF KeyFrame a ".$newWidth." ".$newHeight."....\n";
				}

				if(is_numeric($i))
				{
					$d = answer::format_duration($i);
						
					if($system == 'WINDOWS')
						passthru(GV_imagick.' -fill white -pointsize 15 -undercolor black -gravity south -draw "text 0,0 \''.$d.'\'" '.str_replace('/', "\\", $file).' '.str_replace('/', "\\", $file));
					else
						passthru(GV_imagick.' -fill white -pointsize 15 -undercolor black -gravity south -draw "text 0,0 \''.$d.'\'" '.$file.' '.$file);
				}
			}

			$i++;
		}

		passthru(GV_imagick.' -delay 100 -loop 0   '.$tmpDir.'*.jpg '.$dest);

		foreach($files as $file)
			unlink($file);
			
		if(is_dir($tmpDir))
			@rmdir($tmpDir);
		$retour['width'] = $newWidth ;
		$retour['height'] = $newHeight ;
		$retour['mime'] = 'image/gif'; //'image/jpeg' ;
		$retour['file'] = $newname ;
	}

	if($debug)
	{
		echo "\n ce que l'on retourne :";
		var_dump($retour);
	}

	return $retour;
}

function make_mp4_progressive($mp4_file)
{
	$debug = false;
	
	if($debug)
		echo "\nchecking mp4 box for mp4 progressive video ....\n";
	if(defined('GV_mp4box') && is_executable(GV_mp4box))
	{
		
		if($debug)
			echo "\nmp4 box OK, doing it\n";
			
		$cmd = GV_mp4box.' -inter 0.5 '.escapeshellarg($mp4_file);
		$errArr = '';
		@exec($cmd, $errArr);
		
		$ret = true;
	}
	else
	{
		if($debug)
			echo "\nNO mp4 box OK, PHP way\n";
			
		$tmpFile = $mp4_file.'-tmp.mp4';
		$moovrelocator = moov_relocator::getInstance();
		
		$debug = false;
		
		if($debug)
			echo "\nfichier en arrivee : $mp4_file : ".filesize($mp4_file)." \n";
		
	    $ret = $moovrelocator->setInput($mp4_file);
	
	    if($ret === true)
	        $ret = $moovrelocator->setOutput($tmpFile);
	
	    if($ret === true)
	        $ret = $moovrelocator->fix();
	
	    if($ret === true) {
			unlink($mp4_file);
			rename($tmpFile,$mp4_file);
		}
	}
	
	
	if($debug)
		echo "\nfichier en sortie : $mp4_file : ".filesize($mp4_file)." \n";
		
	return $ret;
}

function get_multiple ($value, $multiple, $bound='nearest')
{
	$modulo = $value % $multiple;
	
	$ret = 0;
	
	if($bound == 'nearest')
	{
		$half_distance = $multiple / 2;
		if($modulo <= $half_distance)
			$bound = 'bottom';
		else
			$bound = 'top';
	}
	
	switch($bound)
	{
		default:
		case 'top':
			$ret = $value + $multiple - $modulo;
			break;
		case 'bottom':
			$ret = $value - $modulo;
			break;
	}
	
	if($ret < $multiple)
		$ret = $multiple;
	
	return (int) $ret;
}

function resizeImage($file, $width, $height)
{
	$fullImage = imagecreatefromjpeg($file);

	$fullSize = getimagesize($file);

	if (!file_exists($file.'_resized'))
	{
		$tnImage = imagecreatetruecolor($width,$height);

		imagecopyresampled($tnImage,$fullImage,0,0,0,0,$width,$height,$fullSize[0],$fullSize[1]);

		imagejpeg($tnImage, $file.'_resized');

		imagedestroy($fullImage);
		imagedestroy($tnImage);

		if(unlink($file))
		{
			if(rename($file.'_resized',$file))
				return true;
		}
	}

	return false;
}
?>
