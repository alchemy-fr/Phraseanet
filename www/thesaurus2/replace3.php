<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();
$session = session::getInstance();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
require(GV_RootPath."www/thesaurus2/xmlhttp.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "id"
					, "src"
					, "rpl"
					, "field"
					, "dlg"
				);


$lng = isset($session->locale)?$session->locale:GV_default_lng;
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}
else
{
	header("Location: /login/?error=auth&lng=".$lng);
	exit();
}
				
				
if($parm["dlg"])
{
	$opener = "window.dialogArguments.win";
}
else
{
	$opener = "opener";
}
?>
<html lang="<?php echo $session->usr_i18n;?>">
<body>
<?php
if($parm["bid"] !== null)
{
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		// table temporaire
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `tmprecord` (`xml` TEXT COLLATE utf8_general_ci) SELECT record_id, xml FROM record";
		// printf("sql:%s<br/>\n", htmlentities($sql));
		if($rsbas = $connbas->query($sql))
		{
			$src_noacc = noaccent_utf8($parm["src"]);
			$src_noacc_len = mb_strlen($src_noacc, "UTF-8");
			$src_noacc_tchar = array();
			for($i=0; $i<$src_noacc_len; $i++)
				$src_noacc_tchar[$i] = mb_substr($src_noacc, $i, 1, "UTF-8");
			
			$sql = "";
			foreach($parm["field"] as $field)
			{
				// $sql .= ($sql==""?"":" OR ") . "(xml REGEXP '<$field>.*[^a-z]".$src_noacc."[^a-z].*</$field>')";
				$sql .= ($sql==""?"":" OR ") . "(xml LIKE '%<$field>%".$src_noacc."%</$field>%')";
			}
			$sql = "SELECT record_id, BINARY xml AS xml FROM tmprecord WHERE $sql";
			
			if($rsbas2 = $connbas->query($sql))
			{
				$nrectot = $connbas->num_rows($rsbas2);
				$nrecdone = $nrecchanged = $nspot = 0;
				while($rowbas2 = $connbas->fetch_assoc($rsbas2))
				{
					$nrecdone++;
					printf("<script type=\"text/javascript\">parent.pbar($nrecdone, $nrectot);</script>\n");
					flush();

					set_time_limit(30);
					
					$xml = $rowbas2["xml"];
					$spots = array();
					foreach($parm["field"] as $field)
					{
						$ibyte_min = $ichar_min = 0;
						while(true)
						{
							if( ($ibyte_min = strpos($xml, "<$field>", $ibyte_min)) === false)
								break;
							$ibyte_min +=  strlen("<$field>");
							if( ($ibyte_max = strpos($xml, "</$field>", $ibyte_min)) === false)
								break;
							
							$ichar_min = mb_strpos($xml, "<$field>", $ichar_min, "UTF-8") + mb_strlen("<$field>");
							$ichar_max = mb_strpos($xml, "</$field>", $ichar_min, "UTF-8"); // + mb_strlen("</$field>");
							
							$txml = substr($xml, $ibyte_min, $ibyte_max-$ibyte_min);
							
							$xml_noacc_tchar = array();			// buffer circulaire taille+2 (car pr�c. et car suiv. pour trouver uniquement les mots entiers)
							$xml_noacc_tchar[0] = array(">", ">", 1);	// car pr�c�dent
							for($i=0; $i<$src_noacc_len+1; $i++)
							{
								$c = mb_substr($txml, 0, 1, "UTF-8");
								$xml_noacc_tchar[$i+1] = array($c, noaccent_utf8($c), $l=strlen($c));
								$txml = substr($txml, $l);
							}
							
							for($ib=$ibyte_min, $ic=$ichar_min; $ic<=$ichar_max-$src_noacc_len; $ic++)
							{
							//	printf("ib=0x%s, ic=%d (d=%d) : ", dechex($ib), $ic, $ib-$ic);
							//	for($i=0; $i<count($xml_noacc_tchar); $i++)
							//		printf("'%s.%s.%d' ; ", $xml_noacc_tchar[$i][0], $xml_noacc_tchar[$i][1], $xml_noacc_tchar[$i][2]);
							//	print("<br/>\n");
							
								if(isdelim($xml_noacc_tchar[0][0]) && isdelim($xml_noacc_tchar[$src_noacc_len+1][0]))
								{
									for($i=0; $i<$src_noacc_len; $i++)
									{
										if($xml_noacc_tchar[$i+1][1] !== $src_noacc_tchar[$i])
											break;
									}
									
									if($i==$src_noacc_len)
									{
									//	printf("ib=0x%s, ic=0x%s (d=%d) : ", dechex($ib), dechex($ic), $ib-$ic);
									//	for($i=0; $i<count($xml_noacc_tchar); $i++)
									//		printf("'%s.%s.%d' ; ", $xml_noacc_tchar[$i][0], $xml_noacc_tchar[$i][1], $xml_noacc_tchar[$i][2]);
									//	print("<br/>\n");
										for($l=0,$i=1; $i<$src_noacc_len+1; $i++)
											$l += $xml_noacc_tchar[$i][2];
											
										if(count($spots)==0)
										{
											$nrecchanged++;
										}
										$nspot++;
											
										$spots[$ib] = array("p"=>$ib, "l"=>$l);
										
									//	printf("found in field $field @0x%s lbin=%d !<br/>\n", dechex($ib), $l);
									//	$xib = $ib-$ibyte_min;
									//	$x = substr($txml_org, 0, $xib) . "<b>" . substr($txml_org, $xib, $l) . "</b>" . substr($txml_org, $xib+$l);
									//	$x = substr($xml, 0, $ib) . "<b>" . substr($xml, $ib, $l) . "</b>" . substr($xml, $ib+$l);
									//	print($x);
									//	print("<br/>\n");
									}
								}
								$lost = array_shift($xml_noacc_tchar);
								$c = mb_substr($txml, 0, 1, "UTF-8");
								$xml_noacc_tchar[] = array($c, noaccent_utf8($c), $l=strlen($c));
								// $txml = mb_substr($txml, 1, 9999, "UTF-8");
								$txml = substr($txml, $l);
								$ib += $lost[2];
								
								$ibyte_min = $ibyte_max +    strlen("</$field>");
								$ichar_min = $ichar_max + mb_strlen("</$field>");
							}
						}
					}
					if(count($spots) > 0)
					{
						ksort($spots);
						$dp = 0;
						// $ddp = strlen("<em>") + (strlen($parm["rpl"]) - strlen($parm["src"])) + strlen("</em>");
						$ddp = (strlen($parm["rpl"]) - strlen($parm["src"])) ;

						foreach($spots as $spot)
						{
						//	$xml = substr($xml, 0, $dp+$spot["p"]) . "<em>" . $parm["rpl"] . "</em>" . substr($xml, $dp+$spot["p"]+$spot["l"]);
							$xml = substr($xml, 0, $dp+$spot["p"]) . $parm["rpl"] .  substr($xml, $dp+$spot["p"]+$spot["l"]);
							$dp += $ddp; // strlen("<em></em>");
						}
						print($xml);
						print("<br/>\n");

						$sql = "UPDATE record SET status=status & ~3, xml='" . $connbas->escape_string($xml) . "'";
						$sql = "UPDATE tmprecord SET xml='" . $connbas->escape_string($xml) . "'";
						$connbas->query($sql);
					/*
					*/
					/*
						ksort($spots);
						$dp = 0;
						foreach($spots as $spot)
						{
							$xml = substr($xml, 0, $dp+$spot["p"]) . "<em>" . substr($xml, $dp+$spot["p"], $spot["l"]) . "</em>" . substr($xml, $dp+$spot["p"]+$spot["l"]);
							$dp += 9; // strlen("<em></em>");
						}
						print($xml);
						print("<br/>\n");
					*/
					}
				}
				printf("found %d times in %d records<br/>\n", $nspot, $nrecdone);
				$connbas->free_result($rsbas2);
				printf("<script type=\"text/javascript\">parent.pdone($nrecdone, $nrectot, $nrecchanged, $nspot);</script>\n");
			}
		}
	}
}


function isdelim($utf8char)
{
	global $endCharacters_iso;
	static $tdelim=null;
	if($tdelim===null)
	{
		for($i=0; $i<strlen($endCharacters_iso); $i++)
			$tdelim[] = substr($endCharacters_iso, $i, 1);
	}
	return(in_array($utf8char, $tdelim));
}
?>
</body>
</html>
