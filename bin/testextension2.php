<?php
define('USR_ID', 4);

set_time_limit(300);

require(dirname(__FILE__)."/../lib/bootstrap.php");

phrasea::headers();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
require(dirname(__FILE__)."/../lib/classes/p4.class.php");
 

$request = httpRequest::getInstance();
$parm = $request->get_parms("qry");
if(!$parm['qry'])
	$parm['qry'] = 'last';
	
?>
<html>
<head>
<title>Test extension</title>
<style type="text/css">
DIV.code
{
	position:relative;
	background-color:#eeeeee;
	margin:6px;
	margin-top:60px;
	border:3px black dotted;
	font-size:16px;
	padding:5px;
}
DIV.var
{
	position:relative;
	overflow:auto;
	background-color:#eeeeee;
	margin:3px;
	max-height:250px;
}
</style>
</head>
<body style="margin:20px;">
<a href="#SEARCHFORM">...recherche...</a>

<?php

print("<br><b>Fonction de la DLL : </b>");	
$result = "";
$allfunc = get_extension_funcs("phrasea2") ;
foreach($allfunc as $oneFunc)
	$result.= $oneFunc."\n";
print("<br><textarea style=\"width:400px;height:150px;\">$result</textarea> ");	
			

$sessid = null;

function showtime()
{
	static $last_t = false;
	$t = microtime(true);
	if($last_t !== false)
		printf("Dur&eacute;e : %0.5f", $t-$last_t);
	$last_t = $t;
}

/* 
// ------------------ phrasea_testutf8 --------------------

$code = '$ret = phrasea_testutf8();' ;

dumpcode($code);
eval($code);
dumpvar($ret, '$ret');
	
die();
*/

showtime();


// ------------------ phrasea_usebase --------------------
/*
$code = '$ret = phrasea_usebase("'.GV_db.'");' ;

dumpcode($code);
eval($code);
dumpvar($ret, '$ret');
	
showtime();
*/
// ------------------ phrasea_list_bases --------------------

/*
// ------------------ phrasea_conn --------------------
$code = '$ret = phrasea_conn("127.0.0.1", "3306", "root", "",  "'.GV_db.'");' ;

dumpcode($code);
eval($code);
dumpvar($ret, '$ret');
	
showtime();
*/



// ------------------ phrasea_list_bases --------------------

$code = '$lb = phrasea_list_bases();' ;

dumpcode($code);
eval($code);
dumpvar($lb, '$lb');
	
showtime();
 


// ------------------ phrasea_open_session --------------------

$code = '$sessid = phrasea_create_session('.USR_ID.');' ;

dumpcode($code);
eval($code);
print("<i> il faut que ca renvoie une valeur de session </i>");	
dumpvar($sessid, '$sessid');
	
showtime();

// ------------------ phrasea_open_session --------------------


	 
$code = '$ph_session = phrasea_open_session($sessid, '.USR_ID.');' ;

dumpcode($code);
eval($code);
print("<i> il faut que ca renvoie un tableau session </i>");	
dumpvar($ph_session, '$ph_session');
	
showtime();
 
//die();


