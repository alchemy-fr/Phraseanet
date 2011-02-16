<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap.php";

require(GV_RootPath."lib/getargs.php");		// le parser d'arguments de la ligne de commande
		
function printHelp(&$argt, &$conn)
{
	print_usage($argt);
}


$argt = array(
				  "--help" =>				array("set"=>false, "values"=>array(), "usage"=>" : this help")
				, "--sbas-id" =>    		array("set"=>false, "values"=>array(), "usage"=>"=sbas_id : sbas_id to check")
				, "--field" =>    			array("set"=>false, "values"=>array(), "usage"=>"(=field |  : delete this field from records")
				, "--showstruct" => 		array("set"=>false, "values"=>array(), "usage"=>"")
				);

function help()
{
	global $argv;
	printf("usage: %s [options]\n", $argv[0]);
	print("options:\n");
	print("\t--help                     : this help\n");
	print("\t--sbas=sbas_id             : sbas to change (if --help, list fields)\n");
	print("\t--showstruct               : show structure changes and quit\n");
	print("\t--field=fieldname          : delete fieldname from records\n");
	print("\t--field=\"oldname:newname\"  : rename field oldname to newname into records\n");
	print("\t[--field=...]              : --field=... can be repeated\n");
}


// on commence par se conncter e application box
$allbas = array();
$conn = connection::getInstance();
$sql = "SELECT * FROM sbas";
if($rs = $conn->query($sql))
{
	while($tmprow = $conn->fetch_assoc($rs))
		$allbas["B".$tmprow["sbas_id"]] = $tmprow;
	$conn->free_result($rs);
}

$error = false;

if( !parse_cmdargs($argt, $err))
{
	help();
	print($err);
	die();
}

if($argt["--help"]["set"])
{
	help();
	$error = true;
}

if(!$argt['--sbas-id']['set'])
{
	print("missing option 'sbas-id'.\n");
	$error = true;
}

$fields = null;
if($argt['--field']['set'])
{
	foreach($argt["--field"]["values"] as $f)
	{
		$f = explode(':', $f);
		$f[] = null;
		$fields[] = array('from'=>$f[0], 'to'=>$f[1]);
	}
}

$domstruct = null;

$connbas = null;
if($argt["--sbas-id"]["set"])
{
	$sbas_id = $argt["--sbas-id"]["values"][0];
	// sauf erreur, on a l'adresse du serveur distant
	$row = null;
	if(array_key_exists("B".$sbas_id, $allbas))
		$row = $allbas["B".$sbas_id];
	if($row)
	{
		$connbas = connection::getInstance($sbas_id);
		if($connbas && $connbas->isok())
		{
			$tfields = array();
			
			if($argt["--help"]["set"])
				echo("fields of sbas=".$sbas_id." :\n");
			
			$sql = 'SELECT value FROM pref WHERE prop=\'structure\'';
			if($rs = $connbas->query($sql))
			{
				if($row = $connbas->fetch_assoc($rs))
				{
					$domstruct = new DOMDocument();
					$domstruct->formatOutput = true;
					$domstruct->preserveWhiteSpace = false;

					if( ($domstruct->loadXML($row['value'])) )
					{
						$xp = new DOMXPath($domstruct);
						
						$xf = @$xp->query('/record/description/*');
						foreach($xf as $f)
						{
							$tfields[] = $f->nodeName;
							if($argt["--help"]["set"])
								printf("\t%s \n", $f->nodeName);
						}
						
						if($argt["--showstruct"]["set"])
							printf("structure, before:\n...\n%s\n...\n", $domstruct->saveXML($xp->query('/record/description')->item(0)));
						
						if(is_array($fields))
						{
							foreach($fields as $f)
							{
								$fok = true;
								$ff = $tf = null;
								if( !($ff = @$xp->query('/record/description/'.$f['from'])) )
								{
									echo("ERROR : bad xml fieldname '".$f['from']."'\n");
									$error = true;
									$fok = false;
								}
								if($f['to'] && !($tf = @$xp->query('/record/description/'.$f['to'])))
								{
									echo("ERROR : bad xml fieldname '".$f['to']."'\n");
									$error = true;
									$fok = false;
								}
								if($fok)
								{
									if(in_array($f['from'], $tfields))
									{
										if($f['to'])
										{
											if($tf->length == 0)
											{
												$oldf = $ff->item(0);
												$newf = $domstruct->createElement($f['to']);
												foreach($oldf->attributes as $atn=>$atv)
												{
								// var_dump($atn, $atv->value);
													$newf->setAttribute($atn, $atv->value);
												}
												$oldf->parentNode->replaceChild($newf, $oldf);
											}
											else
											{
												echo("WARNING : field '".$f['to']."' exists into structure, will be replace by '".$f['from']."\n");
												foreach($tf as $n)
													$n->parentNode->removeChild($n);
											}
										}
										else
										{
											foreach($ff as $n)
												$n->parentNode->removeChild($n);
										}
									}
									else
									{
										echo("WARNING : unknown field '".$f['from']."' in structure\n");
									}
								}
							}
						}
						if($argt["--showstruct"]["set"])
							printf("structure, after:\n...\n%s\n...\n", $domstruct->saveXML($xp->query('/record/description')->item(0)));
					}
					else
					{
						echo("ERROR : structure xml error\n");
						$error = true;
					}
				}
				else
				{
					echo("ERROR : sql reading structure\n");
					$error = true;
				}
				$connbas->free_result($rs);
			}
			else
			{
				echo("ERROR : sql reading structure\n");
				$error = true;
			}
		}
		else
		{
			echo("ERROR accessing database\n");
			$error = true;
		}
	}
	else
	{
		echo("ERROR : unknown sbas_id ".$sbas_id."\n");
		$error = true;
	}
}
else
{
	if($argt["--help"]["set"])
	{
		print("BASES :\n");
		foreach($allbas as $bas)
			printf("%5d : %s @ %s:%s\n", $bas["sbas_id"], $bas["dbname"], $bas["host"], $bas["port"]);
	}
}


