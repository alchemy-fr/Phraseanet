<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap.php";
define('DOCPERDIR', 100);


$tfields = array(
					 'Titre'			=>array('field_out'=>'Titre')
					, 'Categories'			=>array('field_out'=>'Categories')
					, 'MotsCles'		=>array('field_out'=>'MotsCles')
					, 'Date'		=>array('field_out'=>'Date')
					, 'Photographe'			=>array('field_out'=>'Photographe')
					, 'Ville'		=>array('field_out'=>'Ville')
					, 'Pays'		=>array('field_out'=>'Pays')
					, 'Reference'	=>array('field_out'=>'Reference')
					, 'Credit'			=>array('field_out'=>'Credit')
					, 'Legende'	=>array('field_out'=>'Legende')
);

$status = array(
					 '4' => '4'
					 ,'5' => '5'
);


require(GV_RootPath."lib/index_utils2.php");		
require(GV_RootPath."lib/getargs.php");		// le parser d'arguments de la ligne de commande
		
function printHelp(&$argt, &$conn)
{
	print_usage($argt);
}


$argt = array(
				  "--help" =>				array("set"=>false, "values"=>array(), "usage"=>" : cette aide")
				, "--sbas-id" =>    		array("set"=>false, "values"=>array(), "usage"=>"=sbas_id : sbas_id de la base a ventiler")
				, "--coll-id" =>    		array("set"=>false, "values"=>array(), "usage"=>"=coll_id : coll_id a ventiler")
				, "--out" =>    			array("set"=>false, "values"=>array(), "usage"=>"=path : repertoire d'export")
				, "--limit" =>    			array("set"=>false, "values"=>array(), "usage"=>"=n : nombre max de records a exporter (pour test)")
	 		);



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

if( !parse_cmdargs($argt, $err) || $argt["--help"]["set"])
{
	print($err);
	$error = true;
}

if(!$argt["--sbas-id"]["set"])
{
	print("parametre 'sbas-id' obligatoire.\n");
	$error = true;
}

if(!$argt["--out"]["set"])
{
	print("parametre 'out' obligatoire.\n");
	$error = true;
}

if($error)
{
	print_usage($argt);
	print("BASES :\n");
	foreach($allbas as $bas)
		printf("%5d : %s @ %s:%s\n", $bas["sbas_id"], $bas["dbname"], $bas["host"], $bas["port"]);
	flush();
	die();
}




$root = p4string::addEndSlash($argt["--out"]["values"][0]);
if(!is_dir($root))
{
	print("repertoire out '".$root."' absent.\nABANDON\n");
	die();
}


if($argt["--limit"]["set"])
{
	$limit = (int)($argt["--limit"]["values"][0]);
}
else
{
	$limit = NULL;
}



$sbas_id = $argt["--sbas-id"]["values"][0];
// sauf erreur, on a l'adresse du serveur distant

$connbas = null;
$row = null;
if(array_key_exists("B".$sbas_id, $allbas))
	$row = $allbas["B".$sbas_id];
if($row)
{
	$connbas = connection::getInstance($sbas_id);
	if(!$connbas)
	{
		echo("\n\nerreur d'acces a la base\n\nABANDON ! :(\n\n");
		flush();
		die();
	}
}

echo("Connexion a la base ok\n\n");


$root .= $row["dbname"];
@mkdir($root);



$ndig = ceil(log10(DOCPERDIR-1));
define('DIRFMT1', '%0'.(8-$ndig).'d');
define('DIRFMT2', '%0'.$ndig.'d');



$sql = 'SELECT xml, path, file, record.record_id FROM record INNER JOIN subdef ON subdef.record_id=record.record_id AND subdef.name=\'document\'';


if($argt["--coll-id"]["set"])
{
	$sql .= ' WHERE coll_id="'.(int)($argt["--coll-id"]["values"][0]).'" ';
}

$sql .=	' ORDER BY record.record_id ASC';

if($limit !== NULL)
	$sql .= ' LIMIT ' . $limit;
	
if($rs = $connbas->query($sql))
{
	$idir = -1;
	$idoc = DOCPERDIR-1;
	
	$nrec = $connbas->num_rows($rs);
	
	while($row = $connbas->fetch_assoc($rs))
	{
		printf("%d \r", --$nrec);
		
		if( ($sxml = simplexml_load_string($row['xml'])) )
		{
			if( ($orgdocname = (string)($sxml->doc['originalname'])) != '' )
			{
				if(file_exists($phfile = p4string::addEndSlash($row['path']) . $row['file']))
				{
					if(++$idoc == DOCPERDIR)
					{
						$idir++;
						$dir1name = sprintf(DIRFMT1, $idir);
						@mkdir($root . '/' . $dir1name);
						$idoc = 0;
					}
				
					// $dir2name = sprintf(DIRFMT2, $idoc);
					$dir2name = sprintf('rid_%08d', $row['record_id']);
					@mkdir( $outdir = ($root . '/' . $dir1name . '/' . $dir2name) );
					
					// print($phfile . "\n");
					if(copy($phfile, $outdir . '/' . $orgdocname ) )
					{
						
						// file_put_contents($outdir . '/' . $orgdocname . '-old.xml', $row['xml']);
						
						foreach($tfields as $fname=>$field)
							$tfields[$fname]['values'] = array();
						
						foreach($sxml->description->children() as $fname=>$fvalue)
						{
						//	printf("%s : %s\n", $fname, $fvalue);
							if(isset($tfields[$fname]))
								$tfields[$fname]['values'][] = $fvalue;
						}
						
						$domout = new DOMDocument('1.0', 'UTF-8');
						$domout->standalone = true;
						$domout->preserveWhiteSpace = false;
						
						$element = $domout->createElement('record');
						
						$domrec = $domout->appendChild($element);
						$domdesc = $domrec->appendChild($domout->createElement('description'));
						foreach($tfields as $kfield=>$field)
						{
							foreach($field['values'] as $value)
							{
								$domfield = $domdesc->appendChild($domout->createElement($field['field_out']));
								$domfield->appendChild($domout->createTextNode($value));
							}
						}
						
						$sqlS = 'SELECT bin(status) as statin FROM record WHERE record_id="'.$row['record_id'].'"';
						$statin = false;
						if($rsS = $connbas->query($sqlS))
						{
							if($statin = $connbas->fetch_assoc($rsS))
							{
								$statin = strrev($statin['statin']);
							}
						}
						
						$statout = '0000000000000000000000000000000000000000000000000000000000001111';
						if($statin)
							foreach($status as $sIn => $sOut)
							{
								if(substr($statin,$sIn,1)=='1')
								{
									$statout = substr_replace($statout,'1',(63-(int)$sOut),1);
								}
							}
						
						$domstatus = $domrec->appendChild($domout->createElement('status'));
						$domstatus->appendChild($domout->createTextNode($statout));
						
						
						$domout->save($outdir . '/' . $orgdocname . '.xml');
						unset($domout);
					}
					else
					{
						printf("\nERR (rid=%s) : erreur de copie du document '%s'\n", $row['record_id'], $phfile);
					}
				}
				else
				{
					printf("\nERR (rid=%s) : document '%s' manquant\n", $row['record_id'], $phfile);
				}
			}
			else
			{
				printf("\nERR (rid=%s) : orgdocname manquant\n", $row['record_id']);
			}
		}
		else
		{
			printf("\nERR (rid=%s) : xml illisible manquant\n", $row['record_id']);
		}
	}
	$connbas->free_result($rs);
}
die();

?>