if($ph_session)
{
	$sessid = $ph_session["session_id"];
	
	
	// ------------------ phrasea_open_session --------------------
	
	$code = '$ph_session = phrasea_open_session('.$sessid.', '.USR_ID.');' ;
	
	dumpcode($code);
	eval($code);
	print("<i> il faut que ca renvoie la meme valeur de session </i>");	
	dumpvar($ph_session, '$ph_session');
	
 	showtime();
	// pour interroger plus bas, on doit avoir un usr_id et avoir injecte ses 'mask' (droits dans appbox/xbascollusr) dans les dbox/collusr
	// !!!!! pour simplifier, on injecte un usr bidon (id=0) avec des mask '0' (tout voir)  !!!!!
	
	// on se register sur 4 collections
	$rmax = 99999;
	$basok = 0;
	foreach($lb["bases"] as $base)
	{
		if($base["online"] == true)
		{
//			$connbas = new sqlconnectObject($base["host"], $base["port"], $base["user"], $base["passwd"], $base["dbname"], $base["engine"]);
			$connbas = connection::getInstance($base['sbas_id']);

			if($connbas && $connbas->isok())
			{				
				foreach($base["collections"] as $coll_id=>$coll)
				{
					if($rmax-- > 0)
					{
 
						// ------------------ phrasea_register_base --------------------
						
						
						$code = '$rb = phrasea_register_base('.$sessid.', '.$coll['base_id'].', "", "");' ;
						
						dumpcode($code);
						eval($code);
						print("<i> register sur base connue doit retourner 'true' </i>");	
						dumpvar($rb, '$rb');

						if($rb)
						{
							echo "<font color=#00BB00>TRUE (comportement normal)</font><br><br>";
						
							showtime();
	
							// on injecte les droits bidons '0' pour un user bidon '0'
							$sql = sprintf("REPLACE INTO collusr (site, usr_id, coll_id, mask_and, mask_xor) VALUES ('%s', %s, %s, 0, 0)",
													mysql_escape_string(GV_sit),
													USR_ID,
													$coll['coll_id']
											);
							$connbas->query($sql);

							$basok++;
						}
						else
						{ 	
							echo "<font color=#FF0000>FALSE (comportement anormal)</font><br><br>";
							
							showtime();
						}
					}
				}
			}
		}
	}
	
	
	if($basok == 0)
	{
		printf("pas de base/coll ok, fin");
		phrasea_close_session($sessid);
		die();
	}
	
	
	// ------------------ phrasea_register_base (fake) --------------------
						
	$code = '$rb = phrasea_register_base('.$sessid.', 123456, "", "");' ;
	
	dumpcode($code);
	eval($code);
	print("<i> register sur xbas bidon connue doit retourner 'false' </i>");	
	dumpvar($rb, '$rb');
	
	if(!$rb)
		echo "<font color=#00BB00>FALSE (comportement normal)</font><br><br>";
	else 	
		echo "<font color=#FF0000>TRUE (comportement anormal)</font><br><br>";
	
	showtime();

	$basok += $rb ? 1 : 0;


	
	
	
	// ------------------ phrasea_open_session --------------------
	
	$code = '$ph_session = phrasea_open_session('.$sessid.', '.USR_ID.');' ;
	
	dumpcode($code);
	eval($code);
	print("<i> phrasea_open_session(...) apres $basok phrasea_register_base(...) doit retourner les bases/collections registered </i>");	
	dumpvar($ph_session, '$ph_session');
	
 	showtime();


 	// ------------------ phrasea_subdefs --------------------
	
	$code = '$subdef = phrasea_subdefs('.$sessid.', 58, 18863);' ;

	dumpcode($code);
	eval($code);
	dumpvar($subdef, '$subdef');
	
	showtime();

	
	
	// ------------------ phrasea_clear_cache --------------------
	
	$code = '$ret = phrasea_clear_cache('.$sessid.');' ;
	
	dumpcode($code);
	eval($code);
	dumpvar($ret, '$ret');
	
	showtime();

	?>
	<a name="SEARCHFORM"></a>
	<hr>
	<form method="POST">
		recherche : <input type="text" name="qry" value="<?php echo $parm['qry']?>">
		<input type="submit" value="ok">
	</form>
	<?php
	
	
	$result = ""; 
	
/*	
	$tbases = array();
	foreach($ph_session["bases"] as $base)
	{
		$tcoll = array();
		foreach($phbase["collections"] as $coll)
			$tcoll[] = $coll["coll_id"];
		if(sizeof($tcoll) > 0)	// au - une coll de la base etait dispo
		{
			$kbase = "S" . $phbase["xbas_id"];
			$tbases[$kbase] = array();
			$tbases[$kbase]["xbas_id"] = $phbase["xbas_id"];
			$tbases[$kbase]["searchcoll"] = $tcoll;

			$qp = new qparser();
			$treeq = $qp->parsequery($parm['qry']);
			$arrayq = $qp->makequery($treeq);

			$tbases[$kbase]["arrayq"] = $arrayq;
		}
	}
*/
	
	$tbases = array();
	foreach($ph_session["bases"] as $kphbase=>$phbase)
	{
		$tcoll = array();
		foreach($phbase["collections"] as $coll)
		{
			$tcoll[] = 0+$coll["base_id"];	// le tableau de colls doit contenir des int
		}
		if(sizeof($tcoll) > 0)	// au - une coll de la base etait cochee
		{
			$kbase = "S" . $phbase["sbas_id"];
			$tbases[$kbase] = array();
			$tbases[$kbase]["sbas_id"] = $phbase["sbas_id"];
			$tbases[$kbase]["searchcoll"] = $tcoll;
			$tbases[$kbase]["mask_xor"] = $tbases[$kbase]["mask_and"] = 0;

			$qp = new qparser();
			$treeq = $qp->parsequery($parm['qry']);
			$arrayq = $qp->makequery($treeq);

			$tbases[$kbase]["arrayq"] = $arrayq;
		}
	}

	
	
	// ------------------ phrasea_query2 --------------------
/*
	$nbanswers = 0;
	foreach($tbases as $kb=>$base)
	{
		$tbases[$kb]["results"] = NULL;

		set_time_limit(120);
		//$tbases[$kb]["results"] = phrasea_query2($ph_session["session_id"], $base["base_id"], $base["searchcoll"], $base["arrayq"], GV_sit, USR_ID, TRUE);
	 	$tbases[$kb]["results"] =  phrasea_query2($ph_session["session_id"], $base["base_id"], $base["searchcoll"], $base["arrayq"], GV_sit, (string)(USR_ID) , TRUE , (0) );

		if($tbases[$kb]["results"])
		{
			$nbanswers += $tbases[$kb]["results"]["nbanswers"];
			 
			$result .= var_export($tbases[$kb]["results"],true);	
		}
	}
	
	var_dump($result);
*/
	$nbanswers = 0;
	foreach($tbases as $kb=>$base)
	{
		$ret = null;
		$tbases[$kb]["results"] = NULL;

		set_time_limit(120);
		
		$code = "\$ret = phrasea_query2(\n" ;
		$code .= "\t\t\t" . $ph_session["session_id"] . "\t\t// ses_id \n" ;
		$code .= "\t\t\t, " . $base["sbas_id"] . "\t\t// bsas_id \n" ;
		$code .= "\t\t\t, " . my_var_export($base["searchcoll"]) . "\t\t// coll_id's \n" ;
		$code .= "\t\t\t, " . my_var_export($base["arrayq"]) . "\t\t// arrayq \n" ;
		$code .= "\t\t\t, '" . GV_sit . "'\t\t// site \n" ;
		$code .= "\t\t\t, ".USR_ID." \t\t// usr_id ! \n" ;
		$code .= "\t\t\t, FALSE \t\t// nocache \n" ;
		$code .= "\t\t\t, PHRASEA_MULTIDOC_DOCONLY\n" ;
//		$code .= "\t\t\t, PHRASEA_MULTIDOC_REGONLY\n" ;
//		$code .= "\t\t\t, array('DATE') \t\t// sort fields \n" ;
		$code .= "\t\t);" ;
//		$code .= '$base["arrayq"], "'.GV_sit.'", '.USR_ID.', FALSE, PHRASEA_MULTIDOC_DOCONLY, array("DATE"));	// USR_ID=0...' ;
		
		dumpcode($code);
		eval($code);
		print("<br><i>si les bases ne sont pas vides on devrait obtenir le nb de resultats en face de \"nbanswers\"</i>");	
		dumpvar($ret, '$ret');
		
		showtime();
	
		
		//	$tbases[$kb]["results"] = phrasea_query2($ph_session["session_id"], $base["xbas_id"], $base["searchcoll"], $base["arrayq"], GV_sit, USR_ID, FALSE, PHRASEA_MULTIDOC_DOCONLY, array('DATE'));	// USR_ID=0...

		if($ret)
		{
			$tbases[$kb]["results"] = $ret;
			
			$nbanswers += $tbases[$kb]["results"]["nbanswers"];
		}
	}
/*	
*/
	if(function_exists('phrasea_save_cache'))
	{
		// ------------------ phrasea_save_cache --------------------
		
		$code = '$ret = phrasea_save_cache('.$ph_session["session_id"].');' ;
		dumpcode($code);
		eval($code);
		dumpvar($ret, '$ret');
		
		showtime();
	}
	
	
// die();
	
	// ------------------ phrasea_fetch_results --------------------
	
	$code = '$rs = phrasea_fetch_results('.$ph_session["session_id"].', 1, 20, true, \'[[em]]\', \'[[/em]]\');' ;
	
	dumpcode($code);
	eval($code);
	dumpvar($rs, '$rs');

	showtime();
	
	

	// ------------------ phrasea_grpchild --------------------
	
	foreach($rs as $rec)
	{
		$code = '$grpchild = phrasea_grpchild('.$ph_session["session_id"].', '.$rec['base_id'].', '.$rec['record_id'].', "'.GV_sit.'", "'.USR_ID.'" );' ;
		
		dumpcode($code);
		eval($code);
		dumpvar($grpchild, '$grpchild');

		showtime();
	}
	
	
	// ------------------ phrasea_grpparent --------------------
	
	foreach($rs as $rec)
	{
		$code = '$grpparent = phrasea_grpparent('.$ph_session["session_id"].', '.$rec['base_id'].', '.$rec['record_id'].', "'.GV_sit.'", '.USR_ID.' );' ;
		
		dumpcode($code);
		eval($code);
		dumpvar($grpparent, '$grpparent');

		showtime();
	}

	
	
	// ------------------ phrasea_subdefs --------------------
	
	foreach($rs as $rec)
	{
//		$code = '$subdef = phrasea_subdefs('.$ph_session["session_id"].', '.$rec['base_id'].', '.$rec['record_id'].', "thumbnail");' ;
		$code = '$subdef = phrasea_subdefs('.$ph_session["session_id"].', '.$rec['base_id'].', '.$rec['record_id'].');' ;
		
		dumpcode($code);
		eval($code);
		dumpvar($subdef, '$subdef');

		if(isset($subdef) && isset($subdef['document']) && isset($subdef['document']['type']))
			echo "<div style='width:100%;height:25px;margin:20px 0;'>Bonne version d'extension ! Elle retourne le type de document !</div>";
		elseif(!isset($subdef) || !isset($subdef['document']))
			echo "rien";
		else
			echo "<div style='background:red;border:2px solid black;width:100%;height:25px;margin:20px 0;'>Mauvaise version d'extension ! Elle ne retourne pas le type de document !</div>";

		showtime();
	}
	
	// ------------------ phrasea_close_session --------------------
/*	
	$code = '$ret = phrasea_close_session('.$ph_session["session_id"].');' ;

	dumpcode($code);
	eval($code);
	dumpvar($ret, '$ret');
	
	showtime();
*/
}



function dumpcode($code)
{
	print("\n".'<div class="code">');
	$h = highlight_string('<?'.'php ' . $code . '?'.'>', true);
	$h=str_replace('&lt;?php', '', $h);
	$h=str_replace('?&gt;', '', $h);
	print($h);
	print('</div>'."\n");
}
function dumpvar($var, $varname)
{
	print("\n".'<div class="var">');
	$h = highlight_string('<?'.'php ' . var_export($var, true) . '?'.'>', true);
	$h=str_replace('&lt;?php', '', $h);
	$h=str_replace('?&gt;', '', $h);
	print('<b>' . $varname . ' is : </b>' . $h);
	print('</div>'."\n");
}

function my_var_export($var)
{
	$var = str_replace("\n", "", var_export($var, true));
	$var = str_replace("  ", " ", $var);
	$var = str_replace("  ", " ", $var);
	$var = str_replace("  ", " ", $var);
	$var = str_replace("  ", " ", $var);
	$var = str_replace(" => ", "=>", $var);
	$var = str_replace("array ( ", "array(", $var);
	$var = str_replace(", )", ",)", $var);
	$var = str_replace(",)", ")", $var);
	return($var);
}
?>

</body>
</html>