if($error || $argt["--showstruct"]["set"])
{
	if($connbas && $connbas->isok())
		$connbas->close();
	flush();
	die();
}


if(!$argt['--field']['set'])
{
	print("ERROR : missing option 'field'\n");
	$error = true;
}




if($domstruct)
{
	$domstruct->documentElement->setAttribute('modification_date', date('YmdHis'));
	$sql = 'UPDATE pref SET value=\''.$connbas->escape_string($domstruct->saveXML()).'\', updated_on=NOW() WHERE prop=\'structure\'';
	$connbas->query($sql);
}

$dom = new DOMDocument();
$dom->formatOutput = true;
$dom->preserveWhiteSpace = false;

$recChanged = 0;

$sql = 'SELECT record_id, xml FROM record ORDER BY record_id DESC';
if($rs = $connbas->query($sql))
{
	while($row = $connbas->fetch_assoc($rs))
	{
		printf("%d \r", $row['record_id']);
		
		if( $dom->loadXML($row['xml']) )
		{
			$oldxml = $dom->saveXML();
//			printf("avant :\n%s\n", $dom->saveXML());
			
			$xp = new DOMXPath($dom);
			foreach($fields as $f)
			{
				if( ($tn = @$xp->query('/record/description/'.$f['from'])) )
				{
					foreach($tn as $oldn)
					{
						if($f['to'])
						{
							$newn = $dom->createElement($f['to']);
							foreach($oldn->childNodes as $n)
								$newn->appendChild($n);
							$oldn->parentNode->replaceChild($newn, $oldn);
						}
						else
						{
							$oldn->parentNode->removeChild($oldn);
						}
					}
				}
			}

			$newxml = $dom->saveXML();
			
			if($newxml != $oldxml)
			{
				// printf("apres :\n%s\n", $dom->saveXML());
				
				$sql = 'UPDATE record SET xml=\''.$connbas->escape_string($newxml).'\', moddate=NOW() WHERE record_id='.$row['record_id'];
				$connbas->query($sql);
				$recChanged++;
			}
		}
		else
		{
			printf("ERR (rid=%s) : bad xml \n", $row['record_id']);
		}
	}
	$connbas->free_result($rs);
}

$connbas->close();

if($recChanged > 0)
	printf("%s record(s) changed, please reindex database\n", $recChanged);
else
	printf("no record(s) changed\n");



?>
