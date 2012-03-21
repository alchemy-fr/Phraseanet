<?php
// phpinfo();
// die;

define('USR_ID', 3);
define('HOSTNAME', "localhost");
define('PORT', 3306);
define('USER', "dll_v35");
define('PASSWORD', "dll_v35");
define('DBNAME', "ab_v35");
define('LNG', 'fr_FR');
define('GV_min_letters_truncation', 2);
define('GV_sit', 'b88044c9bfea6e050ab0fb5176c9ddfb');

set_time_limit(300);

	// ------------------ phrasea_conn --------------------
	$code = '$sessid = phrasea_conn(\''.HOSTNAME.'\', '.PORT.', \''.USER.'\', \''.PASSWORD.'\', \''.DBNAME.'\');' ;
	dumpall($code, 'phrasea_conn(...)', 'sessid', 'must return a session id');
	

// require(dirname(__FILE__)."/../lib/bootstrap.php");

// $appbox = appbox::get_instance();
// $session = $appbox->get_session();
// $registry = $appbox->get_registry();


// $lng = Session_Handler::get_locale();

// phrasea::headers();
// require(dirname(__FILE__).'/../lib/unicode/lownodiacritics_utf8.php' );
// require(dirname(__FILE__)."/../lib/classes/p4.class.php");
 
ini_set('display_errors', true);

// $request = http_request::getInstance();
// $parm = $request->get_parms('act', 'qry', 'sortfield', 'searchsbas', 'firsta', 'nbra', 'ses', 'fast', 'sha');

foreach(array('act', 'qry', 'sortfield', 'searchsbas', 'firsta', 'nbra', 'ses', 'fast', 'sha', 'businessfields') as $p)
{
  $parm[$p] = NULL;
  if(isset($_GET[$p]))
    $parm[$p] = $_GET[$p];
  elseif(isset($_POST[$p]))
    $parm[$p] = $_POST[$p];
}
if(!$parm['qry'])
	$parm['qry'] = 'last';
if(!$parm['sortfield'])
	$parm['sortfield'] = '';
if($parm['firsta'] === NULL)
	$parm['firsta'] = '0';
if($parm['nbra'] === NULL)
	$parm['nbra'] = '20';
$parm['fast'] = $parm['fast'] !== NULL;
$parm['sha'] = $parm['sha'] !== NULL;
$parm['businessfields'] = $parm['businessfields'] !== NULL;

?>
<html>
<head>
<title>Test extension</title>
<style type="text/css">
*
{
	font-family:monospace;
	font-size:12px;
}
H1, H2
{
	margin:0px;
	padding:0px;
	margin-top:30px;
}
I
{
	font-size:12px;
}
DIV.block
{
	position:relative;
	background-color:#eeeeee;
	margin:6px;
	border:3px black dotted;
	padding:0px;
}
DIV.code
{
	position:relative;
	background-color:#dddddd;
	font-size:12px;
	padding:5px;
}
DIV.var
{
	position:relative;
	overflow:auto;
	padding:5px;
	max-height:250px;
}
DIV.time
{
	font-size:12px;
}
TABLE
{
	border-collapse:collapse
}
TD, TH
{
	border: 1px solid #808080;
	font-size:12px;
	text-align:left;
	padding-left:5px;
	padding-right:5px;
	vertical-align:top;
}

TABLE.sort TD, TABLE.sort TH
{
	text-align:right;
}

</style>
</head>
<body style="margin:20px;">
<a href="#SEARCHFORM">...search form...</a>

<?php


$sessid = null;

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

if($parm['act']=='CLOSE' && $parm['ses'])
{
	// ------------------ phrasea_close_session --------------------

	$code = '$ret = phrasea_close_session('.$parm['ses'].');' ;
	dumpall($code, 'phrasea_close_session(...)', 'ret', true, '');
	
}




if(!$parm['ses'])
{
	print("<br><b>Fonction de la DLL : </b>");	
	$result = "";
	$allfunc = get_extension_funcs("phrasea2") ;
	foreach($allfunc as $oneFunc)
		$result.= $oneFunc."\n";
	print("<br><textarea style=\"width:400px;height:150px;\">$result</textarea> ");	

	// ------------------ phrasea_info --------------------
	
	$code = '$lb = phrasea_info();' ;
	dumpall($code, 'phrasea_info(...)', 'lb', true, '');
		
	

	// ------------------ phrasea_create_session --------------------
	
	$code = '$sessid = phrasea_create_session('.USR_ID.');' ;
	dumpall($code, 'phrasea_create_session(...)', 'sessid', 'must return a session id');
/*
	$rmax = 99999;
	$basok = 0;
	foreach($lb["bases"] as $base)
	{
		if($base["online"] == true)
		{
			$connbas = connection::getInstance($base['sbas_id']);

			if($connbas && $connbas->isok())
			{				
				foreach($base["collections"] as $coll_id=>$coll)
				{
					if($rmax-- > 0)
					{
						// ------------------ phrasea_register_base --------------------
						
						$code = '$rb = phrasea_register_base('.$sessid.', '.$coll['base_id'].', "", "");' ;
						dumpall($code, 'phrasea_register_base(...)', 'rb', true, 'register on known db must return \'true\'');

						if($rb)
						{
							echo "<font color=#00BB00>TRUE (ok)</font><br><br>";
						
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
							echo "<font color=#FF0000>FALSE (error)</font><br><br>";
							
							showtime();
						}
					}
				}
			}
		}
	}

	// ------------------ phrasea_register_base (fake) --------------------
						
	$code = '$rb = phrasea_register_base('.$sessid.', 123456, "", "");' ;
	dumpall($code, 'phrasea_register_base(...)', 'rb', true, 'register on unknown db must return \'false\'');
	
	if(!$rb)
		echo "<font color=#00BB00>FALSE (ok)</font><br><br>";
	else 	
		echo "<font color=#FF0000>TRUE (error)</font><br><br>";
	$basok += $rb ? 1 : 0;
*/
/*	
	if($basok == 0)
	{
		$code = '$ret = phrasea_close_session('.$parm['ses'].');' ;
		dumpall($code, 'phrasea_close_session(...)', 'ret', true, '');

		die();
	}
*/
}
else
{
	$sessid = $parm['ses'];
}

?>
<form name="CLOSE" method="POST" onsubmit="return(false);">
	<input type="hidden" name="act" value="CLOSE">
	<input type="hidden" name="ses" value="<?php echo $sessid?>">
	<H2>session : <?php echo $sessid; ?>
		<button onclick="document.forms['CLOSE'].act.value='CLOSE';document.forms['CLOSE'].submit();">close session</button>
	</H2>
</form>
<?php 


// ------------------ phrasea_open_session --------------------

$code = '$ph_session = phrasea_open_session('.$sessid.', '.USR_ID.');' ;
dumpall($code, 'phrasea_open_session(...)', 'ph_session', !$parm['fast'], 'must return a session array');
	


if($ph_session)
{
	$sessid = $ph_session["session_id"];
	
	$bas2sbas = array();
	
	foreach($ph_session["bases"] as $base)
	{
		foreach($base["collections"] as $coll_id=>$coll)
			$bas2sbas[$coll['base_id']] = $base['sbas_id'] ;
	}
	
	?>
	<a name="SEARCHFORM"></a>
	<H1>search(es)</H1>
	<form name="SEARCHFORM" action="#SEARCHFORM" method="POST" onsubmit="return(false);">
		<input type="hidden" name="act" value="SEARCH">
		<input type="hidden" name="ses" value="<?php echo $sessid?>">
<?php
	$nck = 0;
	foreach($ph_session["bases"] as $kphbase=>$phbase)
	{
		$checked = 0;
		if($parm['searchsbas'] === NULL)
			$checked = 0;
		elseif(array_search((string)($phbase['sbas_id']), $parm['searchsbas']) !== FALSE)
			$checked = 1;
		printf("<input type='checkbox' name='searchsbas[]' value='%s' %s>%s</input><br/>\n", $phbase['sbas_id'], $checked?'checked':'', $phbase['dbname']);
		$nck += $checked;
	}
?>
		<table>
			<tr>
				<td>
					query : <input type="text" name="qry" value="<?php echo $parm['qry']?>" style="width:300px;">
          <br/>
          <input type="checkbox" name="businessfields" <?php echo $parm['businessfields']?'checked':'';?> >search in business fields</input>
				</td>
				<td>
					sort(field) : <input type="text" name="sortfield" value="<?php echo $parm['sortfield']?>"><br/>
					<span style="color:#CC0000; font-size:10px;">
						prepend with '+' to sort asc., and/or '0' to sort numerically<br/>
						ex: 'Author', '+Author', '0Date', '+0Date'...
					</span>
				</td>
				<td>
					<input type="checkbox" name="fast" <?php echo $parm['fast']?'checked':'';?> >less verbose</input>
					<input type="checkbox" name="sha" <?php echo $parm['sha']?'checked':'';?>> show sha256</input>
					<button onclick="document.forms['SEARCHFORM'].firsta='1';document.forms['SEARCHFORM'].act.value='SEARCH';document.forms['SEARCHFORM'].submit();">search</button>
				</td>
			</tr>
		</table>
<?php
	
	if($nck > 0 && $parm['act']=='SEARCH')
	{
		// ------------------ phrasea_clear_cache --------------------
		
		$code = '$ret = phrasea_clear_cache('.$sessid.');' ;
		dumpall($code, 'phrasea_clear_cache(...)', 'ret', !$parm['fast'], '');
	

		$result = ""; 
		$tbases = array();

		$qp = new searchEngine_adapter_phrasea_queryParser(LNG);
		$simple_treeq = $qp->parsequery($parm['qry']);
		$qp->priority_opk($simple_treeq);
		$qp->distrib_opk($simple_treeq);
		$indep_treeq =  $qp->extendThesaurusOnTerms($simple_treeq, true, true, false);
		$needthesaurus = $qp->containsColonOperator($indep_treeq);
		
  	foreach($ph_session["bases"] as $kphbase=>$phbase)
    {
			if(array_search($phbase['sbas_id'], $parm['searchsbas']) !== FALSE)
			{
				$tcoll = array();
				foreach($phbase['collections'] as $collection)
				{
					$tcoll[] = $collection['coll_id'];	// le tableau de colls doit contenir des int
				}
				if(sizeof($tcoll) > 0)	// au - une coll de la base etait cochee
				{
 // 				if(sizeof($tcoll) > 1)
 //           array_shift($tcoll);
					$kbase = "S" . $phbase['sbas_id'];
					$tbases[$kbase] = array();
					$tbases[$kbase]["sbas_id"] = $phbase['sbas_id'];
					$tbases[$kbase]["dbname"] = $phbase['dbname'];
					$tbases[$kbase]["searchcoll"] = $tcoll;
					$tbases[$kbase]["mask_xor"] = $tbases[$kbase]["mask_and"] = 0;
/*
					if($needthesaurus)
					{
						$domthesaurus = $databox->get_dom_thesaurus();
						if($domthesaurus)
							$qp->thesaurus2($indep_treeq, $phbase['sbas_id'], $phbase['dbname'], $domthesaurus, true);
					}
*/
					$emptyw = false;
					$qp->set_default($indep_treeq, $emptyw);
					$qp->distrib_in($indep_treeq);
					$qp->factor_or($indep_treeq);
			//		$qp->setNumValue($indep_treeq, $phbase["xmlstruct"]);
					$qp->thesaurus2_apply($indep_treeq, $phbase['sbas_id']);
					
					$arrayq = $qp->makequery($indep_treeq);
		
					$tbases[$kbase]["arrayq"] = $arrayq;
				}
			}
		}

	
		$nbanswers = 0;
		foreach($tbases as $kb=>$base)
		{
			printf("<H2>search on db [%s]</H2>\n", htmlentities($base['dbname']));
			
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
			if($parm['sortfield'] != '')
				$code .= "\t\t\t, '".$parm['sortfield']."' \t\t// sort field \n" ;
      else
				$code .= "\t\t\t, '' \t\t// no sort \n" ;
      
			if($parm['businessfields'])
				$code .= "\t\t\t, TRUE \t\t// search on business fields \n" ;
      else
				$code .= "\t\t\t, FALSE \t\t// do not search on business fields \n" ;
      
      
			$code .= "\t\t);" ;
	//		$code .= '$base["arrayq"], "'.GV_sit.'", '.USR_ID.', FALSE, PHRASEA_MULTIDOC_DOCONLY, array("DATE"));	// USR_ID=0...' ;
			
			dumpall($code, 'phrasea_query2(...)', 'ret', !$parm['fast'], '');
			
		
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
			dumpall($code, 'ret', !$parm['fast'], '');
		}
	}
	
// die();
?>	
	
		<H1>Results</H1>

		firsta <input type="text" name="firsta" value="<?php echo $parm['firsta']?>">
		nbra <input type="text" name="nbra" value="<?php echo $parm['nbra']?>">
		
		<button onclick="document.forms['SEARCHFORM'].act.value='CHGPAGE';document.forms['SEARCHFORM'].submit();return(false);">fetch</button>
	</form>
<?php 		
	// ------------------ phrasea_fetch_results --------------------
	$code  = "\$rs = phrasea_fetch_results(\n";
	$code .= "\t\t\t".$ph_session["session_id"] . "\t\t// ses_id \n" ;
	$code .= "\t\t\t, ".$parm['firsta'] . "\t\t// first answer \n" ;
	$code .= "\t\t\t, ".$parm['nbra'] . "\t\t// nbr answers \n" ;
//	if(!$parm['fast'])
//	{
		$code .= "\t\t\t, true" . "\t\t// fetch xml\n" ;
		$code .= "\t\t\t, '[[em]]', '[[/em]]'" . "\t\t// hightlight markers\n" ;
//	}
	$code .= "\t\t);" ;
	dumpall($code, 'phrasea_fetch_results(...)', 'rs', !$parm['fast'], '');

	if($rs !== NULL)
	{
		// display a table to check ordering of results
		$sortfield = $parm['sortfield'];
		if($sortfield && ($sortfield[0]=='-' || $sortfield[0]=='+'))
			$sortfield = substr($sortfield, 1);
		if($sortfield && $sortfield[0]=='0')
			$sortfield = substr($sortfield, 1);
		printf("<H3>ordering :</H3>\n");
		printf("<table border=\"1\" class=\"sort\"><thead><tr><th>&nbsp;</th><th>base_id</th><th>record_id</th>");
		if($sortfield != '')
			printf("<th>&lt;%s&gt;</th>", $sortfield);
		if($parm['sha'])
			printf("<th>sha256</th>", $sortfield);
		printf("</tr></thead><tbody>\n");
		foreach($rs['results'] as $irec=>$rec)
		{
			printf("<tr><td>%s</td><td>%s</td><td>%s</td>", $irec+(int)($parm['firsta']), $rec['base_id'], $rec['record_id']);
			if($parm['sortfield'] != '')
			{
				$f = '';
				if( ($sx = simplexml_load_string($rec['xml'])) )
					$f = (string)($sx->description->{$sortfield});
				printf("<td>%s</td>", $f);
			}
			if($parm['sha'])
			{
				$connbas = databox::get_instance($bas2sbas[$rec['base_id']]);
				// $sql = 'SELECT sha256 FROM record WHERE record_id=\''.$connbas->escape_string($rec['record_id']).'\'';
				$sql = 'SELECT sha256 FROM record WHERE record_id=\''.$rec['record_id'].'\'';
				if( ($_rs = $connbas->query($sql)) ) 
				{
					if( ($_row = $connbas->fetch_assoc($_rs)) )
					{
						if($_row['sha256'] === NULL)
							printf("<td><i>NULL</i></td>");
						else
							printf("<td>%s</td>", $_row['sha256']);
					}
					else
					{
						printf("<td>&nbsp;</td>");
					}
					$connbas->free_result($_rs);
				}
			}
			printf("</tr>\n");
		}
		printf("</tbody></table>\n");	
		
	}
}


// ======================================================================================================================================

function showtime()
{
	static $last_t = false;
	$t = microtime(true);
	if($last_t !== false)
		printf("Dur&eacute;e : %0.5f", $t-$last_t);
	$last_t = $t;
}

function dumpall($code, $shortcode, $varname, $dump, $msg='')
{
	global $$varname;

	print("<div class=\"block\">\n");
	print("<div class=\"code\">\n");
	$h = highlight_string('<?'.'php ' . ($dump ? $code : $shortcode) . '?'.'>', true);
	$h = str_replace(array('&lt;?php', '?&gt;', ',&nbsp;'), array('', '', ', '), $h);
	print($h);

	$t = microtime(true);
	eval($code);
	$t = microtime(true) - $t;
	printf("\t<div class='time'>Duration : %0.5f</div>\n", $t);
	if($msg)
		printf("<i>%s</i><br/>\n", $msg);
	print("</div>\n");

	$var = $$varname;
	if($dump)
	{
		print("<div class=\"var\">\n");
		$h = highlight_string('<?'.'php ' . var_export($var, true) . '?'.'>', true);
		$h=str_replace(array('&lt;?php', '?&gt;', ',&nbsp;', ')&nbsp;', '&nbsp;('), array('', '', ', ', ') ', ' ('), $h);
		print('<b>$' . $varname . ' is : </b>' . $h);
	}	
	print('</div>'."\n");
	print('</div>'."\n");
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
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class searchEngine_adapter_phrasea_queryParser
{

  var $ops = array(
      "et" => array("NODETYPE" => PHRASEA_OP_AND, "CANNUM" => false),
      "and" => array("NODETYPE" => PHRASEA_OP_AND, "CANNUM" => false),
      "ou" => array("NODETYPE" => PHRASEA_OP_OR, "CANNUM" => false),
      "or" => array("NODETYPE" => PHRASEA_OP_OR, "CANNUM" => false),
      "sauf" => array("NODETYPE" => PHRASEA_OP_EXCEPT, "CANNUM" => false),
      "except" => array("NODETYPE" => PHRASEA_OP_EXCEPT, "CANNUM" => false),
      "pres" => array("NODETYPE" => PHRASEA_OP_NEAR, "CANNUM" => true),
      "near" => array("NODETYPE" => PHRASEA_OP_NEAR, "CANNUM" => true),
      "avant" => array("NODETYPE" => PHRASEA_OP_BEFORE, "CANNUM" => true),
      "before" => array("NODETYPE" => PHRASEA_OP_BEFORE, "CANNUM" => true),
      "apres" => array("NODETYPE" => PHRASEA_OP_AFTER, "CANNUM" => true),
      "after" => array("NODETYPE" => PHRASEA_OP_AFTER, "CANNUM" => true),
      "dans" => array("NODETYPE" => PHRASEA_OP_IN, "CANNUM" => false),
      "in" => array("NODETYPE" => PHRASEA_OP_IN, "CANNUM" => false)
  );
  var $opk = array(
      "<" => array("NODETYPE" => PHRASEA_OP_LT, "CANNUM" => false),
      ">" => array("NODETYPE" => PHRASEA_OP_GT, "CANNUM" => false),
      "<=" => array("NODETYPE" => PHRASEA_OP_LEQT, "CANNUM" => false),
      ">=" => array("NODETYPE" => PHRASEA_OP_GEQT, "CANNUM" => false),
      "<>" => array("NODETYPE" => PHRASEA_OP_NOTEQU, "CANNUM" => false),
      "=" => array("NODETYPE" => PHRASEA_OP_EQUAL, "CANNUM" => false),
      ":" => array("NODETYPE" => PHRASEA_OP_COLON, "CANNUM" => false)
  );
  var $spw = array(
      "all" => array(
          "CLASS" => "PHRASEA_KW_ALL", "NODETYPE" => PHRASEA_KW_ALL, "CANNUM" => false
      ),
      "last" => array(
          "CLASS" => "PHRASEA_KW_LAST", "NODETYPE" => PHRASEA_KW_LAST, "CANNUM" => true
      ),
      //  "first"    => array("CLASS"=>PHRASEA_KW_FIRST, "CANNUM"=>true),
      //  "premiers" => array("CLASS"=>PHRASEA_KW_FIRST, "CANNUM"=>true),
      "tout" => array(
          "CLASS" => "PHRASEA_KW_ALL", "NODETYPE" => PHRASEA_KW_ALL, "CANNUM" => false
      ),
      "derniers" => array(
          "CLASS" => "PHRASEA_KW_LAST", "NODETYPE" => PHRASEA_KW_LAST, "CANNUM" => true
      )
  );
  var $quoted_defaultop = array(
      "VALUE" => "default_avant", "NODETYPE" => PHRASEA_OP_BEFORE, "PNUM" => 0
  );
  var $defaultop = array(
      "VALUE" => "and", "NODETYPE" => PHRASEA_OP_AND, "PNUM" => NULL
  );
  var $defaultlast = 12;
  var $phq;
  var $errmsg = "";
  /**
   *
   * @var boolean
   */
  var $debug = false;
  /**
   * un tableau qui contiendra des propositions de thesaurus
   * pour les termes de l'arbre simple
   *
   * @var array
   */
  var $proposals = Array("QRY" => "", "BASES" => array());
  /**
   * Current language for thesaurus
   * @var <type>
   */
  var $lng = null;

  protected $unicode;

  function __construct($lng = "???")
  {
    $this->lng = $lng;
    $this->unicode = new unicode();

    return $this;
  }

  function mb_trim($s, $encoding)
  {
    return(trim($s));
  }

  function mb_ltrim($s, $encoding)
  {
    return(ltrim($s));
  }

  function parsequery($phq)
  {
    if ($this->debug)
    {
      for ($i = 0; $i < mb_strlen($phq, 'UTF-8'); $i++)
      {
        $c = mb_substr($phq, $i, 1, 'UTF-8');
        printf("// %s : '%s' (%d octets)\n", $i, $c, strlen($c));
      }
    }

    $this->proposals = Array("QRY" => "", "BASES" => array());
    $this->phq = $this->mb_trim($phq, 'UTF-8');
    if ($this->phq != "")

      return($this->maketree(0));
    else
    {
      if ($this->errmsg != "")
        $this->errmsg .= sprintf("\\n");
      $this->errmsg .= _('qparser::la question est vide');

      return(null);
    }
  }

  function astext($tree)
  {
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
        if (is_array($tree["VALUE"]))

          return(implode(" ", $tree["VALUE"]));
        else

          return($tree["VALUE"]);
        break;
      case "QSIMPLE":
        if (is_array($tree["VALUE"]))

          return("\"" . implode(" ", $tree["VALUE"]) . "\"");
        else

          return("\"" . $tree["VALUE"] . "\"");
        break;
      case "PHRASEA_KW_ALL":
        return($tree["VALUE"][0]);
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)

          return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
        else

          return($tree["VALUE"][0]);
        break;
      case "OPS":
      case "OPK":
        if (isset($tree["PNUM"]))

          return("(" . $this->astext($tree["LB"]) . " " . $tree["VALUE"] . "[" . $tree["PNUM"] . "] " . $this->astext($tree["RB"]) . ")");
        else

          return("(" . $this->astext($tree["LB"]) . " " . $tree["VALUE"] . " " . $this->astext($tree["RB"]) . ")");
        break;
    }
  }

  function astable(&$tree)
  {
    $this->calc_complexity($tree);
    // var_dump($tree);
    $txt = "";
    $this->astable2($txt, $tree);
    $txt = "<table border=\"1\">\n<tr>\n" . $txt . "</tr>\n</table>\n";

    return($txt);
  }

  function calc_complexity(&$tree)
  {
    if ($tree)
    {
      if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")

        return($tree["COMPLEXITY"] = $this->calc_complexity($tree["LB"]) + $this->calc_complexity($tree["RB"]));
      else

        return($tree["COMPLEXITY"] = 1);
    }
  }

  function astable2(&$out, &$tree, $depth=0)
  {
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
        if (is_array($tree["VALUE"]))
          $txt = implode(" ", $tree["VALUE"]);
        else
          $txt = $tree["VALUE"];
        $out .= "\t<td>" . $txt . "</td>\n";
        break;
      case "QSIMPLE":
        if (is_array($tree["VALUE"]))
          $txt = implode(" ", $tree["VALUE"]);
        else
          $txt = $tree["VALUE"];
        $out .= "\t<td>&quot;" . $txt . "&quot;</td>\n";
        break;
      case "PHRASEA_KW_ALL":
        $out .= "\t<td>" . $tree["VALUE"][0] . "</td>\n";
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)
          $out .= "\t<td>" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]" . "</td>\n";
        else
          $out .= "\t<td>" . $tree["VALUE"][0] . "</td>\n";
        break;
      case "OPS":
      case "OPK":
        $op = $tree["VALUE"];
        if (isset($tree["PNUM"]))
          $op .= "[" . $tree["PNUM"] . "]";
        $out .= "\t<td colspan=\"" . $tree["COMPLEXITY"] . "\">$op</td>\n";
        $this->astable2($out, $tree["LB"], $depth + 1);
        $this->astable2($out, $tree["RB"], $depth + 1);
        $out .= "</tr>\n<tr>\n";
        break;
    }
  }

  function dumpDiv(&$tree)
  {
    print("<div class=\"explain\">\n");
    $this->dumpDiv2($tree);
    print("</div>\n");
  }

  function dumpDiv2(&$tree, $depth=0)
  {
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
        if (is_array($tree["VALUE"]))
          $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
        else
          $s = $tree["VALUE"];
        print(str_repeat("\t", $depth) . "<b><font color='green'>" . $s . "</font></b>\n");
      case "QSIMPLE":
        $s = "";
        if (is_array($tree["VALUE"]))
          $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
        else
          $s = $tree["VALUE"];
        print(str_repeat("\t", $depth) . "&quot;<b><font color='green'>" . $s . "</font></b>&quot;\n");
        break;
      case "PHRASEA_KW_ALL":
        printf(str_repeat("\t", $depth) . "<b><font color='red'>%s</font></b>\n", $tree["VALUE"][0]);
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)
          printf(str_repeat("\t", $depth) . "<b><font color='blue'>%s <i>%s</i></font></b>\n", $tree["VALUE"][0], $tree["PNUM"]);
        else
          printf(str_repeat("\t", $depth) . "<b><font color='blue'>%s</font></b>\n", $tree["VALUE"][0]);
        break;
      //    case PHRASEA_KW_FIRST:
      //      if($tree["PNUM"]!==null)
      //        printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"], $tree["PNUM"]);
      //      else
      //        printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"]);
      //      break;
      case "OPS":
      case "OPK":
        print(str_repeat("\t", $depth) . "<div>\n");
        $this->dumpDiv2($tree["LB"], $depth + 1);
        print(str_repeat("\t", $depth) . "</div>\n");
        print(str_repeat("\t", $depth) . "<div>\n");
        if (isset($tree["PNUM"]))
          printf(str_repeat("\t", $depth + 1) . " %s[%s]\n", $tree["VALUE"], $tree["PNUM"]);
        else
          printf(str_repeat("\t", $depth + 1) . " %s\n", $tree["VALUE"]);
        print(str_repeat("\t", $depth) . "</div>\n");
        print(str_repeat("\t", $depth) . "<div>\n");
        $this->dumpDiv2($tree["RB"], $depth + 1);
        print(str_repeat("\t", $depth) . "</div>\n");

        break;
    }
  }

  function dump($tree)
  {
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
        if (is_array($tree["VALUE"]))
          $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
        else
          $s = $tree["VALUE"];
        print("<b><font color='green'>" . $s . "</font></b>");
        break;
      case "QSIMPLE":
        if (is_array($tree["VALUE"]))
          $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
        else
          $s = $tree["VALUE"];
        print("&quot;<b><font color='green'>" . $s . "</font></b>&quot;");
        break;
      case "PHRASEA_KW_ALL":
        printf("<b><font color='red'>%s</font></b>", $tree["VALUE"][0]);
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)
          printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"][0], $tree["PNUM"]);
        else
          printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"][0]);
        break;
      //    case PHRASEA_KW_FIRST:
      //      if($tree["PNUM"]!==null)
      //        printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"], $tree["PNUM"]);
      //      else
      //        printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"]);
      //      break;
      case "OPS":
      case "OPK":
        print("<table border='1'>");
        print("<tr>");
        print("<td colspan='2' align='center'>");
        if (isset($tree["PNUM"]))
          printf(" %s[%s] ", $tree["VALUE"], $tree["PNUM"]);
        else
          printf(" %s ", $tree["VALUE"]);
        print("</td>");
        print("</tr>");
        print("<tr>");
        print("<td width='50%' align='center' valign='top'>");
        print($this->dump($tree["LB"]));
        print("</td>");
        print("<td width='50%' align='center' valign='top'>");
        print($this->dump($tree["RB"]));
        print("</td>");
        print("</tr>");
        print("</table>");
        break;
    }
  }

  function priority_opk(&$tree, $depth=0)
  {
    if (!$tree)

      return;
    if ($tree["CLASS"] == "OPK" && ($tree["LB"]["CLASS"] == "OPS" || $tree["LB"]["CLASS"] == "OPK"))
    {
      // on a un truc du genre ((a ou b) < 5), on le transforme en (a ou (b < 5))
      $t = $tree["LB"];
      $tree["LB"] = $t["RB"];
      $t["RB"] = $tree;
      $tree = $t;
    }
    if (isset($tree["LB"]))
      $this->priority_opk($tree["LB"], $depth + 1);
    if (isset($tree["RB"]))
      $this->priority_opk($tree["RB"], $depth + 1);
  }

  function distrib_opk(&$tree, $depth=0)
  {
    if (!$tree)

      return;
    if ($tree["CLASS"] == "OPK" && ($tree["RB"]["CLASS"] == "OPS"))
    {
      // on a un truc du genre (a = (5 ou 6)), on le transforme en ((a = 5) ou (a = 6))
      $tmp = array("CLASS" => $tree["CLASS"],
          "NODETYPE" => $tree["NODETYPE"],
          "VALUE" => $tree["VALUE"],
          "PNUM" => $tree["PNUM"],
          "LB" => $tree["LB"],
          "RB" => $tree["RB"]["RB"],
          "DEPTH" => $tree["LB"]["DEPTH"]);
      $t = $tree["RB"];
      $tree["RB"] = $t["LB"];
      $t["LB"] = $tree;
      $t["RB"] = $tmp;
      $tree = $t;
    }
    if (isset($tree["LB"]))
      $this->distrib_opk($tree["LB"], $depth + 1);
    if (isset($tree["RB"]))
      $this->distrib_opk($tree["RB"], $depth + 1);
  }

  function thesaurus2_apply(&$tree, $bid)
  {
    if (!$tree)

      return;
    if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE") && isset($tree["SREF"]) && isset($tree["SREF"]["TIDS"]))
    {
      $tids = array();
      foreach ($tree["SREF"]["TIDS"] as $tid)
      {
        if ($tid["bid"] == $bid)
          $tids[] = $tid["pid"];
      }
      if (count($tids) >= 1)
      {
        /*
          if(count($tids)==1)
          {
          // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finit par '%')
          $val = str_replace(".", "d", $tids[0]) . "d%";
          $tree["VALUE"] = array($val);
          }
          else
          {
          // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle méme la syntaxe car la value finit par '$'
          $val = "";
          foreach($tids as $tid)
          $val .= ($val?"|":"") . "(" . str_replace(".", "d", $tid) . "d.*)";
          $tree["VALUE"] = array("^" . $val);
          }
         */
        $tree["VALUE"] = array();
        foreach ($tids as $tid)
          $tree["VALUE"][] = str_replace(".", "d", $tid) . "d%";;
      }
      else
      {
        // le mot n'est pas dans le thesaurus
      }
      /*
       */
    }
    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      $this->thesaurus2_apply($tree["LB"], $bid);
      $this->thesaurus2_apply($tree["RB"], $bid);
    }
  }

  // étend (ou remplace) la recherche sur les termes simples en recherche sur thesaurus
  // ex: (a et b)
  // full-text only :  ==> (a et b)
  // thesaurus only :  ==> ((th:a) et (th:b))
  // ft et thesaurus : ==> ((a ou (th:a)) et (b ou (th:b)))
  // RETOURNE l'arbre résultat sans modifier l'arbre d'origine
  function extendThesaurusOnTerms(&$tree, $useFullText, $useThesaurus, $keepfuzzy)
  {
    $copy = $tree;
    $this->_extendThesaurusOnTerms($tree, $copy, $useFullText, $useThesaurus, $keepfuzzy, 0, "");

    // var_dump($tree);
    $this->proposals["QRY"] = "<span id=\"thprop_q\">" . $this->_queryAsHTML($tree) . "</span>";

    return($copy);
  }

  function _extendThesaurusOnTerms(&$tree, &$copy, $useFullText, $useThesaurus, $keepfuzzy, $depth, $path)
  {
    if ($depth == 0)
      $ret = $tree;
    if (!$useThesaurus)

      return;  // full-text only : inchangé
 if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE"))
    {
      if (isset($tree["CONTEXT"]))
        $copy = $this->_extendToThesaurus_Simple($tree, false, $keepfuzzy, $path);
      else
        $copy = $this->_extendToThesaurus_Simple($tree, $useFullText, $keepfuzzy, $path);
    }
    else
    {
      if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON)
      {
        // on a 'field:value'  , on traite 'value'
        $tree["RB"]["PATH"] = $copy["RB"]["PATH"] = $path . "R";
        if (isset($tree["RB"]["CONTEXT"]))
          $copy["CONTEXT"] = $tree["CONTEXT"] = $tree["RB"]["CONTEXT"];
        else
        if (!$keepfuzzy)
          $copy["CONTEXT"] = $tree["CONTEXT"] = "*";

        $copy["RB"]["SREF"] = &$tree["RB"];
      }
      else
      {
        $recursL = $recursR = false;
        if ($tree["CLASS"] == "OPS" && ($tree["NODETYPE"] == PHRASEA_OP_AND || $tree["NODETYPE"] == PHRASEA_OP_OR || $tree["NODETYPE"] == PHRASEA_OP_EXCEPT))
        {
          // on a une branche à gauche de 'ET', 'OU', 'SAUF'
          $recursL = true;
        }
        if ($tree["CLASS"] == "OPS" && ($tree["NODETYPE"] == PHRASEA_OP_AND || $tree["NODETYPE"] == PHRASEA_OP_OR || $tree["NODETYPE"] == PHRASEA_OP_EXCEPT))
        {
          // on a une branche à droite de 'ET', 'OU', 'SAUF'
          $recursR = true;
        }
        if ($recursL)
          $this->_extendThesaurusOnTerms($tree["LB"], $copy["LB"], $useFullText, $useThesaurus, $keepfuzzy, $depth + 1, $path . "L");
        if ($recursR)
          $this->_extendThesaurusOnTerms($tree["RB"], $copy["RB"], $useFullText, $useThesaurus, $keepfuzzy, $depth + 1, $path . "R");
      }
    }
  }

  // étend (ou remplace) un terme cherché en 'full-text' à une recherche thesaurus (champ non spécifié, tout le thésaurus = '*')
  // le contexte éventuel est rapporté à l'opérateur ':'
  // ex : a[k]   ==>   (a ou (TH :[k] a))
  function _extendToThesaurus_Simple(&$simple, $keepFullText, $keepfuzzy, $path)
  {
    $simple["PATH"] = $path;
    $context = null;
    if (isset($simple["CONTEXT"]))
    {
      $context = $simple["CONTEXT"];
      // unset($simple["CONTEXT"]);
    }
    if ($keepFullText)
    {
      // on fait un OU entre la recherche ft et une recherche th
      $tmp = array("CLASS" => "OPS",
          "NODETYPE" => PHRASEA_OP_OR,
          "VALUE" => "OR",
          "PNUM" => null,
          "DEPTH" => $simple["DEPTH"],
          "LB" => $simple,
          "RB" => array("CLASS" => "OPK",
              "NODETYPE" => PHRASEA_OP_COLON,
              "VALUE" => ":",
              // "CONTEXT"=>$context,
              "PNUM" => null,
              "DEPTH" => $simple["DEPTH"] + 1,
              "LB" => array("CLASS" => "SIMPLE",
                  "NODETYPE" => PHRASEA_KEYLIST,
                  "VALUE" => array("*"),
                  "DEPTH" => $simple["DEPTH"] + 2
              ),
              "RB" => $simple
          )
      );
      // on vire le contexte  du coté fulltext
      unset($tmp["LB"]["CONTEXT"]);
      // ajoute le contexte si nécéssaire
      if ($context !== null)
        $tmp["RB"]["CONTEXT"] = $context;
      else
      if (!$keepfuzzy)
        $tmp["RB"]["CONTEXT"] = "*";
      // corrige les profondeurs des 2 copies du 'simple' d'origine
      $tmp["LB"]["DEPTH"] += 1;
      $tmp["RB"]["RB"]["DEPTH"] += 2;
      // note une référence vers le terme d'origine
      $tmp["RB"]["RB"]["SREF"] = &$simple;
      $tmp["RB"]["RB"]["PATH"] = $path;
    }
    else
    {
      // on remplace le ft par du th
      $tmp = array("CLASS" => "OPK",
          "NODETYPE" => PHRASEA_OP_COLON,
          "VALUE" => ":",
          // "CONTEXT"=>$context,
          "PNUM" => null,
          "DEPTH" => $simple["DEPTH"] + 1,
          "LB" => array("CLASS" => "SIMPLE",
              "NODETYPE" => PHRASEA_KEYLIST,
              "VALUE" => array("*"),
              "DEPTH" => $simple["DEPTH"] + 1
          ),
          "RB" => $simple
      );
      // ajoute le contexte si nécéssaire
      if ($context !== null)
        $tmp["CONTEXT"] = $context;
      else
      if (!$keepfuzzy)
        $tmp["CONTEXT"] = "*";
      // corrige la profondeur de la copie du 'simple' d'origine
      $tmp["RB"]["DEPTH"] += 1;
      // note une référence vers le terme d'origine
      $tmp["RB"]["SREF"] = &$simple;
      $tmp["RB"]["PATH"] = $path;
    }

    return($tmp);
  }

  function thesaurus2(&$tree, $bid, $name, &$domthe, $searchsynonyms=true, $depth=0)
  {
    if ($this->debug)
      print("thesaurus2:\n\$tree=" . var_export($tree, true) . "\n");

    if ($depth == 0)
      $this->proposals["BASES"]["b$bid"] = array("BID" => $bid, "NAME" => $name, "TERMS" => array());

    if (!$tree)

      return(0);

    $ambigus = 0;
    if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON)
    {
//      $ambigus = $this->setTids($tree, $tree["RB"], $bid, $domthe, $searchsynonyms);
      $ambigus = $this->setTids($tree, $bid, $domthe, $searchsynonyms);
    }
    elseif ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      $ambigus += $this->thesaurus2($tree["LB"], $bid, $name, $domthe, $searchsynonyms, $depth + 1);
      $ambigus += $this->thesaurus2($tree["RB"], $bid, $name, $domthe, $searchsynonyms, $depth + 1);
    }

    return($ambigus);
  }

  function propAsHTML(&$node, &$html, $path, $depth=0)
  {
    global $parm;
    if ($depth > 0)
    {
      $tsy = array();
      $lngfound = "?";
      for ($n = $node->firstChild; $n; $n = $n->nextSibling)
      {
        if ($n->nodeName == "sy")
        {
          $lng = $n->getAttribute("lng");
          if (!array_key_exists($lng, $tsy))
            $tsy[$lng] = array();
          $zsy = array("v" => $n->getAttribute("v"), "w" => $n->getAttribute("w"), "k" => $n->getAttribute("k"));

          if ($lngfound == "?" || ($lng == $this->lng && $lngfound != $lng))
          {
            $lngfound = $lng;
            $syfound = $zsy;
          }
          else
          {

          }
          $tsy[$lng][] = $zsy;
        }
      }
      $alt = "";
      foreach ($tsy as $lng => $tsy2)
      {
        foreach ($tsy2 as $sy)
        {
          $alt .= $alt ? "\n" : "";
          $alt .= "" . $lng . ": " . p4string::MakeString($sy["v"], "js");
        }
      }

      $thtml = $syfound["v"];
      $kjs = $syfound["k"] ? ("'" . p4string::MakeString($syfound["k"], "js") . "'") : "null";
      $wjs = "'" . p4string::MakeString($syfound["w"], "js") . "'";

      if ($node->getAttribute("term"))
      {
        $thtml = "<b>" . $thtml . "</b>";
        $node->removeAttribute("term");
      }

      $tab = str_repeat("\t", $depth);
      $html .= $tab . "<div style=\"position:relative; left:10px;\">\n";
      $html .= $tab . "\t<a title=\"" . $alt . "\" href=\"javascript:void();\" onclick=\"return(chgProp('" . $path . "', " . $wjs . ", " . $kjs . "));\">" . $thtml . "</a>\n";
    }

    $tsort = array();
    for ($n = $node->firstChild; $n; $n = $n->nextSibling)
    {
      if ($n->nodeType == XML_ELEMENT_NODE && $n->getAttribute("marked"))  // only 'te' marked
      {
        $lngfound = '?';
        $syfound = '?';
        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling)
        {
          if ($n2->nodeName == 'sy')
          {
            $lng = $n2->getAttribute('lng');
            if ($lngfound == "?" || ($lng == $this->lng && $lngfound != $lng))
            {
              $lngfound = $lng;
              $syfound = $n2->getAttribute('w');
            }
          }
        }
        $n->removeAttribute("marked");
        for ($i = 0; array_key_exists($syfound . $i, $tsort) && $i < 9999; $i++)
          ;
        $tsort[$syfound . $i] = $n;
      }
    }
    ksort($tsort);

//    var_dump($tsort);

    foreach ($tsort as $n)
    {
      $this->propAsHTML($n, $html, $path, $depth + 1);
    }

    if ($depth > 0)
      $html .= $tab . "</div>\n";
  }

  function _queryAsHTML($tree, $depth=0)
  {
    // printf("astext : ");
    // var_dump($tree);
    if ($depth == 0)
    {
      $ambiguites = array("n" => 0, "refs" => array());
    }
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
      case "QSIMPLE":
        $w = is_array($tree["VALUE"]) ? implode(' ', $tree["VALUE"]) : $tree["VALUE"];
        if (isset($tree["PATH"]))
        {
          $path = $tree["PATH"];
          if (isset($tree["CONTEXT"]))
            $w .= ' [' . $tree["CONTEXT"] . ']';
          $txt = '<span id="thprop_a_' . $path . '">"' . $w . '"</span>';
        }
        else
        {
          if (isset($tree["CONTEXT"]))
            $w .= '[' . $tree["CONTEXT"] . ']';
          if ($tree["CLASS"] == "QSIMPLE")
            $txt = '"' . $w . '"';
          else
            $txt = $w;
        }

        return($txt);
        break;
      case "PHRASEA_KW_ALL":
        return($tree["VALUE"][0]);
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)

          return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
        else

          return($tree["VALUE"][0]);
        break;
      case "OPS":
      case "OPK":
        if (isset($tree["PNUM"]))

          return('(' . $this->_queryAsHTML($tree["LB"], $depth + 1) . ' ' . $tree["VALUE"] . '[' . $tree["PNUM"] . '] ' . $this->_queryAsHTML($tree["RB"], $depth + 1) . ')');
        else

          return('(' . $this->_queryAsHTML($tree["LB"], $depth + 1) . ' ' . $tree["VALUE"] . ' ' . $this->_queryAsHTML($tree["RB"], $depth + 1) . ')');
        break;
    }
  }

  /*
    function _queryAsHTML($tree, $mouseCallback="void", $depth=0)
    {
    // printf("astext : ");
    // var_dump($tree);
    if($depth==0)
    {
    $ambiguites = array("n"=>0, "refs"=>array());
    }
    switch($tree["CLASS"])
    {
    case "SIMPLE":
    case "QSIMPLE":
    $w = is_array($tree["VALUE"]) ? implode(" ", $tree["VALUE"]) : $tree["VALUE"];
    $tab = "\n" . str_repeat("\t", $depth);
    if(isset($tree["PATH"]))
    {
    $path = $tree["PATH"];
    if(isset($tree["CONTEXT"]))
    $w .= " [" . $tree["CONTEXT"] . "]";
    $txt  = $tab . "<b><span onmouseover=\"return(".$mouseCallback."(event, '$path'));\" onmouseout=\"return(".$mouseCallback."(event, '$path'));\" id=\"thprop_a_".$path."\">";
    $txt .= $tab . "\t\"" . $w . "";
    //  $txt .= $tab . "\t<span id='thprop_w_".$path."'></span>\"";
    $txt .= "\"";
    $txt .= $tab . "</span></b>\n";
    }
    else
    {
    if(isset($tree["CONTEXT"]))
    $w .= "[" . $tree["CONTEXT"] . "]";
    if($tree["CLASS"] == "QSIMPLE")
    $txt = $tab . "\"" . $w . "\"\n";
    else
    $txt = $tab . "" . $w . "\n";
    }

    return($txt);
    break;
    case "PHRASEA_KW_ALL":
    return($tree["VALUE"][0]);
    break;
    case "PHRASEA_KW_LAST":
    if($tree["PNUM"]!==null)

    return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
    else

    return($tree["VALUE"][0]);
    break;
    case "OPS":
    case "OPK":
    if(isset($tree["PNUM"]))

    return("(" . $this->_queryAsHTML($tree["LB"], $mouseCallback, $depth+1) . "&nbsp;" .  $tree["VALUE"] . "[" . $tree["PNUM"] . "]&nbsp;" . $this->_queryAsHTML($tree["RB"], $mouseCallback, $depth+1) . ")");
    else

    return("(" . $this->_queryAsHTML($tree["LB"], $mouseCallback, $depth+1) . "&nbsp;" .  $tree["VALUE"] . "&nbsp;" . $this->_queryAsHTML($tree["RB"], $mouseCallback, $depth+1) . ")");
    break;
    }
    }
   */

  function setTids(&$tree, $bid, &$domthe, $searchsynonyms)
  {
    if ($this->debug)
      print("============================ setTids:\n\$tree=" . var_export($tree, true) . "\n");

    // $this->proposals["BASES"]["b$bid"] = array("BID"=>$bid, "TERMS"=>array());

    $ambigus = 0;
    if (is_array($w = $tree["RB"]["VALUE"]))
      $t = $w = implode(" ", $w);

    if (isset($tree["CONTEXT"]))
    {
      if (!$tree["CONTEXT"])
      {
        $x0 = "@w=\"" . $w . "\" and not(@k)";
      }
      else
      {
        if ($tree["CONTEXT"] == "*")
        {
          $x0 = "@w=\"" . $w . "\"";
        }
        else
        {
          $x0 = "@w=\"" . $w . "\" and @k=\"" . $tree["CONTEXT"] . "\"";
          $t .= " (" . $tree["CONTEXT"] . ")";
        }
      }
    }
    else
    {
      $x0 = "@w=\"" . $w . "\"";
    }

    $x = "/thesaurus//sy[" . $x0 . "]";

    if ($this->debug)
      printf("searching thesaurus with xpath='%s'<br/>\n", $x);

    $dxp = new DOMXPath($domthe);
    $nodes = $dxp->query($x);

    if (!isset($tree["RB"]["SREF"]["TIDS"]))
      $tree["RB"]["SREF"]["TIDS"] = array();
    if ($nodes->length >= 1)
    {
      if ($nodes->length == 1)
      {
        // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finira par '%')
        $this->addtoTIDS($tree["RB"], $bid, $nodes->item(0));
        // $this->thesaurusDOMNodes[] = $nodes->item(0);
      }
      else
      {
        // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle meme la syntaxe car la value finira par '$')
        $val = "";
        foreach ($nodes as $node)
        {
          if (!isset($tree["CONTEXT"]))
            $ambigus++;
          $this->addtoTIDS($tree["RB"], $bid, $node);
        }
      }
      $path = $tree["RB"]["SREF"]["PATH"];
      $prophtml = "";
      $this->propAsHTML($domthe->documentElement, $prophtml, $path);
      $this->proposals["BASES"]["b$bid"]["TERMS"][$path]["HTML"] = $prophtml;
    }
    else
    {
      // le mot n'est pas dans le thesaurus
    }

    return($ambigus);
  }

  /*
    function dead_setTids(&$tree, &$simple, $bid, &$domthe, $searchsynonyms)
    {
    // if($this->debug)
    print("setTids:\n\$tree=" . var_export($tree, true) . "\n");

    $ambigus = 0;
    if(is_array($w = $simple["VALUE"]))
    $t = $w = implode(" ", $w);

    if(isset($tree["CONTEXT"]))
    {
    if(!$tree["CONTEXT"])
    {
    $x0 = "@w=\"" . $w ."\" and not(@k)";
    }
    else
    {
    if($tree["CONTEXT"]=="*")
    {
    $x0 = "@w=\"" . $w ."\"";
    }
    else
    {
    $x0 = "@w=\"" . $w ."\" and @k=\"" . $tree["CONTEXT"] . "\"";
    $t .= " (" . $tree["CONTEXT"] . ")";
    }
    }
    }
    else
    {
    $x0 = "@w=\"" . $w ."\"";
    }

    $x = "/thesaurus//sy[" . $x0 ."]";

    if($this->debug)
    printf("searching thesaurus with xpath='%s'<br/>\n", $x);

    $dxp = new DOMXPath($domthe);
    $nodes = $dxp->query($x);

    if(!isset($tree["RB"]["SREF"]["TIDS"]))
    $tree["RB"]["SREF"]["TIDS"] = array();
    if($nodes->length >= 1)
    {
    if($nodes->length == 1)
    {
    // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finira par '%')
    $this->addtoTIDS($tree["RB"], $bid, $nodes->item(0));
    // $this->thesaurusDOMNodes[] = $nodes->item(0);
    }
    else
    {
    // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle meme la syntaxe car la value finira par '$')
    $val = "";
    foreach($nodes as $node)
    {
    if(!isset($tree["CONTEXT"]))
    $ambigus++;
    $this->addtoTIDS($tree["RB"], $bid, $node);
    }
    }
    $path = $tree["RB"]["SREF"]["PATH"];
    $prophtml = "";
    $this->propAsHTML($domthe->documentElement, $prophtml, $path);
    $this->proposals["TERMS"][$path]["HTML"] = $prophtml;
    }
    else
    {
    // le mot n'est pas dans le thesaurus
    }

    return($ambigus);
    }
   */

  function containsColonOperator(&$tree)
  {
    if (!$tree)

      return(false);
    if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE"))

      return(true);
    $ret = false;
    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      $ret |= $this->containsColonOperator($tree["LB"]);
      $ret |= $this->containsColonOperator($tree["RB"]);
    }

    return($ret);
  }

  function addtoTIDS(&$extendednode, $bid, $DOMnode) // ajoute un tid en évitant les doublons
  {
    $id = $DOMnode->getAttribute("id");
    $pid = $DOMnode->parentNode->getAttribute("id");
    $lng = $DOMnode->getAttribute("lng");
    $w = $DOMnode->getAttribute("w");
    $k = $DOMnode->getAttribute("k");
    $p = $DOMnode->parentNode->getAttribute("v"); // le terme général (pére) du terme recherché : utile pour la levée d'ambiguité

    $path = $extendednode["SREF"]["PATH"];
    if ($this->debug)
      printf("found node id='%s', v='%s' w='%s', k='%s', p='%s' for node-path=%s \n", $id, $DOMnode->getAttribute("v"), $w, $k, $p, $path);

    if (!$k)
      $k = null;

    $found = false;
    foreach ($extendednode["SREF"]["TIDS"] as $ztid)
    {
      if ($ztid["bid"] != $bid)
        continue;
      if ($ztid["pid"] == $pid)
      {
        $found = true;
      }
      else
      {
//        if($ztid["w"]==$w && $ztid["k"]==$k && $ztid["lng"]==$lng)
//        {
//          // FATAL : il y a un doublon réel dans le thesaurus de cette base (méme terme, méme contexte)
//          //    printf("<font color='red'>FATAL doublon on base %d (%s[%s])</font>\n", $bid, $w, $k);
//          $found = true;
//          break;
//        }
      }
    }
    if (!$found)
      $extendednode["SREF"]["TIDS"][] = array("bid" => $bid, "pid" => $pid, "id" => $id, "w" => $w, "k" => $k, "lng" => $lng, "p" => $p);

    // on liste les propositions de thésaurus pour ce node (dans l'arbre simple)
    if (!isset($this->proposals["BASES"]["b$bid"]["TERMS"][$path]))
    {
      //  $this->proposals["TERMS"][$path] = array("TERM"=>implode(" ", $extendednode["VALUE"]), "PROPOSALS"=>array());
      $term = implode(" ", $extendednode["VALUE"]);
      if (isset($extendednode["CONTEXT"]) && $extendednode["CONTEXT"])
      {
        $term .= " (" . $extendednode["CONTEXT"] . ")";
      }
      $this->proposals["BASES"]["b$bid"]["TERMS"][$path] = array("TERM" => $term); // , "PROPOSALS"=>array() ); //, "PROPOSALS_TREE"=>new DOMDocument("1.0", "UTF-8"));
    }
// printf("<%s id='%s'><br/>\n", $DOMnode->tagName, $DOMnode->getAttribute("id"));
//    printf("<b>found node &lt;%s id='%s' w='%s' k='%s'></b><br/>\n", $DOMnode->nodeName, $DOMnode->getAttribute('id'), $DOMnode->getAttribute('w'), $DOMnode->getAttribute('k'));
    // on marque le terme principal
    $DOMnode->parentNode->setAttribute("term", "1");
    // on commence par marquer les fils directs. rappel:$DOMnode pointe sur un sy
    for ($node = $DOMnode->parentNode->firstChild; $node; $node = $node->nextSibling)
    {
      if ($node->nodeName == "te")
      {
        $node->setAttribute("marked", "1");
      }
    }
    // puis par remonter au père
    for ($node = $DOMnode->parentNode; $node && $node->nodeType == XML_ELEMENT_NODE && $node->parentNode; $node = $node->parentNode)
    {
      $id = $node->getAttribute("id");
      if (!$id)
        break; // on a dépassé la racine du thésaurus
 $node->setAttribute("marked", "1");
// printf("&lt;%s id='%s'<br/>\n", $node->nodeName, $node->getAttribute("id"));
    }
  }

  function astext_ambigu($tree, &$ambiguites, $mouseCallback="void", $depth=0)
  {
    // printf("astext : ");
    // var_dump($tree);
    if ($depth == 0)
    {
      $ambiguites = array("n" => 0, "refs" => array());
    }
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
      case "QSIMPLE":
        $prelink = $postlink = "";
        $w = is_array($tree["VALUE"]) ? implode(" ", $tree["VALUE"]) : $tree["VALUE"];
        $tab = "\n" . str_repeat("\t", $depth);
        if (isset($tree["TIDS"]) && count($tree["TIDS"]) > 1)
        {
          $ambiguites["refs"][$n = $ambiguites["n"]] = &$tree;
          $txt = $tab . "<b><span onmouseover=\"return(" . $mouseCallback . "(event, $n));\" onmouseout=\"return(" . $mouseCallback . "(event, $n));\" id=\"thamb_a_" . $ambiguites["n"] . "\">";
          $txt .= $tab . "\t\"" . $w . "";
          $txt .= $tab . "\t<span id='thamb_w_" . $ambiguites["n"] . "'></span>\"";
          $txt .= $tab . "</span></b>\n";
          $ambiguites["n"]++;
        }
        else
        {
          if (isset($tree["CONTEXT"]))
            $w .= "[" . $tree["CONTEXT"] . "]";
          if ($tree["CLASS"] == "QSIMPLE")
            $txt = $tab . "\"" . $w . "\"\n";
          else
            $txt = $tab . "" . $w . "\n";
        }

        return($txt);
        break;
      case "PHRASEA_KW_ALL":
        return($tree["VALUE"][0]);
        break;
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== null)

          return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
        else

          return($tree["VALUE"][0]);
        break;
      case "OPS":
      case "OPK":
        if (isset($tree["PNUM"]))

          return("(" . $this->astext_ambigu($tree["LB"], $ambiguites, $mouseCallback, $depth + 1) . " " . $tree["VALUE"] . "[" . $tree["PNUM"] . "] " . $this->astext_ambigu($tree["RB"], $ambiguites, $mouseCallback, $depth + 1) . ")");
        else

          return("(" . $this->astext_ambigu($tree["LB"], $ambiguites, $mouseCallback, $depth + 1) . " " . $tree["VALUE"] . " " . $this->astext_ambigu($tree["RB"], $ambiguites, $mouseCallback, $depth + 1) . ")");
        break;
    }
  }

  function get_ambigu(&$tree, $mouseCallback="void", $depth=0)
  {
    if (!$tree)

      return("");
    unset($tree["DEPTH"]);
    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      $this->get_ambigu($tree["LB"], $mouseCallback, $depth + 1);
      $this->get_ambigu($tree["RB"], $mouseCallback, $depth + 1);
    }
    else
    {

    }
    if ($depth == 0)
    {
      $t_ambiguites = array();
      $r = ($this->astext_ambigu($tree, $t_ambiguites, $mouseCallback));
      $t_ambiguites["query"] = $r;

      return($t_ambiguites);
    }
  }

  function set_default(&$tree, &$emptyw, $depth=0)
  {
    if (!$tree)

      return(true);
    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      if ($tree["CLASS"] == "OPS")
      {
        if (!$this->set_default($tree["LB"], $emptyw, $depth + 1))

          return(false);
        if (!$this->set_default($tree["RB"], $emptyw, $depth + 1))

          return(false);
      }
      else // OPK !
      {
        // jy 20041223 : ne pas appliquer d'op. par def. derriere un op arith.
        // ex : "d < 1/2/2003" : grouper la liste "1","2","2004" en "mot" unique
        if (!$tree["LB"] || ($tree["LB"]["CLASS"] != "SIMPLE" && $tree["LB"]["CLASS"] != "QSIMPLE") || (is_array($tree["LB"]["VALUE"]) && count($tree["LB"]["VALUE"]) != 1))
        {
          // un op. arith. doit étre précédé d'un seul nom de champ
          if ($this->errmsg != "")
            $this->errmsg .= sprintf("\\n");
          $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, un nom de champs est attendu avant l operateur %s'), $tree["VALUE"]);

          return(false);
        }
        if (!$tree["RB"] || ($tree["RB"]["CLASS"] != "SIMPLE" && $tree["RB"]["CLASS"] != "QSIMPLE"))
        {
          // un op. arith. doit étre suivi d'une valeur
          if ($this->errmsg != "")
            $this->errmsg .= sprintf("\\n");
          $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, une valeur est attendue apres l operateur %s'), $tree["VALUE"]);

          return(false);
        }
        if (is_array($tree["RB"]["VALUE"]))
        {
          $lw = "";
          foreach ($tree["RB"]["VALUE"] as $w)
            $lw .= ( $lw == "" ? "" : " ") . $w;
          $tree["RB"]["VALUE"] = $lw;
        }
      }

      /** gestion des branches null
       *   a revoir car ca ppete pas d'erreur mais corrige automatiquement
       * ** */
      if (!isset($tree["RB"]))
        $tree = $tree["LB"];
      else
      if (!isset($tree["LB"]))
        $tree = $tree["RB"];
    }
    else
    {
      if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE"))
      {
        if (is_array($tree["VALUE"]))
        {
          $treetmp = null;
          $pnum = 0;
          for ($i = 0; $i < count($tree["VALUE"]); $i++)
          {
            // gestion mot vide
            if (isset($emptyw[$tree["VALUE"][$i]]) || $tree["VALUE"][$i] == "?" || $tree["VALUE"][$i] == "*")
            {
              // on a forcé les '?' ou '*' isolés comme des mots vides
              $pnum++;
            }
            else
            {
              if ($treetmp == null)
              {
                $treetmp = array("CLASS" => $tree["CLASS"],
                    "NODETYPE" => $tree["NODETYPE"],
                    "VALUE" => $tree["VALUE"][$i],
                    "PNUM" => $tree["PNUM"],
                    "DEPTH" => $tree["DEPTH"]);
                $pnum = 0;
              }
              else
              {
                $dop = $tree["CLASS"] == "QSIMPLE" ? $this->quoted_defaultop : $this->defaultop;
                $treetmp = array("CLASS" => "OPS",
                    "VALUE" => $dop["VALUE"],
                    "NODETYPE" => $dop["NODETYPE"],
                    "PNUM" => $pnum, // peut-être écrasé par defaultop
                    "DEPTH" => $depth,
                    "LB" => $treetmp,
                    "RB" => array("CLASS" => $tree["CLASS"],
                        "NODETYPE" => $tree["NODETYPE"],
                        "VALUE" => $tree["VALUE"][$i],
                        "PNUM" => $tree["PNUM"],
                        "DEPTH" => $tree["DEPTH"])
                );
                if (array_key_exists("PNUM", $dop))
                  $treetmp["PNUM"] = $dop["PNUM"];
                $pnum = 0;
              }
            }
          }
          $tree = $treetmp;
        }
      }
    }

    return(true);
  }

  function factor_or(&$tree)
  {
    do
      $n = $this->factor_or2($tree);
    while ($n > 0);
  }

  function factor_or2(&$tree, $depth=0)
  {
    // printf("<hr><b>factor_or depth=%s sur</b><br>", $depth);
    // var_dump($tree);
    $nmodif = 0;
    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      if ($tree["NODETYPE"] == PHRASEA_OP_OR && ($tree["LB"]["CLASS"] == "SIMPLE" || $tree["LB"]["CLASS"] == "QSIMPLE") && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE"))
      {
        $tree["CLASS"] = "SIMPLE";
        $tree["NODETYPE"] = PHRASEA_KEYLIST;
        $tree["VALUE"] = is_array($tree["LB"]["VALUE"]) ? $tree["LB"]["VALUE"] : array($tree["LB"]["VALUE"]);
        if (is_array($tree["RB"]["VALUE"]))
        {
          foreach ($tree["RB"]["VALUE"] as $v)
            $tree["VALUE"][] = $v;
        }
        else
          $tree["VALUE"][] = $tree["RB"]["VALUE"];
        unset($tree["LB"]);
        unset($tree["RB"]);
        // unset($tree["NODETYPE"]);
        unset($tree["PNUM"]);
        $nmodif++;
        // printf("<hr><b>donne</b><br>");
        // var_dump($tree);
      }
      else
      {
        $nmodif += $this->factor_or2($tree["LB"], $depth + 1);
        $nmodif += $this->factor_or2($tree["RB"], $depth + 1);
      }
    }
    // printf("<br>return %s<br>", $nmodif);
    return($nmodif);
  }

  function setNumValue(&$tree, SimpleXMLElement $sxml_struct, $depth=0)
  {
    // var_dump($tree);
    if ($tree["CLASS"] == "OPK")
    {
      if (isset($tree["RB"]) && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE") && ($tree["LB"]["CLASS"] == "SIMPLE" || $tree["LB"]["CLASS"] == "QSIMPLE"))
      {
        $z = $sxml_struct->xpath('/record/description');
        if ($z && is_array($z))
        {
          foreach ($z[0] as $ki => $vi)
          {
            $champ = null;
            if (is_array($tree["LB"]["VALUE"]))
              $champ = $tree["LB"]["VALUE"][0];
            else
              $champ = $tree["LB"]["VALUE"];
            if ($champ && strtoupper($ki) == strtoupper($champ))
            {
              foreach ($vi->attributes() as $propname => $val)
              {
                if (strtoupper($propname) == strtoupper("type"))
                {
                  if ($tree["NODETYPE"] == PHRASEA_OP_EQUAL) // cas particulier du "=" sur une date
                    $this->changeNodeEquals($tree, $val);
                  else
                    $this->setNumValue2($tree["RB"], $val);
                }
              }
            }
          }
        }
      }
    }
    if (isset($tree["LB"]))
      $this->setNumValue($tree["LB"], $sxml_struct, $depth + 1);
    if (isset($tree["RB"]))
      $this->setNumValue($tree["RB"], $sxml_struct, $depth + 1);
  }

  function changeNodeEquals(&$branch, $type)
  {
    if (strtoupper($type) == strtoupper("Date"))
    {
      $branch = $this->changeNodeEquals2($branch);
    }
  }

  function changeNodeEquals2($oneBranch)
  {
    ## creation branche gauche avec ">="
// print("changeNodeEquals2\n");
// print("creation branche gauche ( '>=' ) \n");
    $newTreeLB = array("CLASS" => "OPK",
        "VALUE" => ">=",
        "NODETYPE" => PHRASEA_OP_GEQT,
        "PNUM" => NULL,
        "DEPTH" => 0,
        "LB" => $oneBranch["LB"],
        "RB" => array("CLASS" => "SIMPLE",
            "VALUE" => $this->isoDate($oneBranch["RB"]["VALUE"], false),
            "NODETYPE" => PHRASEA_KEYLIST,
            "PNUM" => NULL,
            "DEPTH" => 0)
    );
// var_dump($newTreeLB);
// print("fin creation branche gauche ( '>=' ) \n");
    ## fin creation branche gauche ( ">=" )
    ## creation branche droite avec "<="
// print("creation branche droite avec '<=' \n");
    $newTreeRB = array("CLASS" => "OPK",
        "VALUE" => "<=",
        "NODETYPE" => PHRASEA_OP_LEQT,
        "PNUM" => NULL,
        "DEPTH" => 0,
        "LB" => $oneBranch["LB"],
        "RB" => array("CLASS" => "SIMPLE",
            "VALUE" => $this->isoDate($oneBranch["RB"]["VALUE"], true),
            "NODETYPE" => PHRASEA_KEYLIST,
            "PNUM" => NULL,
            "DEPTH" => 0)
    );
// var_dump($newTreeRB);
// print("fin creation branche droite avec '<=' \n");
    ## fin creation branche droite ( "<=" )

    $tree = array("CLASS" => "OPS",
        "VALUE" => "et",
        "NODETYPE" => PHRASEA_OP_AND,
        "PNUM" => NULL,
        "DEPTH" => 0,
        "LB" => $newTreeLB,
        "RB" => $newTreeRB);


    // et  on le retourne
// var_dump($tree);
    return $tree;
  }

  function setNumValue2(&$branch, $type)
  {
    if (strtoupper($type) == strtoupper("Date"))
    {
      $dateEnIso = $this->isoDate($branch["VALUE"]);
      $branch["VALUE"] = $dateEnIso;
    }
  }

  function isoDate($onedate, $max=false)
  {
    $v_y = "1900";
    $v_m = "01";
    $v_d = "01";

    $v_h = $v_minutes = $v_s = "00";
    if ($max)
    {
      $v_h = $v_minutes = $v_s = "99";
    }
    $tmp = $onedate;

    if (!is_array($tmp))
      $tmp = explode(" ", $tmp);

    switch (sizeof($tmp))
    {
      // on a une date complete séparé avec des espaces, slash ou tiret
      case 3 :
        if (strlen($tmp[0]) == 4)
        {
          $v_y = $tmp[0];
          $v_m = $tmp[1];
          $v_d = $tmp[2];
          // on a l'année en premier, on suppose alors que c'est de la forme YYYY MM DD
        }
        elseif (strlen($tmp[2]) == 4)
        {
          // on a l'année en dernier, on suppose alors que c'est de la forme  DD MM YYYY
          $v_y = $tmp[2];
          $v_m = $tmp[1];
          $v_d = $tmp[0];
        }
        else
        {
          // l'année est sur un 2 chiffre et pas 4
          // ca fou la zone

          $v_d = $tmp[0];
          $v_m = $tmp[1];
          if ($tmp[2] < 20)
            $v_y = "20" . $tmp[2];
          else
            $v_y = "19" . $tmp[2];
        }
        break;

      case 2 :
        //   On supposerait n'avoir que le mois et l'année
        if (strlen($tmp[0]) == 4)
        {
          $v_y = $tmp[0];
          $v_m = $tmp[1];
          // on a l'année en premier, on suppose alors que c'est de la forme YYYY MM DD
          if ($max)
            $v_d = "99";
          else
            $v_d = "00";
        }
        elseif (strlen($tmp[1]) == 4)
        {
          // on a l'année en premier, on suppose alors que c'est de la forme  DD MM YYYY
          $v_y = $tmp[1];
          $v_m = $tmp[0];
          if ($max)
            $v_d = "99";
          else
            $v_d = "00";
        }
        else
        {
          // on a l'anné sur 2 chiffres
          if ($tmp[1] < 20)
            $v_y = "20" . $tmp[1];
          else
            $v_y = "19" . $tmp[1];
          $v_m = $tmp[0];
          if ($max)
            $v_d = "99";
          else
            $v_d = "00";
        }
        break;


      // lé ca devient la zone pour savoir si on a que l'année ou si c'est une date sans espaces,slash ou tiret
      case 1 :
        switch (strlen($tmp[0]))
        {
          case 14 :
            // date iso YYYYMMDDHHMMSS
            $v_y = substr($tmp[0], 0, 4);
            $v_m = substr($tmp[0], 4, 2);
            $v_d = substr($tmp[0], 6, 2);
            $v_h = substr($tmp[0], 8, 2);
            $v_minutes = substr($tmp[0], 10, 2);
            $v_s = substr($tmp[0], 12, 2);
            break;
          case 8 :
            // date iso YYYYMMDD
            $v_y = substr($tmp[0], 0, 4);
            $v_m = substr($tmp[0], 4, 2);
            $v_d = substr($tmp[0], 6, 2);
            break;
          case 6 :
            // date iso YYYYMM
            $v_y = substr($tmp[0], 0, 4);
            $v_m = substr($tmp[0], 4, 2);
            if ($max)
              $v_d = "99";
            else
              $v_d = "00";
            break;
          case 4 :
            // date iso YYYY
            $v_y = $tmp[0];

            if ($max)
              $v_m = "99";
            else
              $v_m = "00";

            if ($max)
              $v_d = "99";
            else
              $v_d = "00";
            break;
          case 2 :
            // date iso YY
            if ($tmp[0] < 20)
              $v_y = "20" . $tmp[0];
            else
              $v_y = "19" . $tmp[0];

            if ($max)
              $v_m = "99";
            else
              $v_m = "00";

            if ($max)
              $v_d = "99";
            else
              $v_d = "00";
            break;
        }



        break;
    }

    return("" . $v_y . $v_m . $v_d . $v_h . $v_minutes . $v_s);
  }

  function distrib_in(&$tree, $depth=0)
  {
    $opdistrib = array(PHRASEA_OP_AND, PHRASEA_OP_OR, PHRASEA_OP_EXCEPT, PHRASEA_OP_NEAR, PHRASEA_OP_BEFORE, PHRASEA_OP_AFTER); // ces opérateurs sont 'distribuables' autour d'un 'IN'
    // printf("<hr><b>distrib_in depth=%s sur</b><br>", $depth);
    // var_dump($tree);

    if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
    {
      if ($tree["NODETYPE"] == PHRASEA_OP_IN || $tree["CLASS"] == "OPK")
      {
        if ($tree["LB"]["CLASS"] == "OPK")
        {
          // on a un truc du genre '(t1 = t2) dans t3'
          // ... on ne fait rien
        }
        if ($tree["LB"]["CLASS"] == "OPS" && in_array($tree["LB"]["NODETYPE"], $opdistrib))
        {
          // on a un truc du genre '(t1 op t2) {dans|=} t3', on distribue le dans é t1 et t2
          // ==> ((t1 dans t3) op (t2 dans t3))
          $m_v = $tree["VALUE"];
          $m_t = $tree["CLASS"];
          $m_o = $tree["NODETYPE"];
          $m_n = $tree["PNUM"];

          $tree["CLASS"] = $tree["LB"]["CLASS"];
          $tree["NODETYPE"] = $tree["LB"]["NODETYPE"];
          $tree["VALUE"] = $tree["LB"]["VALUE"];
          $tree["PNUM"] = $tree["LB"]["PNUM"];

          $tree["LB"]["CLASS"] = $m_t;
          $tree["LB"]["NODETYPE"] = $m_o;
          $tree["LB"]["VALUE"] = $m_v;
          $tree["LB"]["PNUM"] = $m_n;

          $tree["RB"] = array("CLASS" => $m_t,
              "NODETYPE" => $m_o,
              "VALUE" => $m_v,
              "PNUM" => $m_n,
              "LB" => $tree["LB"]["RB"],
              "RB" => $tree["RB"]);

          $tree["LB"]["RB"] = $tree["RB"]["RB"];
          // var_dump($tree);
          // return;
        }


        if ($tree["RB"]["CLASS"] == "OPS" && in_array($tree["RB"]["NODETYPE"], $opdistrib))
        {

          // on a un truc du genre 't1 {dans|=} (t2 op t3)', on distribue le dans é t2 et t3
          // ==> ((t1 dans t2) ou (t1 dans t3))
          $m_v = $tree["VALUE"];
          $m_t = $tree["CLASS"];
          $m_o = $tree["NODETYPE"];
          $m_n = $tree["PNUM"];

          $tree["CLASS"] = $tree["RB"]["CLASS"];
          $tree["NODETYPE"] = $tree["RB"]["NODETYPE"];
          $tree["VALUE"] = $tree["RB"]["VALUE"];
          $tree["PNUM"] = $tree["RB"]["PNUM"];

          $tree["RB"]["CLASS"] = $m_t;
          $tree["RB"]["NODETYPE"] = $m_o;
          $tree["RB"]["VALUE"] = $m_v;
          $tree["RB"]["PNUM"] = $m_n;

          $tree["LB"] = array("CLASS" => $m_t,
              "NODETYPE" => $m_o,
              "VALUE" => $m_v,
              "PNUM" => $m_n,
              "LB" => $tree["LB"],
              "RB" => $tree["RB"]["LB"]);

          $tree["RB"]["LB"] = $tree["LB"]["LB"];
        }
      }
      $this->distrib_in($tree["LB"], $depth + 1);
      $this->distrib_in($tree["RB"], $depth + 1);
    }
  }

  function makequery($tree)
  {
    $a = array($tree["NODETYPE"]);
    switch ($tree["CLASS"])
    {
      case "PHRASEA_KW_LAST":
        if ($tree["PNUM"] !== NULL)
          $a[] = $tree["PNUM"];
        break;
      case "PHRASEA_KW_ALL":
        break;
      case "SIMPLE":
      case "QSIMPLE":
        // pas de tid, c'est un terme normal
        if (is_array($tree["VALUE"]))
        {
          foreach ($tree["VALUE"] as $k => $v)
            $a[] = $v;
        }
        else
        {
          $a[] = $tree["VALUE"];
        }
        break;
      case "OPK":
        if ($tree["LB"] !== NULL)
          $a[] = $this->makequery($tree["LB"]);
        if ($tree["RB"] !== NULL)
          $a[] = $this->makequery($tree["RB"]);
        break;
      case "OPS":
        if ($tree["PNUM"] !== NULL)
          $a[] = intval($tree["PNUM"]);
        if ($tree["LB"] !== NULL)
          $a[] = $this->makequery($tree["LB"]);
        if ($tree["RB"] !== NULL)
          $a[] = $this->makequery($tree["RB"]);
        break;
    }

    return($a);
  }

  function maketree($depth, $inquote = false)
  {
//    printf("<!-- PARSING $depth  -->\n\n");
    $tree = null;
    while ($t = $this->nexttoken($inquote))
    {
      if ($this->debug)
        printf("got token %s of class %s\n", $t["VALUE"], $t["CLASS"]);
      switch ($t["CLASS"])
      {
        case "TOK_RP":
          if ($inquote)
          {
            // quand on est entre guillements les tokens perdent leur signification
            $tree = $this->addtotree($tree, $t, $depth, $inquote);
            if (!$tree)

              return(null);
          }
          else
          {
            if ($depth <= 0)  // ')' : retour de récursivité
            {
              if ($this->errmsg != "")
                $this->errmsg .= sprintf("\\n");
              $this->errmsg .= _('qparser:: erreur : trop de parentheses fermantes');

              return(null);
            }

            return($tree);
          }
          break;
        case "TOK_LP":
          if ($inquote)
          {
            // quand on est entre guillements les tokens perdent leur signification
            $tree = $this->addtotree($tree, $t, $depth, $inquote);
            if (!$tree)

              return(null);
          }
          else  // '(' : appel récursif
          {
            if (!$tree)
              $tree = $this->maketree($depth + 1);
            else
            {
              if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null)
              {
                $tree["RB"] = $this->maketree($depth + 1);
                if (!$tree["RB"])
                  $tree = null;
              }
              else
              {
                // ici on applique l'opérateur par défaut
                $tree = array("CLASS" => "OPS",
                    "VALUE" => $this->defaultop["VALUE"],
                    "NODETYPE" => $this->defaultop["NODETYPE"],
                    "PNUM" => $this->defaultop["PNUM"],
                    "DEPTH" => $depth,
                    "LB" => $tree,
                    "RB" => $this->maketree($depth + 1));
              }
            }
            if (!$tree)

              return(null);
          }
          break;
        case "TOK_VOID":
          // ce token est entre guillemets : on le saute
          break;
        case "TOK_QUOTE":
          // une expr entre guillemets est 'comme entre parenthéses',
          //  sinon "a b" OU "x y" -> (((a B0 b) OU x) B0 y) au lieu de
          //        "a b" OU "x y" -> ((a B0 b) OU (x B0 y))
          if ($inquote)
          {
            if ($this->debug)
            {
              print("CLOSING QUOTE!\n");
            }
            // fermeture des guillemets -> retour de récursivité
            if ($depth <= 0)  // ')' : retour de récursivité
            {
              print("\nguillemets fermants en trop<br>");

              return(null);
            }

            return($tree);
          }
          else
          {
            if ($this->debug)
            {
              print("OPENING QUOTE!<br>");
            }
            // ouverture des guillemets -> récursivité
            if (!$tree)
              $tree = $this->maketree($depth + 1, true);
            else
            {
              if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null)
              {
                $tree["RB"] = $this->maketree($depth + 1, true);
                if (!$tree["RB"])
                  $tree = null;
              }
              else
              {
                // ici on applique l'opérateur par défaut
                $tree = array("CLASS" => "OPS",
                    "VALUE" => $this->defaultop["VALUE"],
                    "NODETYPE" => $this->defaultop["NODETYPE"],
                    "PNUM" => $this->defaultop["PNUM"],
                    "DEPTH" => $depth,
                    "LB" => $tree,
                    "RB" => $this->maketree($depth + 1, true));
              }
            }
            if (!$tree)

              return(null);
          }
          break;
        default:
          $tree = $this->addtotree($tree, $t, $depth, $inquote);
          if ($this->debug)
          {
            print("---- après addtotree ----\n");
            var_dump($tree);
            print("-------------------------\n");
          }
          if (!$tree)

            return(null);
          break;
      }
    }
    if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null)
    {
      if ($this->errmsg != "")
        $this->errmsg .= sprintf("\\n");
      $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, une valeur est attendu apres %s'), $tree["VALUE"]);
      $tree = $tree["LB"];
    }

    return($tree);
  }

  function addtotree($tree, $t, $depth, $inquote)
  {
    if ($this->debug)
    {
      printf("addtotree({tree}, \$t[CLASS]='%s', \$t[VALUE]='%s', \$depth=%d, inquote=%s)\n", $t["CLASS"], $t["VALUE"], $depth, $inquote ? "true" : "false");
      print("---- avant addtotree ----\n");
      var_dump($tree);
      print("-------------------------\n");
    }

    if (!$t)

      return($tree);
    switch ($t["CLASS"])
    {
      case "TOK_CONTEXT":
//        if($this->debug)
//        {
//          printf("addtotree({tree}, \$t='%s', \$depth=%d, inquote=%s)\n", $t["VALUE"], $depth, $inquote?"true":"false");
//          var_dump($tree);
//        }
        if ($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE")
        {
          // un [xxx] suit un terme : il introduit un contexte
          $tree["CONTEXT"] = $t["VALUE"];
        }
        elseif ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK")
        {
          if (!isset($tree["RB"]) || !$tree["RB"])
          {
            // un [xxx] peut suivre un opérateur, c'est un paramétre normalement numérique
            $tree["PNUM"] = $t["VALUE"];
          }
          else
          {
            // [xxx] suit un terme déjé en branche droite ? (ex: a ou b[k])
            if ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE")
              $tree["RB"]["CONTEXT"] = $t["VALUE"];
            else
            {
              if ($this->errmsg != "")
                $this->errmsg .= "\\n";
              $this->errmsg .= sprintf("le contexte [%s] ne peut suivre qu'un terme ou un opérateur<br/>", $t["VALUE"]);

              return(null);
            }
          }
        }
        else
        {
          if ($this->errmsg != "")
            $this->errmsg .= "\\n";
          $this->errmsg .= sprintf("le contexte [%s] ne peut suivre qu'un terme ou un opérateur<br/>", $t["VALUE"]);

          return(null);
        }

        return($tree);
        break;
      case "TOK_CMP":
        // < > <= >= <> = : sont des opérateurs de comparaison
        if (!$tree)
        {
          // printf("\nUne question ne peut commencer par '" . $t["VALUE"] . "'<br>");
          if ($this->errmsg != "")
            $this->errmsg .= "\\n";
          $this->errmsg .= sprintf(_('qparser::erreur : une question ne peut commencer par %s'), $t["VALUE"]);

          return(null);
        }
        if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null)
        {
          // printf("'" . $t["VALUE"] . "' ne peut suivre un opérateur<br>");
          if ($this->errmsg != "")
            $this->errmsg .= "\\n";
          $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, ne peut suivre un operateur :  %s'), $t["VALUE"]);

          return(null);
        }

        return(array("CLASS" => "OPK", "VALUE" => $t["VALUE"], "NODETYPE" => $this->opk[$t["VALUE"]]["NODETYPE"], "PNUM" => null, "DEPTH" => $depth, "LB" => $tree, "RB" => null));
        break;
      case "TOK_WORD":
        if ($t["CLASS"] == "TOK_WORD" && isset($this->ops[$t["VALUE"]]) && !$inquote)
        {
          // ce mot est un opérateur phrasea
          if (!$tree)
          {
            // printf("\n581 : Une question ne peut commencer par un opérateur<br>");
            if ($this->errmsg != "")
              $this->errmsg .= "\\n";
            $this->errmsg .= sprintf(_('qparser::erreur : une question ne peut commencer par %s'), $t["VALUE"]);

            return(null);
          }
          if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null)
          {

            // printf("\n586 : Un opérateur ne peut suivre un opérateur<br>");
            if ($this->errmsg != "")
              $this->errmsg .= "\\n";
            $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, %s ne peut suivre un operateur'), $t["VALUE"]);

            return(null);
          }
          $pnum = null;
          if ($this->ops[$t["VALUE"]]["CANNUM"])
          {
            // cet opérateur peut étre suivi d'un nombre ('near', 'before', 'after')
            if ($tn = $this->nexttoken())
            {
              if ($tn["CLASS"] == "TOK_WORD" && is_numeric($tn["VALUE"]))
                $pnum = (int) $tn["VALUE"];
              else
                $this->ungettoken($tn["VALUE"]);
            }
          }

          return(array("CLASS" => "OPS", "VALUE" => $t["VALUE"], "NODETYPE" => $this->ops[$t["VALUE"]]["NODETYPE"], "PNUM" => $pnum, "DEPTH" => $depth, "LB" => $tree, "RB" => null));
        }
        else
        {
          // ce mot n'est pas un opérateur
          $pnum = null;
          $nodetype = PHRASEA_KEYLIST;
          if ($t["CLASS"] == "TOK_WORD" && isset($this->spw[$t["VALUE"]]) && !$inquote)
          {
            // mais c'est un mot 'spécial' de phrasea ('last', 'all')
            $type = $this->spw[$t["VALUE"]]["CLASS"];
            $nodetype = $this->spw[$t["VALUE"]]["NODETYPE"];
            if ($this->spw[$t["VALUE"]]["CANNUM"])
            {
              // 'last' peut étre suivi d'un nombre
              if ($tn = $this->nexttoken())
              {
                if ($tn["CLASS"] == "TOK_WORD" && is_numeric($tn["VALUE"]))
                  $pnum = (int) $tn["VALUE"];
                else
                  $this->ungettoken($tn["VALUE"]);
              }
            }
          }
          else
          {
            //printf("sdfsdfsdfsd<br>");
            $type = $inquote ? "QSIMPLE" : "SIMPLE";
          }

          return($this->addsimple($t, $type, $nodetype, $pnum, $tree, $depth));
        }
        break;
    }
  }

  function addsimple($t, $type, $nodetype, $pnum, $tree, $depth)
  {
    $nok = 0;
//    $registry = registry::get_instance();
    $w = $t["VALUE"];
    if ($w != "?" && $w != "*")  // on laisse passer les 'isolés' pour les traiter plus tard comme des mots vides
    {
      for ($i = 0; $i < strlen($w); $i++)
      {
        $c = substr($w, $i, 1);
        if ($c == "?" || $c == "*")
        {
          if ($nok < GV_min_letters_truncation )
          {
            if ($this->errmsg != "")
              $this->errmsg .= sprintf("\\n");
            $this->errmsg .= _('qparser:: Formulation incorrecte, necessite plus de caractere : ') . "<br>" . GV_min_letters_truncation;

            return(null);
          }
          // $nok = 0;
        }
        else
          $nok++;
      }
    }
    if (!$tree)
    {
      return(array("CLASS" => $type, "NODETYPE" => $nodetype, "VALUE" => array($t["VALUE"]), "PNUM" => $pnum, "DEPTH" => $depth));
    }
    switch ($tree["CLASS"])
    {
      case "SIMPLE":
      case "QSIMPLE":
        if ($type == "SIMPLE" || $type == "QSIMPLE")
          $tree["VALUE"][] = $t["VALUE"];
        else
        {
          $tree = array("CLASS" => "OPS",
              "VALUE" => "et",
              "NODETYPE" => PHRASEA_OP_AND,
              "PNUM" => null,
              "DEPTH" => $depth,
              "LB" => $tree,
              "RB" => array("CLASS" => $type,
                  "NODETYPE" => $nodetype,
                  "VALUE" => array($t["VALUE"]),
                  "PNUM" => $pnum,
                  "DEPTH" => $depth));
        }

        return($tree);
      case "OPS":
      case "OPK":
        if ($tree["RB"] == null)
        {
          $tree["RB"] = array("CLASS" => $type, "NODETYPE" => $nodetype, "VALUE" => array($t["VALUE"]), "PNUM" => $pnum, "DEPTH" => $depth);

          return($tree);
        }
        else
        {
          if (($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE") && $tree["RB"]["DEPTH"] == $depth)
          {
            $tree["RB"]["VALUE"][] = $t["VALUE"];

            return($tree);
          }
          if (($tree["RB"]["CLASS"] == "PHRASEA_KW_LAST" || $tree["RB"]["CLASS"] == "PHRASEA_KW_ALL") && $tree["RB"]["DEPTH"] == $depth)
          {
            $tree["RB"] = array("CLASS" => "OPS",
                "VALUE" => "et",
                "NODETYPE" => PHRASEA_OP_AND,
                "PNUM" => null,
                "DEPTH" => $depth,
                "LB" => $tree["RB"],
                "RB" => array("CLASS" => $type,
                    "NODETYPE" => $nodetype,
                    "VALUE" => array($t["VALUE"]),
                    "PNUM" => $pnum,
                    "DEPTH" => $depth));

            return($tree);
          }

          return(array("CLASS" => "OPS",
      "VALUE" => $this->defaultop["VALUE"],
      "NODETYPE" => $this->defaultop["NODETYPE"],
      "PNUM" => $this->defaultop["PNUM"],
      "DEPTH" => $depth,
      "LB" => $tree,
      "RB" => array("CLASS" => $type, "NODETYPE" => $nodetype, "VALUE" => array($t["VALUE"]), "PNUM" => $pnum, "DEPTH" => $depth)
          ));
        }
      case "PHRASEA_KW_LAST":
      case "PHRASEA_KW_ALL":
        return(array("CLASS" => "OPS",
    "VALUE" => "et",
    "NODETYPE" => PHRASEA_OP_AND,
    "PNUM" => null,
    "DEPTH" => $depth,
    "LB" => $tree,
    "RB" => array("CLASS" => $type,
        "NODETYPE" => $nodetype,
        "VALUE" => array($t["VALUE"]),
        "PNUM" => $pnum,
        "DEPTH" => $depth)));
    }
  }

  function ungettoken($s)
  {
    $this->phq = $s . " " . $this->phq;
  }

  function nexttoken($inquote=false)
  {
    if ($this->phq == "")

      return(null);
    switch ($c = substr($this->phq, 0, 1))
    {
      case "<":
      case ">":
        if ($inquote)
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

          return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
        }
        $c2 = $c . substr($this->phq, 1, 1);
        if ($c2 == "<=" || $c2 == ">=" || $c2 == "<>")
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 2, 99999, 'UTF-8'), 'UTF-8');
          $c = $c2;
        }
        else
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');
        }

        return(array("CLASS" => "TOK_CMP", "VALUE" => $c));
        break;
      case "=":
        if ($inquote)
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

          return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
        }
        $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

        return(array("CLASS" => "TOK_CMP", "VALUE" => "="));
        break;
      case ":":
        if ($inquote)
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

          return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
        }
        $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

        return(array("CLASS" => "TOK_CMP", "VALUE" => ":"));
        break;
      case "(":
        if ($inquote)
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

          return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
        }
        $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

        return(array("CLASS" => "TOK_LP", "VALUE" => "("));
        break;
      case ")":
        if ($inquote)
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

          return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
        }
        $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

        return(array("CLASS" => "TOK_RP", "VALUE" => ")"));
        break;
      case "[":
        //  if($inquote)
        //  {
        //    $this->phq = ltrim(substr($this->phq, 1));
        //    return(array("CLASS"=>"TOK_VOID", "VALUE"=>$c));
        //  }
        // un '[' introduit un contexte qu'on lit jusqu'au ']'
        $closeb = mb_strpos($this->phq, "]", 1, 'UTF-8');
        if ($closeb !== false)
        {
          $context = $this->mb_trim(mb_substr($this->phq, 1, $closeb - 1, 'UTF-8'), 'UTF-8');
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, $closeb + 1, 99999, 'UTF-8'), 'UTF-8');
        }
        else
        {
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');
          $this->phq = "";
        }
        $context = $this->unicode->remove_indexer_chars($context);

        return(array("CLASS" => "TOK_CONTEXT", "VALUE" => $context));
        break;
      /*
        case "]":
        //  if($inquote)
        //  {
        //    $this->phq = ltrim(substr($this->phq, 1));
        //    return(array("CLASS"=>"TOK_VOID", "VALUE"=>$c));
        //  }
        $this->phq = ltrim(substr($this->phq, 1));

        return(array("CLASS"=>"TOK_RB", "VALUE"=>"]"));
        break;
       */
      case "\"":
        $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

        return(array("CLASS" => "TOK_QUOTE", "VALUE" => "\""));
        break;
      default:
        $l = mb_strlen($this->phq, 'UTF-8');
        $t = "";
        $c_utf8 = "";
        for ($i = 0; $i < $l; $i++)
        {
          if (!$this->unicode->has_indexer_bad_char(($c_utf8 = mb_substr($this->phq, $i, 1, 'UTF-8'))))
          {
            //  $c = mb_strtolower($c);
            //  $t .= isset($this->noaccent[$c]) ? $this->noaccent[$c] : $c;
            $t .= $this->unicode->remove_diacritics(mb_strtolower($c_utf8));
          }
          else
            break;
        }
//        if ($c_utf8 == "(" || $c_utf8 == ")" || $c_utf8 == "[" || $c_utf8 == "]" || $c_utf8 == "=" || $c_utf8 == ":" || $c_utf8 == "<" || $c_utf8 == ">" || $c_utf8 == "\"")
        if (in_array($c_utf8, array("(", ")", "[", "]", "=", ":", "<", ">", "\"")))
        {
          // ces caractéres sont des délimiteurs avec un sens, il faut les garder
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, $i, 99999, 'UTF-8'), 'UTF-8');
        }
        else
        {
          // le délimiteur était une simple ponctuation, on le saute
          $this->phq = $this->mb_ltrim(mb_substr($this->phq, $i + 1, 99999, 'UTF-8'), 'UTF-8');
        }
        if ($t != "")

          return(array("CLASS" => "TOK_WORD", "VALUE" => $t));
        else

          return(array("CLASS" => "TOK_VOID", "VALUE" => $t));
        break;
    }
  }

}

class unicode
{

  protected $map = array(
      //------  U+0000..U+007F : Basic Latin
      /* U+0041 */ "\x41" => "\x61",
      /* U+0042 */ "\x42" => "\x62",
      /* U+0043 */ "\x43" => "\x63",
      /* U+0044 */ "\x44" => "\x64",
      /* U+0045 */ "\x45" => "\x65",
      /* U+0046 */ "\x46" => "\x66",
      /* U+0047 */ "\x47" => "\x67",
      /* U+0048 */ "\x48" => "\x68",
      /* U+0049 */ "\x49" => "\x69",
      /* U+004A */ "\x4A" => "\x6A",
      /* U+004B */ "\x4B" => "\x6B",
      /* U+004C */ "\x4C" => "\x6C",
      /* U+004D */ "\x4D" => "\x6D",
      /* U+004E */ "\x4E" => "\x6E",
      /* U+004F */ "\x4F" => "\x6F",
      /* U+0050 */ "\x50" => "\x70",
      /* U+0051 */ "\x51" => "\x71",
      /* U+0052 */ "\x52" => "\x72",
      /* U+0053 */ "\x53" => "\x73",
      /* U+0054 */ "\x54" => "\x74",
      /* U+0055 */ "\x55" => "\x75",
      /* U+0056 */ "\x56" => "\x76",
      /* U+0057 */ "\x57" => "\x77",
      /* U+0058 */ "\x58" => "\x78",
      /* U+0059 */ "\x59" => "\x79",
      /* U+005A */ "\x5A" => "\x7A",
      //------  U+0080..U+00FF : Latin-1 Supplement
      /* U+00C0 */ "\xC3\x80" => "\x61",
      /* U+00C1 */ "\xC3\x81" => "\x61",
      /* U+00C2 */ "\xC3\x82" => "\x61",
      /* U+00C3 */ "\xC3\x83" => "\x61",
      /* U+00C4 */ "\xC3\x84" => "\x61",
      /* U+00C5 */ "\xC3\x85" => "\x61",
      /* U+00C6 */ "\xC3\x86" => "\xC3\xA6",
      /* U+00C7 */ "\xC3\x87" => "\x63",
      /* U+00C8 */ "\xC3\x88" => "\x65",
      /* U+00C9 */ "\xC3\x89" => "\x65",
      /* U+00CA */ "\xC3\x8A" => "\x65",
      /* U+00CB */ "\xC3\x8B" => "\x65",
      /* U+00CC */ "\xC3\x8C" => "\x69",
      /* U+00CD */ "\xC3\x8D" => "\x69",
      /* U+00CE */ "\xC3\x8E" => "\x69",
      /* U+00CF */ "\xC3\x8F" => "\x69",
      /* U+00D0 */ "\xC3\x90" => "\xC3\xB0",
      /* U+00D1 */ "\xC3\x91" => "\x6E",
      /* U+00D2 */ "\xC3\x92" => "\x6F",
      /* U+00D3 */ "\xC3\x93" => "\x6F",
      /* U+00D4 */ "\xC3\x94" => "\x6F",
      /* U+00D5 */ "\xC3\x95" => "\x6F",
      /* U+00D6 */ "\xC3\x96" => "\x6F",
      /* U+00D8 */ "\xC3\x98" => "\x6F",
      /* U+00D9 */ "\xC3\x99" => "\x75",
      /* U+00DA */ "\xC3\x9A" => "\x75",
      /* U+00DB */ "\xC3\x9B" => "\x75",
      /* U+00DC */ "\xC3\x9C" => "\x75",
      /* U+00DD */ "\xC3\x9D" => "\x79",
      /* U+00DE */ "\xC3\x9E" => "\xC3\xBE",
      /* U+00E0 */ "\xC3\xA0" => "\x61",
      /* U+00E1 */ "\xC3\xA1" => "\x61",
      /* U+00E2 */ "\xC3\xA2" => "\x61",
      /* U+00E3 */ "\xC3\xA3" => "\x61",
      /* U+00E4 */ "\xC3\xA4" => "\x61",
      /* U+00E5 */ "\xC3\xA5" => "\x61",
      /* U+00E7 */ "\xC3\xA7" => "\x63",
      /* U+00E8 */ "\xC3\xA8" => "\x65",
      /* U+00E9 */ "\xC3\xA9" => "\x65",
      /* U+00EA */ "\xC3\xAA" => "\x65",
      /* U+00EB */ "\xC3\xAB" => "\x65",
      /* U+00EC */ "\xC3\xAC" => "\x69",
      /* U+00ED */ "\xC3\xAD" => "\x69",
      /* U+00EE */ "\xC3\xAE" => "\x69",
      /* U+00EF */ "\xC3\xAF" => "\x69",
      /* U+00F1 */ "\xC3\xB1" => "\x6E",
      /* U+00F2 */ "\xC3\xB2" => "\x6F",
      /* U+00F3 */ "\xC3\xB3" => "\x6F",
      /* U+00F4 */ "\xC3\xB4" => "\x6F",
      /* U+00F5 */ "\xC3\xB5" => "\x6F",
      /* U+00F6 */ "\xC3\xB6" => "\x6F",
      /* U+00F8 */ "\xC3\xB8" => "\x6F",
      /* U+00F9 */ "\xC3\xB9" => "\x75",
      /* U+00FA */ "\xC3\xBA" => "\x75",
      /* U+00FB */ "\xC3\xBB" => "\x75",
      /* U+00FC */ "\xC3\xBC" => "\x75",
      /* U+00FD */ "\xC3\xBD" => "\x79",
      /* U+00FF */ "\xC3\xBF" => "\x79",
      //------  U+0100..U+017F : Latin Extended-A
      /* U+0100 */ "\xC4\x80" => "\x61",
      /* U+0101 */ "\xC4\x81" => "\x61",
      /* U+0102 */ "\xC4\x82" => "\x61",
      /* U+0103 */ "\xC4\x83" => "\x61",
      /* U+0104 */ "\xC4\x84" => "\x61",
      /* U+0105 */ "\xC4\x85" => "\x61",
      /* U+0106 */ "\xC4\x86" => "\x63",
      /* U+0107 */ "\xC4\x87" => "\x63",
      /* U+0108 */ "\xC4\x88" => "\x63",
      /* U+0109 */ "\xC4\x89" => "\x63",
      /* U+010A */ "\xC4\x8A" => "\x63",
      /* U+010B */ "\xC4\x8B" => "\x63",
      /* U+010C */ "\xC4\x8C" => "\x63",
      /* U+010D */ "\xC4\x8D" => "\x63",
      /* U+010E */ "\xC4\x8E" => "\x64",
      /* U+010F */ "\xC4\x8F" => "\x64",
      /* U+0110 */ "\xC4\x90" => "\x64",
      /* U+0111 */ "\xC4\x91" => "\x64",
      /* U+0112 */ "\xC4\x92" => "\x65",
      /* U+0113 */ "\xC4\x93" => "\x65",
      /* U+0114 */ "\xC4\x94" => "\x65",
      /* U+0115 */ "\xC4\x95" => "\x65",
      /* U+0116 */ "\xC4\x96" => "\x65",
      /* U+0117 */ "\xC4\x97" => "\x65",
      /* U+0118 */ "\xC4\x98" => "\x65",
      /* U+0119 */ "\xC4\x99" => "\x65",
      /* U+011A */ "\xC4\x9A" => "\x65",
      /* U+011B */ "\xC4\x9B" => "\x65",
      /* U+011C */ "\xC4\x9C" => "\x67",
      /* U+011D */ "\xC4\x9D" => "\x67",
      /* U+011E */ "\xC4\x9E" => "\x67",
      /* U+011F */ "\xC4\x9F" => "\x67",
      /* U+0120 */ "\xC4\xA0" => "\x67",
      /* U+0121 */ "\xC4\xA1" => "\x67",
      /* U+0122 */ "\xC4\xA2" => "\x67",
      /* U+0123 */ "\xC4\xA3" => "\x67",
      /* U+0124 */ "\xC4\xA4" => "\x68",
      /* U+0125 */ "\xC4\xA5" => "\x68",
      /* U+0126 */ "\xC4\xA6" => "\x68",
      /* U+0127 */ "\xC4\xA7" => "\x68",
      /* U+0128 */ "\xC4\xA8" => "\x69",
      /* U+0129 */ "\xC4\xA9" => "\x69",
      /* U+012A */ "\xC4\xAA" => "\x69",
      /* U+012B */ "\xC4\xAB" => "\x69",
      /* U+012C */ "\xC4\xAC" => "\x69",
      /* U+012D */ "\xC4\xAD" => "\x69",
      /* U+012E */ "\xC4\xAE" => "\x69",
      /* U+012F */ "\xC4\xAF" => "\x69",
      /* U+0130 */ "\xC4\xB0" => "\x69",
      /* U+0132 */ "\xC4\xB2" => "\xC4\xB3",
      /* U+0134 */ "\xC4\xB4" => "\x6A",
      /* U+0135 */ "\xC4\xB5" => "\x6A",
      /* U+0136 */ "\xC4\xB6" => "\x6B",
      /* U+0137 */ "\xC4\xB7" => "\x6B",
      /* U+0139 */ "\xC4\xB9" => "\x6C",
      /* U+013A */ "\xC4\xBA" => "\x6C",
      /* U+013B */ "\xC4\xBB" => "\x6C",
      /* U+013C */ "\xC4\xBC" => "\x6C",
      /* U+013D */ "\xC4\xBD" => "\x6C",
      /* U+013E */ "\xC4\xBE" => "\x6C",
      /* U+013F */ "\xC4\xBF" => "\x6C",
      /* U+0140 */ "\xC5\x80" => "\x6C",
      /* U+0141 */ "\xC5\x81" => "\x6C",
      /* U+0142 */ "\xC5\x82" => "\x6C",
      /* U+0143 */ "\xC5\x83" => "\x6E",
      /* U+0144 */ "\xC5\x84" => "\x6E",
      /* U+0145 */ "\xC5\x85" => "\x6E",
      /* U+0146 */ "\xC5\x86" => "\x6E",
      /* U+0147 */ "\xC5\x87" => "\x6E",
      /* U+0148 */ "\xC5\x88" => "\x6E",
      /* U+014A */ "\xC5\x8A" => "\xC5\x8B",
      /* U+014C */ "\xC5\x8C" => "\x6F",
      /* U+014D */ "\xC5\x8D" => "\x6F",
      /* U+014E */ "\xC5\x8E" => "\x6F",
      /* U+014F */ "\xC5\x8F" => "\x6F",
      /* U+0150 */ "\xC5\x90" => "\x6F",
      /* U+0151 */ "\xC5\x91" => "\x6F",
      /* U+0152 */ "\xC5\x92" => "\xC5\x93",
      /* U+0154 */ "\xC5\x94" => "\x72",
      /* U+0155 */ "\xC5\x95" => "\x72",
      /* U+0156 */ "\xC5\x96" => "\x72",
      /* U+0157 */ "\xC5\x97" => "\x72",
      /* U+0158 */ "\xC5\x98" => "\x72",
      /* U+0159 */ "\xC5\x99" => "\x72",
      /* U+015A */ "\xC5\x9A" => "\x73",
      /* U+015B */ "\xC5\x9B" => "\x73",
      /* U+015C */ "\xC5\x9C" => "\x73",
      /* U+015D */ "\xC5\x9D" => "\x73",
      /* U+015E */ "\xC5\x9E" => "\x73",
      /* U+015F */ "\xC5\x9F" => "\x73",
      /* U+0160 */ "\xC5\xA0" => "\x73",
      /* U+0161 */ "\xC5\xA1" => "\x73",
      /* U+0162 */ "\xC5\xA2" => "\x74",
      /* U+0163 */ "\xC5\xA3" => "\x74",
      /* U+0164 */ "\xC5\xA4" => "\x74",
      /* U+0165 */ "\xC5\xA5" => "\x74",
      /* U+0166 */ "\xC5\xA6" => "\x74",
      /* U+0167 */ "\xC5\xA7" => "\x74",
      /* U+0168 */ "\xC5\xA8" => "\x75",
      /* U+0169 */ "\xC5\xA9" => "\x75",
      /* U+016A */ "\xC5\xAA" => "\x75",
      /* U+016B */ "\xC5\xAB" => "\x75",
      /* U+016C */ "\xC5\xAC" => "\x75",
      /* U+016D */ "\xC5\xAD" => "\x75",
      /* U+016E */ "\xC5\xAE" => "\x75",
      /* U+016F */ "\xC5\xAF" => "\x75",
      /* U+0170 */ "\xC5\xB0" => "\x75",
      /* U+0171 */ "\xC5\xB1" => "\x75",
      /* U+0172 */ "\xC5\xB2" => "\x75",
      /* U+0173 */ "\xC5\xB3" => "\x75",
      /* U+0174 */ "\xC5\xB4" => "\x77",
      /* U+0175 */ "\xC5\xB5" => "\x77",
      /* U+0176 */ "\xC5\xB6" => "\x79",
      /* U+0177 */ "\xC5\xB7" => "\x79",
      /* U+0178 */ "\xC5\xB8" => "\x79",
      /* U+0179 */ "\xC5\xB9" => "\x7A",
      /* U+017A */ "\xC5\xBA" => "\x7A",
      /* U+017B */ "\xC5\xBB" => "\x7A",
      /* U+017C */ "\xC5\xBC" => "\x7A",
      /* U+017D */ "\xC5\xBD" => "\x7A",
      /* U+017E */ "\xC5\xBE" => "\x7A",
      //------  U+0180..U+024F : Latin Extended-B
      /* U+0180 */ "\xC6\x80" => "\x62",
      /* U+0181 */ "\xC6\x81" => "\x62",
      /* U+0182 */ "\xC6\x82" => "\x62",
      /* U+0183 */ "\xC6\x83" => "\x62",
      /* U+0184 */ "\xC6\x84" => "\xC6\x85",
      /* U+0186 */ "\xC6\x86" => "\xC9\x94",
      /* U+0187 */ "\xC6\x87" => "\x63",
      /* U+0188 */ "\xC6\x88" => "\x63",
      /* U+0189 */ "\xC6\x89" => "\x64",
      /* U+018A */ "\xC6\x8A" => "\x64",
      /* U+018B */ "\xC6\x8B" => "\x64",
      /* U+018C */ "\xC6\x8C" => "\x64",
      /* U+018E */ "\xC6\x8E" => "\xC7\x9D",
      /* U+018F */ "\xC6\x8F" => "\xC9\x99",
      /* U+0190 */ "\xC6\x90" => "\xC9\x9B",
      /* U+0191 */ "\xC6\x91" => "\x66",
      /* U+0192 */ "\xC6\x92" => "\x66",
      /* U+0193 */ "\xC6\x93" => "\x67",
      /* U+0194 */ "\xC6\x94" => "\xC9\xA3",
      /* U+0196 */ "\xC6\x96" => "\xC9\xA9",
      /* U+0197 */ "\xC6\x97" => "\x69",
      /* U+0198 */ "\xC6\x98" => "\x6B",
      /* U+0199 */ "\xC6\x99" => "\x6B",
      /* U+019A */ "\xC6\x9A" => "\x6C",
      /* U+019C */ "\xC6\x9C" => "\xC9\xAF",
      /* U+019D */ "\xC6\x9D" => "\x6E",
      /* U+019E */ "\xC6\x9E" => "\x6E",
      /* U+019F */ "\xC6\x9F" => "\xC9\xB5",
      /* U+01A0 */ "\xC6\xA0" => "\x6F",
      /* U+01A1 */ "\xC6\xA1" => "\x6F",
      /* U+01A2 */ "\xC6\xA2" => "\xC6\xA3",
      /* U+01A4 */ "\xC6\xA4" => "\x70",
      /* U+01A5 */ "\xC6\xA5" => "\x70",
      /* U+01A6 */ "\xC6\xA6" => "\xCA\x80",
      /* U+01A7 */ "\xC6\xA7" => "\xC6\xA8",
      /* U+01A9 */ "\xC6\xA9" => "\xCA\x83",
      /* U+01AB */ "\xC6\xAB" => "\x74",
      /* U+01AC */ "\xC6\xAC" => "\x74",
      /* U+01AD */ "\xC6\xAD" => "\x74",
      /* U+01AE */ "\xC6\xAE" => "\x74",
      /* U+01AF */ "\xC6\xAF" => "\x75",
      /* U+01B0 */ "\xC6\xB0" => "\x75",
      /* U+01B1 */ "\xC6\xB1" => "\xCA\x8A",
      /* U+01B2 */ "\xC6\xB2" => "\x76",
      /* U+01B3 */ "\xC6\xB3" => "\x79",
      /* U+01B4 */ "\xC6\xB4" => "\x79",
      /* U+01B5 */ "\xC6\xB5" => "\x7A",
      /* U+01B6 */ "\xC6\xB6" => "\x7A",
      /* U+01B7 */ "\xC6\xB7" => "\xCA\x92",
      /* U+01B8 */ "\xC6\xB8" => "\xC6\xB9",
      /* U+01BA */ "\xC6\xBA" => "\xCA\x92",
      /* U+01BC */ "\xC6\xBC" => "\xC6\xBD",
      /* U+01C4 */ "\xC7\x84" => "\xC7\x86",
      /* U+01C5 */ "\xC7\x85" => "\xC7\x86",
      /* U+01C7 */ "\xC7\x87" => "\xC7\x89",
      /* U+01C8 */ "\xC7\x88" => "\xC7\x89",
      /* U+01CA */ "\xC7\x8A" => "\xC7\x8C",
      /* U+01CB */ "\xC7\x8B" => "\xC7\x8C",
      /* U+01CD */ "\xC7\x8D" => "\x61",
      /* U+01CE */ "\xC7\x8E" => "\x61",
      /* U+01CF */ "\xC7\x8F" => "\x69",
      /* U+01D0 */ "\xC7\x90" => "\x69",
      /* U+01D1 */ "\xC7\x91" => "\x6F",
      /* U+01D2 */ "\xC7\x92" => "\x6F",
      /* U+01D3 */ "\xC7\x93" => "\x75",
      /* U+01D4 */ "\xC7\x94" => "\x75",
      /* U+01D5 */ "\xC7\x95" => "\x75",
      /* U+01D6 */ "\xC7\x96" => "\x75",
      /* U+01D7 */ "\xC7\x97" => "\x75",
      /* U+01D8 */ "\xC7\x98" => "\x75",
      /* U+01D9 */ "\xC7\x99" => "\x75",
      /* U+01DA */ "\xC7\x9A" => "\x75",
      /* U+01DB */ "\xC7\x9B" => "\x75",
      /* U+01DC */ "\xC7\x9C" => "\x75",
      /* U+01DE */ "\xC7\x9E" => "\x61",
      /* U+01DF */ "\xC7\x9F" => "\x61",
      /* U+01E0 */ "\xC7\xA0" => "\x61",
      /* U+01E1 */ "\xC7\xA1" => "\x61",
      /* U+01E2 */ "\xC7\xA2" => "\xC3\xA6",
      /* U+01E3 */ "\xC7\xA3" => "\xC3\xA6",
      /* U+01E4 */ "\xC7\xA4" => "\x67",
      /* U+01E5 */ "\xC7\xA5" => "\x67",
      /* U+01E6 */ "\xC7\xA6" => "\x67",
      /* U+01E7 */ "\xC7\xA7" => "\x67",
      /* U+01E8 */ "\xC7\xA8" => "\x6B",
      /* U+01E9 */ "\xC7\xA9" => "\x6B",
      /* U+01EA */ "\xC7\xAA" => "\x6F",
      /* U+01EB */ "\xC7\xAB" => "\x6F",
      /* U+01EC */ "\xC7\xAC" => "\x6F",
      /* U+01ED */ "\xC7\xAD" => "\x6F",
      /* U+01EE */ "\xC7\xAE" => "\xCA\x92",
      /* U+01EF */ "\xC7\xAF" => "\xCA\x92",
      /* U+01F0 */ "\xC7\xB0" => "\x6A",
      /* U+01F1 */ "\xC7\xB1" => "\xC7\xB3",
      /* U+01F2 */ "\xC7\xB2" => "\xC7\xB3",
      /* U+01F4 */ "\xC7\xB4" => "\x67",
      /* U+01F5 */ "\xC7\xB5" => "\x67",
      /* U+01F6 */ "\xC7\xB6" => "\xC6\x95",
      /* U+01F7 */ "\xC7\xB7" => "\xC6\xBF",
      /* U+01F8 */ "\xC7\xB8" => "\x6E",
      /* U+01F9 */ "\xC7\xB9" => "\x6E",
      /* U+01FA */ "\xC7\xBA" => "\x61",
      /* U+01FB */ "\xC7\xBB" => "\x61",
      /* U+01FC */ "\xC7\xBC" => "\xC3\xA6",
      /* U+01FD */ "\xC7\xBD" => "\xC3\xA6",
      /* U+01FE */ "\xC7\xBE" => "\x6F",
      /* U+01FF */ "\xC7\xBF" => "\x6F",
      /* U+0200 */ "\xC8\x80" => "\x61",
      /* U+0201 */ "\xC8\x81" => "\x61",
      /* U+0202 */ "\xC8\x82" => "\x61",
      /* U+0203 */ "\xC8\x83" => "\x61",
      /* U+0204 */ "\xC8\x84" => "\x65",
      /* U+0205 */ "\xC8\x85" => "\x65",
      /* U+0206 */ "\xC8\x86" => "\x65",
      /* U+0207 */ "\xC8\x87" => "\x65",
      /* U+0208 */ "\xC8\x88" => "\x69",
      /* U+0209 */ "\xC8\x89" => "\x69",
      /* U+020A */ "\xC8\x8A" => "\x69",
      /* U+020B */ "\xC8\x8B" => "\x69",
      /* U+020C */ "\xC8\x8C" => "\x6F",
      /* U+020D */ "\xC8\x8D" => "\x6F",
      /* U+020E */ "\xC8\x8E" => "\x6F",
      /* U+020F */ "\xC8\x8F" => "\x6F",
      /* U+0210 */ "\xC8\x90" => "\x72",
      /* U+0211 */ "\xC8\x91" => "\x72",
      /* U+0212 */ "\xC8\x92" => "\x72",
      /* U+0213 */ "\xC8\x93" => "\x72",
      /* U+0214 */ "\xC8\x94" => "\x75",
      /* U+0215 */ "\xC8\x95" => "\x75",
      /* U+0216 */ "\xC8\x96" => "\x75",
      /* U+0217 */ "\xC8\x97" => "\x75",
      /* U+0218 */ "\xC8\x98" => "\x73",
      /* U+0219 */ "\xC8\x99" => "\x73",
      /* U+021A */ "\xC8\x9A" => "\x74",
      /* U+021B */ "\xC8\x9B" => "\x74",
      /* U+021C */ "\xC8\x9C" => "\xC8\x9D",
      /* U+021E */ "\xC8\x9E" => "\x68",
      /* U+021F */ "\xC8\x9F" => "\x68",
      /* U+0220 */ "\xC8\xA0" => "\x6E",
      /* U+0221 */ "\xC8\xA1" => "\x64",
      /* U+0222 */ "\xC8\xA2" => "\xC8\xA3",
      /* U+0224 */ "\xC8\xA4" => "\x7A",
      /* U+0225 */ "\xC8\xA5" => "\x7A",
      /* U+0226 */ "\xC8\xA6" => "\x61",
      /* U+0227 */ "\xC8\xA7" => "\x61",
      /* U+0228 */ "\xC8\xA8" => "\x65",
      /* U+0229 */ "\xC8\xA9" => "\x65",
      /* U+022A */ "\xC8\xAA" => "\x6F",
      /* U+022B */ "\xC8\xAB" => "\x6F",
      /* U+022C */ "\xC8\xAC" => "\x6F",
      /* U+022D */ "\xC8\xAD" => "\x6F",
      /* U+022E */ "\xC8\xAE" => "\x6F",
      /* U+022F */ "\xC8\xAF" => "\x6F",
      /* U+0230 */ "\xC8\xB0" => "\x6F",
      /* U+0231 */ "\xC8\xB1" => "\x6F",
      /* U+0232 */ "\xC8\xB2" => "\x79",
      /* U+0233 */ "\xC8\xB3" => "\x79",
      /* U+0234 */ "\xC8\xB4" => "\x6C",
      /* U+0235 */ "\xC8\xB5" => "\x6E",
      /* U+0236 */ "\xC8\xB6" => "\x74",
      /* U+023B */ "\xC8\xBB" => "\xC8\xBC",
      /* U+023D */ "\xC8\xBD" => "\x6C",
      /* U+0241 */ "\xC9\x81" => "\xCA\x94",
      //------  U+0370..U+03FF : Greek and Coptic
      /* U+0386 */ "\xCE\x86" => "\xCE\xB1",
      /* U+0388 */ "\xCE\x88" => "\xCE\xB5",
      /* U+0389 */ "\xCE\x89" => "\xCE\xB7",
      /* U+038A */ "\xCE\x8A" => "\xCE\xB9",
      /* U+038C */ "\xCE\x8C" => "\xCE\xBF",
      /* U+038E */ "\xCE\x8E" => "\xCF\x85",
      /* U+038F */ "\xCE\x8F" => "\xCF\x89",
      /* U+0390 */ "\xCE\x90" => "\xCE\xB9",
      /* U+0391 */ "\xCE\x91" => "\xCE\xB1",
      /* U+0392 */ "\xCE\x92" => "\xCE\xB2",
      /* U+0393 */ "\xCE\x93" => "\xCE\xB3",
      /* U+0394 */ "\xCE\x94" => "\xCE\xB4",
      /* U+0395 */ "\xCE\x95" => "\xCE\xB5",
      /* U+0396 */ "\xCE\x96" => "\xCE\xB6",
      /* U+0397 */ "\xCE\x97" => "\xCE\xB7",
      /* U+0398 */ "\xCE\x98" => "\xCE\xB8",
      /* U+0399 */ "\xCE\x99" => "\xCE\xB9",
      /* U+039A */ "\xCE\x9A" => "\xCE\xBA",
      /* U+039B */ "\xCE\x9B" => "\xCE\xBB",
      /* U+039C */ "\xCE\x9C" => "\xCE\xBC",
      /* U+039D */ "\xCE\x9D" => "\xCE\xBD",
      /* U+039E */ "\xCE\x9E" => "\xCE\xBE",
      /* U+039F */ "\xCE\x9F" => "\xCE\xBF",
      /* U+03A0 */ "\xCE\xA0" => "\xCF\x80",
      /* U+03A1 */ "\xCE\xA1" => "\xCF\x81",
      /* U+03A3 */ "\xCE\xA3" => "\xCF\x83",
      /* U+03A4 */ "\xCE\xA4" => "\xCF\x84",
      /* U+03A5 */ "\xCE\xA5" => "\xCF\x85",
      /* U+03A6 */ "\xCE\xA6" => "\xCF\x86",
      /* U+03A7 */ "\xCE\xA7" => "\xCF\x87",
      /* U+03A8 */ "\xCE\xA8" => "\xCF\x88",
      /* U+03A9 */ "\xCE\xA9" => "\xCF\x89",
      /* U+03AA */ "\xCE\xAA" => "\xCE\xB9",
      /* U+03AB */ "\xCE\xAB" => "\xCF\x85",
      /* U+03AC */ "\xCE\xAC" => "\xCE\xB1",
      /* U+03AD */ "\xCE\xAD" => "\xCE\xB5",
      /* U+03AE */ "\xCE\xAE" => "\xCE\xB7",
      /* U+03AF */ "\xCE\xAF" => "\xCE\xB9",
      /* U+03B0 */ "\xCE\xB0" => "\xCF\x85",
      /* U+03CA */ "\xCF\x8A" => "\xCE\xB9",
      /* U+03CB */ "\xCF\x8B" => "\xCF\x85",
      /* U+03CC */ "\xCF\x8C" => "\xCE\xBF",
      /* U+03CD */ "\xCF\x8D" => "\xCF\x85",
      /* U+03CE */ "\xCF\x8E" => "\xCF\x89",
      /* U+03D8 */ "\xCF\x98" => "\xCF\x99",
      /* U+03DA */ "\xCF\x9A" => "\xCF\x9B",
      /* U+03DC */ "\xCF\x9C" => "\xCF\x9D",
      /* U+03DE */ "\xCF\x9E" => "\xCF\x9F",
      /* U+03E0 */ "\xCF\xA0" => "\xCF\xA1",
      /* U+03E2 */ "\xCF\xA2" => "\xCF\xA3",
      /* U+03E4 */ "\xCF\xA4" => "\xCF\xA5",
      /* U+03E6 */ "\xCF\xA6" => "\xCF\xA7",
      /* U+03E8 */ "\xCF\xA8" => "\xCF\xA9",
      /* U+03EA */ "\xCF\xAA" => "\xCF\xAB",
      /* U+03EC */ "\xCF\xAC" => "\xCF\xAD",
      /* U+03EE */ "\xCF\xAE" => "\xCF\xAF",
      /* U+03F4 */ "\xCF\xB4" => "\xCE\xB8",
      /* U+03F7 */ "\xCF\xB7" => "\xCF\xB8",
      /* U+03F9 */ "\xCF\xB9" => "\xCF\xB2",
      /* U+03FA */ "\xCF\xBA" => "\xCF\xBB",
      //------  U+0400..U+04FF : Cyrillic
      /* U+0400 */ "\xD0\x80" => "\xD0\xB5",
      /* U+0401 */ "\xD0\x81" => "\xD1\x91",
      /* U+0402 */ "\xD0\x82" => "\xD1\x92",
      /* U+0403 */ "\xD0\x83" => "\xD1\x93",
      /* U+0404 */ "\xD0\x84" => "\xD1\x94",
      /* U+0405 */ "\xD0\x85" => "\xD1\x95",
      /* U+0406 */ "\xD0\x86" => "\xD1\x96",
      /* U+0407 */ "\xD0\x87" => "\xD1\x97",
      /* U+0408 */ "\xD0\x88" => "\xD1\x98",
      /* U+0409 */ "\xD0\x89" => "\xD1\x99",
      /* U+040A */ "\xD0\x8A" => "\xD1\x9A",
      /* U+040B */ "\xD0\x8B" => "\xD1\x9B",
      /* U+040C */ "\xD0\x8C" => "\xD1\x9C",
      /* U+040D */ "\xD0\x8D" => "\xD0\xB8",
      /* U+040E */ "\xD0\x8E" => "\xD1\x9E",
      /* U+040F */ "\xD0\x8F" => "\xD1\x9F",
      /* U+0410 */ "\xD0\x90" => "\xD0\xB0",
      /* U+0411 */ "\xD0\x91" => "\xD0\xB1",
      /* U+0412 */ "\xD0\x92" => "\xD0\xB2",
      /* U+0413 */ "\xD0\x93" => "\xD0\xB3",
      /* U+0414 */ "\xD0\x94" => "\xD0\xB4",
      /* U+0415 */ "\xD0\x95" => "\xD0\xB5",
      /* U+0416 */ "\xD0\x96" => "\xD0\xB6",
      /* U+0417 */ "\xD0\x97" => "\xD0\xB7",
      /* U+0418 */ "\xD0\x98" => "\xD0\xB8",
      /* U+0419 */ "\xD0\x99" => "\xD0\xB9",
      /* U+041A */ "\xD0\x9A" => "\xD0\xBA",
      /* U+041B */ "\xD0\x9B" => "\xD0\xBB",
      /* U+041C */ "\xD0\x9C" => "\xD0\xBC",
      /* U+041D */ "\xD0\x9D" => "\xD0\xBD",
      /* U+041E */ "\xD0\x9E" => "\xD0\xBE",
      /* U+041F */ "\xD0\x9F" => "\xD0\xBF",
      /* U+0420 */ "\xD0\xA0" => "\xD1\x80",
      /* U+0421 */ "\xD0\xA1" => "\xD1\x81",
      /* U+0422 */ "\xD0\xA2" => "\xD1\x82",
      /* U+0423 */ "\xD0\xA3" => "\xD1\x83",
      /* U+0424 */ "\xD0\xA4" => "\xD1\x84",
      /* U+0425 */ "\xD0\xA5" => "\xD1\x85",
      /* U+0426 */ "\xD0\xA6" => "\xD1\x86",
      /* U+0427 */ "\xD0\xA7" => "\xD1\x87",
      /* U+0428 */ "\xD0\xA8" => "\xD1\x88",
      /* U+0429 */ "\xD0\xA9" => "\xD1\x89",
      /* U+042A */ "\xD0\xAA" => "\xD1\x8A",
      /* U+042B */ "\xD0\xAB" => "\xD1\x8B",
      /* U+042C */ "\xD0\xAC" => "\xD1\x8C",
      /* U+042D */ "\xD0\xAD" => "\xD1\x8D",
      /* U+042E */ "\xD0\xAE" => "\xD1\x8E",
      /* U+042F */ "\xD0\xAF" => "\xD1\x8F",
      /* U+0450 */ "\xD1\x90" => "\xD0\xB5",
      /* U+045D */ "\xD1\x9D" => "\xD0\xB8",
      /* U+0460 */ "\xD1\xA0" => "\xD1\xA1",
      /* U+0462 */ "\xD1\xA2" => "\xD1\xA3",
      /* U+0464 */ "\xD1\xA4" => "\xD1\xA5",
      /* U+0466 */ "\xD1\xA6" => "\xD1\xA7",
      /* U+0468 */ "\xD1\xA8" => "\xD1\xA9",
      /* U+046A */ "\xD1\xAA" => "\xD1\xAB",
      /* U+046C */ "\xD1\xAC" => "\xD1\xAD",
      /* U+046E */ "\xD1\xAE" => "\xD1\xAF",
      /* U+0470 */ "\xD1\xB0" => "\xD1\xB1",
      /* U+0472 */ "\xD1\xB2" => "\xD1\xB3",
      /* U+0474 */ "\xD1\xB4" => "\xD1\xB5",
      /* U+0476 */ "\xD1\xB6" => "\xD1\xB5",
      /* U+0477 */ "\xD1\xB7" => "\xD1\xB5",
      /* U+0478 */ "\xD1\xB8" => "\xD1\xB9",
      /* U+047A */ "\xD1\xBA" => "\xD1\xBB",
      /* U+047C */ "\xD1\xBC" => "\xD1\xA1",
      /* U+047D */ "\xD1\xBD" => "\xD1\xA1",
      /* U+047E */ "\xD1\xBE" => "\xD1\xBF",
      /* U+0480 */ "\xD2\x80" => "\xD2\x81",
      /* U+048A */ "\xD2\x8A" => "\xD0\xB9",
      /* U+048B */ "\xD2\x8B" => "\xD0\xB9",
      /* U+048C */ "\xD2\x8C" => "\xD2\x8D",
      /* U+048E */ "\xD2\x8E" => "\xD1\x80",
      /* U+048F */ "\xD2\x8F" => "\xD1\x80",
      /* U+0490 */ "\xD2\x90" => "\xD0\xB3",
      /* U+0491 */ "\xD2\x91" => "\xD0\xB3",
      /* U+0492 */ "\xD2\x92" => "\xD0\xB3",
      /* U+0493 */ "\xD2\x93" => "\xD0\xB3",
      /* U+0494 */ "\xD2\x94" => "\xD0\xB3",
      /* U+0495 */ "\xD2\x95" => "\xD0\xB3",
      /* U+0496 */ "\xD2\x96" => "\xD0\xB6",
      /* U+0497 */ "\xD2\x97" => "\xD0\xB6",
      /* U+0498 */ "\xD2\x98" => "\xD0\xB7",
      /* U+0499 */ "\xD2\x99" => "\xD0\xB7",
      /* U+049A */ "\xD2\x9A" => "\xD0\xBA",
      /* U+049B */ "\xD2\x9B" => "\xD0\xBA",
      /* U+049C */ "\xD2\x9C" => "\xD0\xBA",
      /* U+049D */ "\xD2\x9D" => "\xD0\xBA",
      /* U+049E */ "\xD2\x9E" => "\xD0\xBA",
      /* U+049F */ "\xD2\x9F" => "\xD0\xBA",
      /* U+04A0 */ "\xD2\xA0" => "\xD2\xA1",
      /* U+04A2 */ "\xD2\xA2" => "\xD0\xBD",
      /* U+04A3 */ "\xD2\xA3" => "\xD0\xBD",
      /* U+04A4 */ "\xD2\xA4" => "\xD2\xA5",
      /* U+04A6 */ "\xD2\xA6" => "\xD0\xBF",
      /* U+04A7 */ "\xD2\xA7" => "\xD0\xBF",
      /* U+04A8 */ "\xD2\xA8" => "\xD2\xA9",
      /* U+04AA */ "\xD2\xAA" => "\xD1\x81",
      /* U+04AB */ "\xD2\xAB" => "\xD1\x81",
      /* U+04AC */ "\xD2\xAC" => "\xD1\x82",
      /* U+04AD */ "\xD2\xAD" => "\xD1\x82",
      /* U+04AE */ "\xD2\xAE" => "\xD2\xAF",
      /* U+04B0 */ "\xD2\xB0" => "\xD2\xAF",
      /* U+04B1 */ "\xD2\xB1" => "\xD2\xAF",
      /* U+04B2 */ "\xD2\xB2" => "\xD0\xA5",
      /* U+04B3 */ "\xD2\xB3" => "\xD0\xA5",
      /* U+04B4 */ "\xD2\xB4" => "\xD2\xB5",
      /* U+04B6 */ "\xD2\xB6" => "\xD2\xBC",
      /* U+04B7 */ "\xD2\xB7" => "\xD2\xBC",
      /* U+04B8 */ "\xD2\xB8" => "\xD1\x87",
      /* U+04B9 */ "\xD2\xB9" => "\xD1\x87",
      /* U+04BA */ "\xD2\xBA" => "\xD2\xBB",
      /* U+04BC */ "\xD2\xBC" => "\xD2\xBD",
      /* U+04BE */ "\xD2\xBE" => "\xD2\xBC",
      /* U+04BF */ "\xD2\xBF" => "\xD2\xBC",
      /* U+04C1 */ "\xD3\x81" => "\xD0\xB6",
      /* U+04C2 */ "\xD3\x82" => "\xD0\xB6",
      /* U+04C3 */ "\xD3\x83" => "\xD0\xBA",
      /* U+04C4 */ "\xD3\x84" => "\xD0\xBA",
      /* U+04C5 */ "\xD3\x85" => "\xD0\xBB",
      /* U+04C6 */ "\xD3\x86" => "\xD0\xBB",
      /* U+04C7 */ "\xD3\x87" => "\xD0\xBD",
      /* U+04C8 */ "\xD3\x88" => "\xD0\xBD",
      /* U+04C9 */ "\xD3\x89" => "\xD0\xBD",
      /* U+04CA */ "\xD3\x8A" => "\xD0\xBD",
      /* U+04CB */ "\xD3\x8B" => "\xD2\xBC",
      /* U+04CC */ "\xD3\x8C" => "\xD2\xBC",
      /* U+04CD */ "\xD3\x8D" => "\xD0\xBC",
      /* U+04CE */ "\xD3\x8E" => "\xD0\xBC",
      /* U+04D0 */ "\xD3\x90" => "\xD0\xB0",
      /* U+04D1 */ "\xD3\x91" => "\xD0\xB0",
      /* U+04D2 */ "\xD3\x92" => "\xD0\xB0",
      /* U+04D3 */ "\xD3\x93" => "\xD0\xB0",
      /* U+04D4 */ "\xD3\x94" => "\xD3\x95",
      /* U+04D6 */ "\xD3\x96" => "\xD0\xB5",
      /* U+04D7 */ "\xD3\x97" => "\xD0\xB5",
      /* U+04D8 */ "\xD3\x98" => "\xD3\x99",
      /* U+04DA */ "\xD3\x9A" => "\xD3\x99",
      /* U+04DB */ "\xD3\x9B" => "\xD3\x99",
      /* U+04DC */ "\xD3\x9C" => "\xD0\xB6",
      /* U+04DD */ "\xD3\x9D" => "\xD0\xB6",
      /* U+04DE */ "\xD3\x9E" => "\xD0\xB7",
      /* U+04DF */ "\xD3\x9F" => "\xD0\xB7",
      /* U+04E0 */ "\xD3\xA0" => "\xD3\xA1",
      /* U+04E2 */ "\xD3\xA2" => "\xD0\xB8",
      /* U+04E3 */ "\xD3\xA3" => "\xD0\xB8",
      /* U+04E4 */ "\xD3\xA4" => "\xD0\xB8",
      /* U+04E5 */ "\xD3\xA5" => "\xD0\xB8",
      /* U+04E6 */ "\xD3\xA6" => "\xD0\xBE",
      /* U+04E7 */ "\xD3\xA7" => "\xD0\xBE",
      /* U+04E8 */ "\xD3\xA8" => "\xD3\xA9",
      /* U+04EA */ "\xD3\xAA" => "\xD3\xA9",
      /* U+04EB */ "\xD3\xAB" => "\xD3\xA9",
      /* U+04EC */ "\xD3\xAC" => "\xD1\x8D",
      /* U+04ED */ "\xD3\xAD" => "\xD1\x8D",
      /* U+04EE */ "\xD3\xAE" => "\xD1\x83",
      /* U+04EF */ "\xD3\xAF" => "\xD1\x83",
      /* U+04F0 */ "\xD3\xB0" => "\xD1\x83",
      /* U+04F1 */ "\xD3\xB1" => "\xD1\x83",
      /* U+04F2 */ "\xD3\xB2" => "\xD1\x83",
      /* U+04F3 */ "\xD3\xB3" => "\xD1\x83",
      /* U+04F4 */ "\xD3\xB4" => "\xD1\x87",
      /* U+04F5 */ "\xD3\xB5" => "\xD1\x87",
      /* U+04F6 */ "\xD3\xB6" => "\xD3\xB7",
      /* U+04F8 */ "\xD3\xB8" => "\xD1\x8B",
      /* U+04F9 */ "\xD3\xB9" => "\xD1\x8B",
      //------  U+0500..U+052F : Cyrillic Supplement
      /* U+0500 */ "\xD4\x80" => "\xD4\x81",
      /* U+0502 */ "\xD4\x82" => "\xD4\x83",
      /* U+0504 */ "\xD4\x84" => "\xD4\x85",
      /* U+0506 */ "\xD4\x86" => "\xD4\x87",
      /* U+0508 */ "\xD4\x88" => "\xD4\x89",
      /* U+050A */ "\xD4\x8A" => "\xD4\x8B",
      /* U+050C */ "\xD4\x8C" => "\xD4\x8D",
      /* U+050E */ "\xD4\x8E" => "\xD4\x8F",
      //------  U+0530..U+058F : Armenian
      /* U+0531 */ "\xD4\xB1" => "\xD5\xA1",
      /* U+0532 */ "\xD4\xB2" => "\xD5\xA2",
      /* U+0533 */ "\xD4\xB3" => "\xD5\xA3",
      /* U+0534 */ "\xD4\xB4" => "\xD5\xA4",
      /* U+0535 */ "\xD4\xB5" => "\xD5\xA5",
      /* U+0536 */ "\xD4\xB6" => "\xD5\xA6",
      /* U+0537 */ "\xD4\xB7" => "\xD5\xA7",
      /* U+0538 */ "\xD4\xB8" => "\xD5\xA8",
      /* U+0539 */ "\xD4\xB9" => "\xD5\xA9",
      /* U+053A */ "\xD4\xBA" => "\xD5\xAA",
      /* U+053B */ "\xD4\xBB" => "\xD5\xAB",
      /* U+053C */ "\xD4\xBC" => "\xD5\xAC",
      /* U+053D */ "\xD4\xBD" => "\xD5\xAD",
      /* U+053E */ "\xD4\xBE" => "\xD5\xAE",
      /* U+053F */ "\xD4\xBF" => "\xD5\xAF",
      /* U+0540 */ "\xD5\x80" => "\xD5\xB0",
      /* U+0541 */ "\xD5\x81" => "\xD5\xB1",
      /* U+0542 */ "\xD5\x82" => "\xD5\xB2",
      /* U+0543 */ "\xD5\x83" => "\xD5\xB3",
      /* U+0544 */ "\xD5\x84" => "\xD5\xB4",
      /* U+0545 */ "\xD5\x85" => "\xD5\xB5",
      /* U+0546 */ "\xD5\x86" => "\xD5\xB6",
      /* U+0547 */ "\xD5\x87" => "\xD5\xB7",
      /* U+0548 */ "\xD5\x88" => "\xD5\xB8",
      /* U+0549 */ "\xD5\x89" => "\xD5\xB9",
      /* U+054A */ "\xD5\x8A" => "\xD5\xBA",
      /* U+054B */ "\xD5\x8B" => "\xD5\xBB",
      /* U+054C */ "\xD5\x8C" => "\xD5\xBC",
      /* U+054D */ "\xD5\x8D" => "\xD5\xBD",
      /* U+054E */ "\xD5\x8E" => "\xD5\xBE",
      /* U+054F */ "\xD5\x8F" => "\xD5\xBF",
      /* U+0550 */ "\xD5\x90" => "\xD6\x80",
      /* U+0551 */ "\xD5\x91" => "\xD6\x81",
      /* U+0552 */ "\xD5\x92" => "\xD6\x82",
      /* U+0553 */ "\xD5\x93" => "\xD6\x83",
      /* U+0554 */ "\xD5\x94" => "\xD6\x84",
      /* U+0555 */ "\xD5\x95" => "\xD6\x85",
      /* U+0556 */ "\xD5\x96" => "\xD6\x86",
      //------  U+1E00..U+1EFF : Latin Extended Additional
      /* U+1E00 */ "\xE1\xB8\x80" => "\x61",
      /* U+1E01 */ "\xE1\xB8\x81" => "\x61",
      /* U+1E02 */ "\xE1\xB8\x82" => "\x62",
      /* U+1E03 */ "\xE1\xB8\x83" => "\x62",
      /* U+1E04 */ "\xE1\xB8\x84" => "\x62",
      /* U+1E05 */ "\xE1\xB8\x85" => "\x62",
      /* U+1E06 */ "\xE1\xB8\x86" => "\x62",
      /* U+1E07 */ "\xE1\xB8\x87" => "\x62",
      /* U+1E08 */ "\xE1\xB8\x88" => "\x63",
      /* U+1E09 */ "\xE1\xB8\x89" => "\x63",
      /* U+1E0A */ "\xE1\xB8\x8A" => "\x64",
      /* U+1E0B */ "\xE1\xB8\x8B" => "\x64",
      /* U+1E0C */ "\xE1\xB8\x8C" => "\x64",
      /* U+1E0D */ "\xE1\xB8\x8D" => "\x64",
      /* U+1E0E */ "\xE1\xB8\x8E" => "\x64",
      /* U+1E0F */ "\xE1\xB8\x8F" => "\x64",
      /* U+1E10 */ "\xE1\xB8\x90" => "\x64",
      /* U+1E11 */ "\xE1\xB8\x91" => "\x64",
      /* U+1E12 */ "\xE1\xB8\x92" => "\x64",
      /* U+1E13 */ "\xE1\xB8\x93" => "\x64",
      /* U+1E14 */ "\xE1\xB8\x94" => "\x65",
      /* U+1E15 */ "\xE1\xB8\x95" => "\x65",
      /* U+1E16 */ "\xE1\xB8\x96" => "\x65",
      /* U+1E17 */ "\xE1\xB8\x97" => "\x65",
      /* U+1E18 */ "\xE1\xB8\x98" => "\x65",
      /* U+1E19 */ "\xE1\xB8\x99" => "\x65",
      /* U+1E1A */ "\xE1\xB8\x9A" => "\x65",
      /* U+1E1B */ "\xE1\xB8\x9B" => "\x65",
      /* U+1E1C */ "\xE1\xB8\x9C" => "\x65",
      /* U+1E1D */ "\xE1\xB8\x9D" => "\x65",
      /* U+1E1E */ "\xE1\xB8\x9E" => "\x66",
      /* U+1E1F */ "\xE1\xB8\x9F" => "\x66",
      /* U+1E20 */ "\xE1\xB8\xA0" => "\x67",
      /* U+1E21 */ "\xE1\xB8\xA1" => "\x67",
      /* U+1E22 */ "\xE1\xB8\xA2" => "\x68",
      /* U+1E23 */ "\xE1\xB8\xA3" => "\x68",
      /* U+1E24 */ "\xE1\xB8\xA4" => "\x68",
      /* U+1E25 */ "\xE1\xB8\xA5" => "\x68",
      /* U+1E26 */ "\xE1\xB8\xA6" => "\x68",
      /* U+1E27 */ "\xE1\xB8\xA7" => "\x68",
      /* U+1E28 */ "\xE1\xB8\xA8" => "\x68",
      /* U+1E29 */ "\xE1\xB8\xA9" => "\x68",
      /* U+1E2A */ "\xE1\xB8\xAA" => "\x68",
      /* U+1E2B */ "\xE1\xB8\xAB" => "\x68",
      /* U+1E2C */ "\xE1\xB8\xAC" => "\x69",
      /* U+1E2D */ "\xE1\xB8\xAD" => "\x69",
      /* U+1E2E */ "\xE1\xB8\xAE" => "\x69",
      /* U+1E2F */ "\xE1\xB8\xAF" => "\x69",
      /* U+1E30 */ "\xE1\xB8\xB0" => "\x6B",
      /* U+1E31 */ "\xE1\xB8\xB1" => "\x6B",
      /* U+1E32 */ "\xE1\xB8\xB2" => "\x6B",
      /* U+1E33 */ "\xE1\xB8\xB3" => "\x6B",
      /* U+1E34 */ "\xE1\xB8\xB4" => "\x6B",
      /* U+1E35 */ "\xE1\xB8\xB5" => "\x6B",
      /* U+1E36 */ "\xE1\xB8\xB6" => "\x6C",
      /* U+1E37 */ "\xE1\xB8\xB7" => "\x6C",
      /* U+1E38 */ "\xE1\xB8\xB8" => "\x6C",
      /* U+1E39 */ "\xE1\xB8\xB9" => "\x6C",
      /* U+1E3A */ "\xE1\xB8\xBA" => "\x6C",
      /* U+1E3B */ "\xE1\xB8\xBB" => "\x6C",
      /* U+1E3C */ "\xE1\xB8\xBC" => "\x6C",
      /* U+1E3D */ "\xE1\xB8\xBD" => "\x6C",
      /* U+1E3E */ "\xE1\xB8\xBE" => "\x6D",
      /* U+1E3F */ "\xE1\xB8\xBF" => "\x6D",
      /* U+1E40 */ "\xE1\xB9\x80" => "\x6D",
      /* U+1E41 */ "\xE1\xB9\x81" => "\x6D",
      /* U+1E42 */ "\xE1\xB9\x82" => "\x6D",
      /* U+1E43 */ "\xE1\xB9\x83" => "\x6D",
      /* U+1E44 */ "\xE1\xB9\x84" => "\x6E",
      /* U+1E45 */ "\xE1\xB9\x85" => "\x6E",
      /* U+1E46 */ "\xE1\xB9\x86" => "\x6E",
      /* U+1E47 */ "\xE1\xB9\x87" => "\x6E",
      /* U+1E48 */ "\xE1\xB9\x88" => "\x6E",
      /* U+1E49 */ "\xE1\xB9\x89" => "\x6E",
      /* U+1E4A */ "\xE1\xB9\x8A" => "\x6E",
      /* U+1E4B */ "\xE1\xB9\x8B" => "\x6E",
      /* U+1E4C */ "\xE1\xB9\x8C" => "\x6F",
      /* U+1E4D */ "\xE1\xB9\x8D" => "\x6F",
      /* U+1E4E */ "\xE1\xB9\x8E" => "\x6F",
      /* U+1E4F */ "\xE1\xB9\x8F" => "\x6F",
      /* U+1E50 */ "\xE1\xB9\x90" => "\x6F",
      /* U+1E51 */ "\xE1\xB9\x91" => "\x6F",
      /* U+1E52 */ "\xE1\xB9\x92" => "\x6F",
      /* U+1E53 */ "\xE1\xB9\x93" => "\x6F",
      /* U+1E54 */ "\xE1\xB9\x94" => "\x70",
      /* U+1E55 */ "\xE1\xB9\x95" => "\x70",
      /* U+1E56 */ "\xE1\xB9\x96" => "\x70",
      /* U+1E57 */ "\xE1\xB9\x97" => "\x70",
      /* U+1E58 */ "\xE1\xB9\x98" => "\x72",
      /* U+1E59 */ "\xE1\xB9\x99" => "\x72",
      /* U+1E5A */ "\xE1\xB9\x9A" => "\x72",
      /* U+1E5B */ "\xE1\xB9\x9B" => "\x72",
      /* U+1E5C */ "\xE1\xB9\x9C" => "\x72",
      /* U+1E5D */ "\xE1\xB9\x9D" => "\x72",
      /* U+1E5E */ "\xE1\xB9\x9E" => "\x72",
      /* U+1E5F */ "\xE1\xB9\x9F" => "\x72",
      /* U+1E60 */ "\xE1\xB9\xA0" => "\x73",
      /* U+1E61 */ "\xE1\xB9\xA1" => "\x73",
      /* U+1E62 */ "\xE1\xB9\xA2" => "\x73",
      /* U+1E63 */ "\xE1\xB9\xA3" => "\x73",
      /* U+1E64 */ "\xE1\xB9\xA4" => "\x73",
      /* U+1E65 */ "\xE1\xB9\xA5" => "\x73",
      /* U+1E66 */ "\xE1\xB9\xA6" => "\x73",
      /* U+1E67 */ "\xE1\xB9\xA7" => "\x73",
      /* U+1E68 */ "\xE1\xB9\xA8" => "\x73",
      /* U+1E69 */ "\xE1\xB9\xA9" => "\x73",
      /* U+1E6A */ "\xE1\xB9\xAA" => "\x74",
      /* U+1E6B */ "\xE1\xB9\xAB" => "\x74",
      /* U+1E6C */ "\xE1\xB9\xAC" => "\x74",
      /* U+1E6D */ "\xE1\xB9\xAD" => "\x74",
      /* U+1E6E */ "\xE1\xB9\xAE" => "\x74",
      /* U+1E6F */ "\xE1\xB9\xAF" => "\x74",
      /* U+1E70 */ "\xE1\xB9\xB0" => "\x74",
      /* U+1E71 */ "\xE1\xB9\xB1" => "\x74",
      /* U+1E72 */ "\xE1\xB9\xB2" => "\x75",
      /* U+1E73 */ "\xE1\xB9\xB3" => "\x75",
      /* U+1E74 */ "\xE1\xB9\xB4" => "\x75",
      /* U+1E75 */ "\xE1\xB9\xB5" => "\x75",
      /* U+1E76 */ "\xE1\xB9\xB6" => "\x75",
      /* U+1E77 */ "\xE1\xB9\xB7" => "\x75",
      /* U+1E78 */ "\xE1\xB9\xB8" => "\x75",
      /* U+1E79 */ "\xE1\xB9\xB9" => "\x75",
      /* U+1E7A */ "\xE1\xB9\xBA" => "\x75",
      /* U+1E7B */ "\xE1\xB9\xBB" => "\x75",
      /* U+1E7C */ "\xE1\xB9\xBC" => "\x76",
      /* U+1E7D */ "\xE1\xB9\xBD" => "\x76",
      /* U+1E7E */ "\xE1\xB9\xBE" => "\x76",
      /* U+1E7F */ "\xE1\xB9\xBF" => "\x76",
      /* U+1E80 */ "\xE1\xBA\x80" => "\x77",
      /* U+1E81 */ "\xE1\xBA\x81" => "\x77",
      /* U+1E82 */ "\xE1\xBA\x82" => "\x77",
      /* U+1E83 */ "\xE1\xBA\x83" => "\x77",
      /* U+1E84 */ "\xE1\xBA\x84" => "\x77",
      /* U+1E85 */ "\xE1\xBA\x85" => "\x77",
      /* U+1E86 */ "\xE1\xBA\x86" => "\x77",
      /* U+1E87 */ "\xE1\xBA\x87" => "\x77",
      /* U+1E88 */ "\xE1\xBA\x88" => "\x77",
      /* U+1E89 */ "\xE1\xBA\x89" => "\x77",
      /* U+1E8A */ "\xE1\xBA\x8A" => "\x78",
      /* U+1E8B */ "\xE1\xBA\x8B" => "\x78",
      /* U+1E8C */ "\xE1\xBA\x8C" => "\x78",
      /* U+1E8D */ "\xE1\xBA\x8D" => "\x78",
      /* U+1E8E */ "\xE1\xBA\x8E" => "\x79",
      /* U+1E8F */ "\xE1\xBA\x8F" => "\x79",
      /* U+1E90 */ "\xE1\xBA\x90" => "\x7A",
      /* U+1E91 */ "\xE1\xBA\x91" => "\x7A",
      /* U+1E92 */ "\xE1\xBA\x92" => "\x7A",
      /* U+1E93 */ "\xE1\xBA\x93" => "\x7A",
      /* U+1E94 */ "\xE1\xBA\x94" => "\x7A",
      /* U+1E95 */ "\xE1\xBA\x95" => "\x7A",
      /* U+1E96 */ "\xE1\xBA\x96" => "\x68",
      /* U+1E97 */ "\xE1\xBA\x97" => "\x74",
      /* U+1E98 */ "\xE1\xBA\x98" => "\x77",
      /* U+1E99 */ "\xE1\xBA\x99" => "\x79",
      /* U+1E9A */ "\xE1\xBA\x9A" => "\x61",
      /* U+1E9B */ "\xE1\xBA\x9B" => "\xC5\xBF",
      /* U+1EA0 */ "\xE1\xBA\xA0" => "\x61",
      /* U+1EA1 */ "\xE1\xBA\xA1" => "\x61",
      /* U+1EA2 */ "\xE1\xBA\xA2" => "\x61",
      /* U+1EA3 */ "\xE1\xBA\xA3" => "\x61",
      /* U+1EA4 */ "\xE1\xBA\xA4" => "\x61",
      /* U+1EA5 */ "\xE1\xBA\xA5" => "\x61",
      /* U+1EA6 */ "\xE1\xBA\xA6" => "\x61",
      /* U+1EA7 */ "\xE1\xBA\xA7" => "\x61",
      /* U+1EA8 */ "\xE1\xBA\xA8" => "\x61",
      /* U+1EA9 */ "\xE1\xBA\xA9" => "\x61",
      /* U+1EAA */ "\xE1\xBA\xAA" => "\x61",
      /* U+1EAB */ "\xE1\xBA\xAB" => "\x61",
      /* U+1EAC */ "\xE1\xBA\xAC" => "\x61",
      /* U+1EAD */ "\xE1\xBA\xAD" => "\x61",
      /* U+1EAE */ "\xE1\xBA\xAE" => "\x61",
      /* U+1EAF */ "\xE1\xBA\xAF" => "\x61",
      /* U+1EB0 */ "\xE1\xBA\xB0" => "\x61",
      /* U+1EB1 */ "\xE1\xBA\xB1" => "\x61",
      /* U+1EB2 */ "\xE1\xBA\xB2" => "\x61",
      /* U+1EB3 */ "\xE1\xBA\xB3" => "\x61",
      /* U+1EB4 */ "\xE1\xBA\xB4" => "\x61",
      /* U+1EB5 */ "\xE1\xBA\xB5" => "\x61",
      /* U+1EB6 */ "\xE1\xBA\xB6" => "\x61",
      /* U+1EB7 */ "\xE1\xBA\xB7" => "\x61",
      /* U+1EB8 */ "\xE1\xBA\xB8" => "\x65",
      /* U+1EB9 */ "\xE1\xBA\xB9" => "\x65",
      /* U+1EBA */ "\xE1\xBA\xBA" => "\x65",
      /* U+1EBB */ "\xE1\xBA\xBB" => "\x65",
      /* U+1EBC */ "\xE1\xBA\xBC" => "\x65",
      /* U+1EBD */ "\xE1\xBA\xBD" => "\x65",
      /* U+1EBE */ "\xE1\xBA\xBE" => "\x65",
      /* U+1EBF */ "\xE1\xBA\xBF" => "\x65",
      /* U+1EC0 */ "\xE1\xBB\x80" => "\x65",
      /* U+1EC1 */ "\xE1\xBB\x81" => "\x65",
      /* U+1EC2 */ "\xE1\xBB\x82" => "\x65",
      /* U+1EC3 */ "\xE1\xBB\x83" => "\x65",
      /* U+1EC4 */ "\xE1\xBB\x84" => "\x65",
      /* U+1EC5 */ "\xE1\xBB\x85" => "\x65",
      /* U+1EC6 */ "\xE1\xBB\x86" => "\x65",
      /* U+1EC7 */ "\xE1\xBB\x87" => "\x65",
      /* U+1EC8 */ "\xE1\xBB\x88" => "\x69",
      /* U+1EC9 */ "\xE1\xBB\x89" => "\x69",
      /* U+1ECA */ "\xE1\xBB\x8A" => "\x69",
      /* U+1ECB */ "\xE1\xBB\x8B" => "\x69",
      /* U+1ECC */ "\xE1\xBB\x8C" => "\x6F",
      /* U+1ECD */ "\xE1\xBB\x8D" => "\x6F",
      /* U+1ECE */ "\xE1\xBB\x8E" => "\x6F",
      /* U+1ECF */ "\xE1\xBB\x8F" => "\x6F",
      /* U+1ED0 */ "\xE1\xBB\x90" => "\x6F",
      /* U+1ED1 */ "\xE1\xBB\x91" => "\x6F",
      /* U+1ED2 */ "\xE1\xBB\x92" => "\x6F",
      /* U+1ED3 */ "\xE1\xBB\x93" => "\x6F",
      /* U+1ED4 */ "\xE1\xBB\x94" => "\x6F",
      /* U+1ED5 */ "\xE1\xBB\x95" => "\x6F",
      /* U+1ED6 */ "\xE1\xBB\x96" => "\x6F",
      /* U+1ED7 */ "\xE1\xBB\x97" => "\x6F",
      /* U+1ED8 */ "\xE1\xBB\x98" => "\x6F",
      /* U+1ED9 */ "\xE1\xBB\x99" => "\x6F",
      /* U+1EDA */ "\xE1\xBB\x9A" => "\x6F",
      /* U+1EDB */ "\xE1\xBB\x9B" => "\x6F",
      /* U+1EDC */ "\xE1\xBB\x9C" => "\x6F",
      /* U+1EDD */ "\xE1\xBB\x9D" => "\x6F",
      /* U+1EDE */ "\xE1\xBB\x9E" => "\x6F",
      /* U+1EDF */ "\xE1\xBB\x9F" => "\x6F",
      /* U+1EE0 */ "\xE1\xBB\xA0" => "\x6F",
      /* U+1EE1 */ "\xE1\xBB\xA1" => "\x6F",
      /* U+1EE2 */ "\xE1\xBB\xA2" => "\x6F",
      /* U+1EE3 */ "\xE1\xBB\xA3" => "\x6F",
      /* U+1EE4 */ "\xE1\xBB\xA4" => "\x75",
      /* U+1EE5 */ "\xE1\xBB\xA5" => "\x75",
      /* U+1EE6 */ "\xE1\xBB\xA6" => "\x75",
      /* U+1EE7 */ "\xE1\xBB\xA7" => "\x75",
      /* U+1EE8 */ "\xE1\xBB\xA8" => "\x75",
      /* U+1EE9 */ "\xE1\xBB\xA9" => "\x75",
      /* U+1EEA */ "\xE1\xBB\xAA" => "\x75",
      /* U+1EEB */ "\xE1\xBB\xAB" => "\x75",
      /* U+1EEC */ "\xE1\xBB\xAC" => "\x75",
      /* U+1EED */ "\xE1\xBB\xAD" => "\x75",
      /* U+1EEE */ "\xE1\xBB\xAE" => "\x75",
      /* U+1EEF */ "\xE1\xBB\xAF" => "\x75",
      /* U+1EF0 */ "\xE1\xBB\xB0" => "\x75",
      /* U+1EF1 */ "\xE1\xBB\xB1" => "\x75",
      /* U+1EF2 */ "\xE1\xBB\xB2" => "\x79",
      /* U+1EF3 */ "\xE1\xBB\xB3" => "\x79",
      /* U+1EF4 */ "\xE1\xBB\xB4" => "\x79",
      /* U+1EF5 */ "\xE1\xBB\xB5" => "\x79",
      /* U+1EF6 */ "\xE1\xBB\xB6" => "\x79",
      /* U+1EF7 */ "\xE1\xBB\xB7" => "\x79",
      /* U+1EF8 */ "\xE1\xBB\xB8" => "\x79",
      /* U+1EF9 */ "\xE1\xBB\xB9" => "\x79",
      //------  U+1F00..U+1FFF : Greek Extended
      /* U+1F00 */ "\xE1\xBC\x80" => "\xCE\xB1",
      /* U+1F01 */ "\xE1\xBC\x81" => "\xCE\xB1",
      /* U+1F02 */ "\xE1\xBC\x82" => "\xCE\xB1",
      /* U+1F03 */ "\xE1\xBC\x83" => "\xCE\xB1",
      /* U+1F04 */ "\xE1\xBC\x84" => "\xCE\xB1",
      /* U+1F05 */ "\xE1\xBC\x85" => "\xCE\xB1",
      /* U+1F06 */ "\xE1\xBC\x86" => "\xCE\xB1",
      /* U+1F07 */ "\xE1\xBC\x87" => "\xCE\xB1",
      /* U+1F08 */ "\xE1\xBC\x88" => "\xCE\xB1",
      /* U+1F09 */ "\xE1\xBC\x89" => "\xCE\xB1",
      /* U+1F0A */ "\xE1\xBC\x8A" => "\xCE\xB1",
      /* U+1F0B */ "\xE1\xBC\x8B" => "\xCE\xB1",
      /* U+1F0C */ "\xE1\xBC\x8C" => "\xCE\xB1",
      /* U+1F0D */ "\xE1\xBC\x8D" => "\xCE\xB1",
      /* U+1F0E */ "\xE1\xBC\x8E" => "\xCE\xB1",
      /* U+1F0F */ "\xE1\xBC\x8F" => "\xCE\xB1",
      /* U+1F10 */ "\xE1\xBC\x90" => "\xCE\xB5",
      /* U+1F11 */ "\xE1\xBC\x91" => "\xCE\xB5",
      /* U+1F12 */ "\xE1\xBC\x92" => "\xCE\xB5",
      /* U+1F13 */ "\xE1\xBC\x93" => "\xCE\xB5",
      /* U+1F14 */ "\xE1\xBC\x94" => "\xCE\xB5",
      /* U+1F15 */ "\xE1\xBC\x95" => "\xCE\xB5",
      /* U+1F18 */ "\xE1\xBC\x98" => "\xCE\xB5",
      /* U+1F19 */ "\xE1\xBC\x99" => "\xCE\xB5",
      /* U+1F1A */ "\xE1\xBC\x9A" => "\xCE\xB5",
      /* U+1F1B */ "\xE1\xBC\x9B" => "\xCE\xB5",
      /* U+1F1C */ "\xE1\xBC\x9C" => "\xCE\xB5",
      /* U+1F1D */ "\xE1\xBC\x9D" => "\xCE\xB5",
      /* U+1F20 */ "\xE1\xBC\xA0" => "\xCE\xB7",
      /* U+1F21 */ "\xE1\xBC\xA1" => "\xCE\xB7",
      /* U+1F22 */ "\xE1\xBC\xA2" => "\xCE\xB7",
      /* U+1F23 */ "\xE1\xBC\xA3" => "\xCE\xB7",
      /* U+1F24 */ "\xE1\xBC\xA4" => "\xCE\xB7",
      /* U+1F25 */ "\xE1\xBC\xA5" => "\xCE\xB7",
      /* U+1F26 */ "\xE1\xBC\xA6" => "\xCE\xB7",
      /* U+1F27 */ "\xE1\xBC\xA7" => "\xCE\xB7",
      /* U+1F28 */ "\xE1\xBC\xA8" => "\xCE\xB7",
      /* U+1F29 */ "\xE1\xBC\xA9" => "\xCE\xB7",
      /* U+1F2A */ "\xE1\xBC\xAA" => "\xCE\xB7",
      /* U+1F2B */ "\xE1\xBC\xAB" => "\xCE\xB7",
      /* U+1F2C */ "\xE1\xBC\xAC" => "\xCE\xB7",
      /* U+1F2D */ "\xE1\xBC\xAD" => "\xCE\xB7",
      /* U+1F2E */ "\xE1\xBC\xAE" => "\xCE\xB7",
      /* U+1F2F */ "\xE1\xBC\xAF" => "\xCE\xB7",
      /* U+1F30 */ "\xE1\xBC\xB0" => "\xCE\xB9",
      /* U+1F31 */ "\xE1\xBC\xB1" => "\xCE\xB9",
      /* U+1F32 */ "\xE1\xBC\xB2" => "\xCE\xB9",
      /* U+1F33 */ "\xE1\xBC\xB3" => "\xCE\xB9",
      /* U+1F34 */ "\xE1\xBC\xB4" => "\xCE\xB9",
      /* U+1F35 */ "\xE1\xBC\xB5" => "\xCE\xB9",
      /* U+1F36 */ "\xE1\xBC\xB6" => "\xCE\xB9",
      /* U+1F37 */ "\xE1\xBC\xB7" => "\xCE\xB9",
      /* U+1F38 */ "\xE1\xBC\xB8" => "\xCE\xB9",
      /* U+1F39 */ "\xE1\xBC\xB9" => "\xCE\xB9",
      /* U+1F3A */ "\xE1\xBC\xBA" => "\xCE\xB9",
      /* U+1F3B */ "\xE1\xBC\xBB" => "\xCE\xB9",
      /* U+1F3C */ "\xE1\xBC\xBC" => "\xCE\xB9",
      /* U+1F3D */ "\xE1\xBC\xBD" => "\xCE\xB9",
      /* U+1F3E */ "\xE1\xBC\xBE" => "\xCE\xB9",
      /* U+1F3F */ "\xE1\xBC\xBF" => "\xCE\xB9",
      /* U+1F40 */ "\xE1\xBD\x80" => "\xCE\xBF",
      /* U+1F41 */ "\xE1\xBD\x81" => "\xCE\xBF",
      /* U+1F42 */ "\xE1\xBD\x82" => "\xCE\xBF",
      /* U+1F43 */ "\xE1\xBD\x83" => "\xCE\xBF",
      /* U+1F44 */ "\xE1\xBD\x84" => "\xCE\xBF",
      /* U+1F45 */ "\xE1\xBD\x85" => "\xCE\xBF",
      /* U+1F48 */ "\xE1\xBD\x88" => "\xCE\xBF",
      /* U+1F49 */ "\xE1\xBD\x89" => "\xCE\xBF",
      /* U+1F4A */ "\xE1\xBD\x8A" => "\xCE\xBF",
      /* U+1F4B */ "\xE1\xBD\x8B" => "\xCE\xBF",
      /* U+1F4C */ "\xE1\xBD\x8C" => "\xCE\xBF",
      /* U+1F4D */ "\xE1\xBD\x8D" => "\xCE\xBF",
      /* U+1F50 */ "\xE1\xBD\x90" => "\xCF\x85",
      /* U+1F51 */ "\xE1\xBD\x91" => "\xCF\x85",
      /* U+1F52 */ "\xE1\xBD\x92" => "\xCF\x85",
      /* U+1F53 */ "\xE1\xBD\x93" => "\xCF\x85",
      /* U+1F54 */ "\xE1\xBD\x94" => "\xCF\x85",
      /* U+1F55 */ "\xE1\xBD\x95" => "\xCF\x85",
      /* U+1F56 */ "\xE1\xBD\x96" => "\xCF\x85",
      /* U+1F57 */ "\xE1\xBD\x97" => "\xCF\x85",
      /* U+1F59 */ "\xE1\xBD\x99" => "\xCF\x85",
      /* U+1F5B */ "\xE1\xBD\x9B" => "\xCF\x85",
      /* U+1F5D */ "\xE1\xBD\x9D" => "\xCF\x85",
      /* U+1F5F */ "\xE1\xBD\x9F" => "\xCF\x85",
      /* U+1F60 */ "\xE1\xBD\xA0" => "\xCF\x89",
      /* U+1F61 */ "\xE1\xBD\xA1" => "\xCF\x89",
      /* U+1F62 */ "\xE1\xBD\xA2" => "\xCF\x89",
      /* U+1F63 */ "\xE1\xBD\xA3" => "\xCF\x89",
      /* U+1F64 */ "\xE1\xBD\xA4" => "\xCF\x89",
      /* U+1F65 */ "\xE1\xBD\xA5" => "\xCF\x89",
      /* U+1F66 */ "\xE1\xBD\xA6" => "\xCF\x89",
      /* U+1F67 */ "\xE1\xBD\xA7" => "\xCF\x89",
      /* U+1F68 */ "\xE1\xBD\xA8" => "\xCF\x89",
      /* U+1F69 */ "\xE1\xBD\xA9" => "\xCF\x89",
      /* U+1F6A */ "\xE1\xBD\xAA" => "\xCF\x89",
      /* U+1F6B */ "\xE1\xBD\xAB" => "\xCF\x89",
      /* U+1F6C */ "\xE1\xBD\xAC" => "\xCF\x89",
      /* U+1F6D */ "\xE1\xBD\xAD" => "\xCF\x89",
      /* U+1F6E */ "\xE1\xBD\xAE" => "\xCF\x89",
      /* U+1F6F */ "\xE1\xBD\xAF" => "\xCF\x89",
      /* U+1F70 */ "\xE1\xBD\xB0" => "\xCE\xB1",
      /* U+1F71 */ "\xE1\xBD\xB1" => "\xCE\xB1",
      /* U+1F72 */ "\xE1\xBD\xB2" => "\xCE\xB5",
      /* U+1F73 */ "\xE1\xBD\xB3" => "\xCE\xB5",
      /* U+1F74 */ "\xE1\xBD\xB4" => "\xCE\xB7",
      /* U+1F75 */ "\xE1\xBD\xB5" => "\xCE\xB7",
      /* U+1F76 */ "\xE1\xBD\xB6" => "\xCE\xB9",
      /* U+1F77 */ "\xE1\xBD\xB7" => "\xCE\xB9",
      /* U+1F78 */ "\xE1\xBD\xB8" => "\xCE\xBF",
      /* U+1F79 */ "\xE1\xBD\xB9" => "\xCE\xBF",
      /* U+1F7A */ "\xE1\xBD\xBA" => "\xCF\x85",
      /* U+1F7B */ "\xE1\xBD\xBB" => "\xCF\x85",
      /* U+1F7C */ "\xE1\xBD\xBC" => "\xCF\x89",
      /* U+1F7D */ "\xE1\xBD\xBD" => "\xCF\x89",
      /* U+1F80 */ "\xE1\xBE\x80" => "\xCE\xB1",
      /* U+1F81 */ "\xE1\xBE\x81" => "\xCE\xB1",
      /* U+1F82 */ "\xE1\xBE\x82" => "\xCE\xB1",
      /* U+1F83 */ "\xE1\xBE\x83" => "\xCE\xB1",
      /* U+1F84 */ "\xE1\xBE\x84" => "\xCE\xB1",
      /* U+1F85 */ "\xE1\xBE\x85" => "\xCE\xB1",
      /* U+1F86 */ "\xE1\xBE\x86" => "\xCE\xB1",
      /* U+1F87 */ "\xE1\xBE\x87" => "\xCE\xB1",
      /* U+1F88 */ "\xE1\xBE\x88" => "\xCE\xB1",
      /* U+1F89 */ "\xE1\xBE\x89" => "\xCE\xB1",
      /* U+1F8A */ "\xE1\xBE\x8A" => "\xCE\xB1",
      /* U+1F8B */ "\xE1\xBE\x8B" => "\xCE\xB1",
      /* U+1F8C */ "\xE1\xBE\x8C" => "\xCE\xB1",
      /* U+1F8D */ "\xE1\xBE\x8D" => "\xCE\xB1",
      /* U+1F8E */ "\xE1\xBE\x8E" => "\xCE\xB1",
      /* U+1F8F */ "\xE1\xBE\x8F" => "\xCE\xB1",
      /* U+1F90 */ "\xE1\xBE\x90" => "\xCE\xB7",
      /* U+1F91 */ "\xE1\xBE\x91" => "\xCE\xB7",
      /* U+1F92 */ "\xE1\xBE\x92" => "\xCE\xB7",
      /* U+1F93 */ "\xE1\xBE\x93" => "\xCE\xB7",
      /* U+1F94 */ "\xE1\xBE\x94" => "\xCE\xB7",
      /* U+1F95 */ "\xE1\xBE\x95" => "\xCE\xB7",
      /* U+1F96 */ "\xE1\xBE\x96" => "\xCE\xB7",
      /* U+1F97 */ "\xE1\xBE\x97" => "\xCE\xB7",
      /* U+1F98 */ "\xE1\xBE\x98" => "\xCE\xB7",
      /* U+1F99 */ "\xE1\xBE\x99" => "\xCE\xB7",
      /* U+1F9A */ "\xE1\xBE\x9A" => "\xCE\xB7",
      /* U+1F9B */ "\xE1\xBE\x9B" => "\xCE\xB7",
      /* U+1F9C */ "\xE1\xBE\x9C" => "\xCE\xB7",
      /* U+1F9D */ "\xE1\xBE\x9D" => "\xCE\xB7",
      /* U+1F9E */ "\xE1\xBE\x9E" => "\xCE\xB7",
      /* U+1F9F */ "\xE1\xBE\x9F" => "\xCE\xB7",
      /* U+1FA0 */ "\xE1\xBE\xA0" => "\xCF\x89",
      /* U+1FA1 */ "\xE1\xBE\xA1" => "\xCF\x89",
      /* U+1FA2 */ "\xE1\xBE\xA2" => "\xCF\x89",
      /* U+1FA3 */ "\xE1\xBE\xA3" => "\xCF\x89",
      /* U+1FA4 */ "\xE1\xBE\xA4" => "\xCF\x89",
      /* U+1FA5 */ "\xE1\xBE\xA5" => "\xCF\x89",
      /* U+1FA6 */ "\xE1\xBE\xA6" => "\xCF\x89",
      /* U+1FA7 */ "\xE1\xBE\xA7" => "\xCF\x89",
      /* U+1FA8 */ "\xE1\xBE\xA8" => "\xCF\x89",
      /* U+1FA9 */ "\xE1\xBE\xA9" => "\xCF\x89",
      /* U+1FAA */ "\xE1\xBE\xAA" => "\xCF\x89",
      /* U+1FAB */ "\xE1\xBE\xAB" => "\xCF\x89",
      /* U+1FAC */ "\xE1\xBE\xAC" => "\xCF\x89",
      /* U+1FAD */ "\xE1\xBE\xAD" => "\xCF\x89",
      /* U+1FAE */ "\xE1\xBE\xAE" => "\xCF\x89",
      /* U+1FAF */ "\xE1\xBE\xAF" => "\xCF\x89",
      /* U+1FB0 */ "\xE1\xBE\xB0" => "\xCE\xB1",
      /* U+1FB1 */ "\xE1\xBE\xB1" => "\xCE\xB1",
      /* U+1FB2 */ "\xE1\xBE\xB2" => "\xCE\xB1",
      /* U+1FB3 */ "\xE1\xBE\xB3" => "\xCE\xB1",
      /* U+1FB4 */ "\xE1\xBE\xB4" => "\xCE\xB1",
      /* U+1FB6 */ "\xE1\xBE\xB6" => "\xCE\xB1",
      /* U+1FB7 */ "\xE1\xBE\xB7" => "\xCE\xB1",
      /* U+1FB8 */ "\xE1\xBE\xB8" => "\xCE\xB1",
      /* U+1FB9 */ "\xE1\xBE\xB9" => "\xCE\xB1",
      /* U+1FBA */ "\xE1\xBE\xBA" => "\xCE\xB1",
      /* U+1FBB */ "\xE1\xBE\xBB" => "\xCE\xB1",
      /* U+1FBC */ "\xE1\xBE\xBC" => "\xCE\xB1",
      /* U+1FC2 */ "\xE1\xBF\x82" => "\xCE\xB7",
      /* U+1FC3 */ "\xE1\xBF\x83" => "\xCE\xB7",
      /* U+1FC4 */ "\xE1\xBF\x84" => "\xCE\xB7",
      /* U+1FC6 */ "\xE1\xBF\x86" => "\xCE\xB7",
      /* U+1FC7 */ "\xE1\xBF\x87" => "\xCE\xB7",
      /* U+1FC8 */ "\xE1\xBF\x88" => "\xCE\xB5",
      /* U+1FC9 */ "\xE1\xBF\x89" => "\xCE\xB5",
      /* U+1FCA */ "\xE1\xBF\x8A" => "\xCE\xB7",
      /* U+1FCB */ "\xE1\xBF\x8B" => "\xCE\xB7",
      /* U+1FCC */ "\xE1\xBF\x8C" => "\xCE\xB7",
      /* U+1FD0 */ "\xE1\xBF\x90" => "\xCE\xB9",
      /* U+1FD1 */ "\xE1\xBF\x91" => "\xCE\xB9",
      /* U+1FD2 */ "\xE1\xBF\x92" => "\xCE\xB9",
      /* U+1FD3 */ "\xE1\xBF\x93" => "\xCE\xB9",
      /* U+1FD6 */ "\xE1\xBF\x96" => "\xCE\xB9",
      /* U+1FD7 */ "\xE1\xBF\x97" => "\xCE\xB9",
      /* U+1FD8 */ "\xE1\xBF\x98" => "\xCE\xB9",
      /* U+1FD9 */ "\xE1\xBF\x99" => "\xCE\xB9",
      /* U+1FDA */ "\xE1\xBF\x9A" => "\xCE\xB9",
      /* U+1FDB */ "\xE1\xBF\x9B" => "\xCE\xB9",
      /* U+1FE0 */ "\xE1\xBF\xA0" => "\xCF\x85",
      /* U+1FE1 */ "\xE1\xBF\xA1" => "\xCF\x85",
      /* U+1FE2 */ "\xE1\xBF\xA2" => "\xCF\x85",
      /* U+1FE3 */ "\xE1\xBF\xA3" => "\xCF\x85",
      /* U+1FE4 */ "\xE1\xBF\xA4" => "\xCF\x81",
      /* U+1FE5 */ "\xE1\xBF\xA5" => "\xCF\x81",
      /* U+1FE6 */ "\xE1\xBF\xA6" => "\xCF\x85",
      /* U+1FE7 */ "\xE1\xBF\xA7" => "\xCF\x85",
      /* U+1FE8 */ "\xE1\xBF\xA8" => "\xCF\x85",
      /* U+1FE9 */ "\xE1\xBF\xA9" => "\xCF\x85",
      /* U+1FEA */ "\xE1\xBF\xAA" => "\xCF\x85",
      /* U+1FEB */ "\xE1\xBF\xAB" => "\xCF\x85",
      /* U+1FEC */ "\xE1\xBF\xAC" => "\xCF\x81",
      /* U+1FF2 */ "\xE1\xBF\xB2" => "\xCF\x89",
      /* U+1FF3 */ "\xE1\xBF\xB3" => "\xCF\x89",
      /* U+1FF4 */ "\xE1\xBF\xB4" => "\xCF\x89",
      /* U+1FF6 */ "\xE1\xBF\xB6" => "\xCF\x89",
      /* U+1FF7 */ "\xE1\xBF\xB7" => "\xCF\x89",
      /* U+1FF8 */ "\xE1\xBF\xB8" => "\xCE\xBF",
      /* U+1FF9 */ "\xE1\xBF\xB9" => "\xCE\xBF",
      /* U+1FFA */ "\xE1\xBF\xBA" => "\xCF\x89",
      /* U+1FFB */ "\xE1\xBF\xBB" => "\xCF\x89",
      /* U+1FFC */ "\xE1\xBF\xBC" => "\xCF\x89",
      //------  U+2C00..U+2C5F : Glagolitic
      /* U+2C00 */ "\xE2\xB0\x80" => "\xE2\xB0\xB0",
      /* U+2C01 */ "\xE2\xB0\x81" => "\xE2\xB0\xB1",
      /* U+2C02 */ "\xE2\xB0\x82" => "\xE2\xB0\xB2",
      /* U+2C03 */ "\xE2\xB0\x83" => "\xE2\xB0\xB3",
      /* U+2C04 */ "\xE2\xB0\x84" => "\xE2\xB0\xB4",
      /* U+2C05 */ "\xE2\xB0\x85" => "\xE2\xB0\xB5",
      /* U+2C06 */ "\xE2\xB0\x86" => "\xE2\xB0\xB6",
      /* U+2C07 */ "\xE2\xB0\x87" => "\xE2\xB0\xB7",
      /* U+2C08 */ "\xE2\xB0\x88" => "\xE2\xB0\xB8",
      /* U+2C09 */ "\xE2\xB0\x89" => "\xE2\xB0\xB9",
      /* U+2C0A */ "\xE2\xB0\x8A" => "\xE2\xB0\xBA",
      /* U+2C0B */ "\xE2\xB0\x8B" => "\xE2\xB0\xBB",
      /* U+2C0C */ "\xE2\xB0\x8C" => "\xE2\xB0\xBC",
      /* U+2C0D */ "\xE2\xB0\x8D" => "\xE2\xB0\xBD",
      /* U+2C0E */ "\xE2\xB0\x8E" => "\xE2\xB0\xBE",
      /* U+2C0F */ "\xE2\xB0\x8F" => "\xE2\xB0\xBF",
      /* U+2C10 */ "\xE2\xB0\x90" => "\xE2\xB1\x80",
      /* U+2C11 */ "\xE2\xB0\x91" => "\xE2\xB1\x81",
      /* U+2C12 */ "\xE2\xB0\x92" => "\xE2\xB1\x82",
      /* U+2C13 */ "\xE2\xB0\x93" => "\xE2\xB1\x83",
      /* U+2C14 */ "\xE2\xB0\x94" => "\xE2\xB1\x84",
      /* U+2C15 */ "\xE2\xB0\x95" => "\xE2\xB1\x85",
      /* U+2C16 */ "\xE2\xB0\x96" => "\xE2\xB1\x86",
      /* U+2C17 */ "\xE2\xB0\x97" => "\xE2\xB1\x87",
      /* U+2C18 */ "\xE2\xB0\x98" => "\xE2\xB1\x88",
      /* U+2C19 */ "\xE2\xB0\x99" => "\xE2\xB1\x89",
      /* U+2C1A */ "\xE2\xB0\x9A" => "\xE2\xB1\x8A",
      /* U+2C1B */ "\xE2\xB0\x9B" => "\xE2\xB1\x8B",
      /* U+2C1C */ "\xE2\xB0\x9C" => "\xE2\xB1\x8C",
      /* U+2C1D */ "\xE2\xB0\x9D" => "\xE2\xB1\x8D",
      /* U+2C1E */ "\xE2\xB0\x9E" => "\xE2\xB1\x8E",
      /* U+2C1F */ "\xE2\xB0\x9F" => "\xE2\xB1\x8F",
      /* U+2C20 */ "\xE2\xB0\xA0" => "\xE2\xB1\x90",
      /* U+2C21 */ "\xE2\xB0\xA1" => "\xE2\xB1\x91",
      /* U+2C22 */ "\xE2\xB0\xA2" => "\xE2\xB1\x92",
      /* U+2C23 */ "\xE2\xB0\xA3" => "\xE2\xB1\x93",
      /* U+2C24 */ "\xE2\xB0\xA4" => "\xE2\xB1\x94",
      /* U+2C25 */ "\xE2\xB0\xA5" => "\xE2\xB1\x95",
      /* U+2C26 */ "\xE2\xB0\xA6" => "\xE2\xB1\x96",
      /* U+2C27 */ "\xE2\xB0\xA7" => "\xE2\xB1\x97",
      /* U+2C28 */ "\xE2\xB0\xA8" => "\xE2\xB1\x98",
      /* U+2C29 */ "\xE2\xB0\xA9" => "\xE2\xB1\x99",
      /* U+2C2A */ "\xE2\xB0\xAA" => "\xE2\xB1\x9A",
      /* U+2C2B */ "\xE2\xB0\xAB" => "\xE2\xB1\x9B",
      /* U+2C2C */ "\xE2\xB0\xAC" => "\xE2\xB1\x9C",
      /* U+2C2D */ "\xE2\xB0\xAD" => "\xE2\xB1\x9D",
      /* U+2C2E */ "\xE2\xB0\xAE" => "\xE2\xB1\x9E",
      //------  U+2C80..U+2CFF : Coptic
      /* U+2C80 */ "\xE2\xB2\x80" => "\xE2\xB2\x81",
      /* U+2C82 */ "\xE2\xB2\x82" => "\xE2\xB2\x83",
      /* U+2C84 */ "\xE2\xB2\x84" => "\xE2\xB2\x85",
      /* U+2C86 */ "\xE2\xB2\x86" => "\xE2\xB2\x87",
      /* U+2C88 */ "\xE2\xB2\x88" => "\xE2\xB2\x89",
      /* U+2C8A */ "\xE2\xB2\x8A" => "\xE2\xB2\x8B",
      /* U+2C8C */ "\xE2\xB2\x8C" => "\xE2\xB2\x8D",
      /* U+2C8E */ "\xE2\xB2\x8E" => "\xE2\xB2\x8F",
      /* U+2C90 */ "\xE2\xB2\x90" => "\xE2\xB2\x91",
      /* U+2C92 */ "\xE2\xB2\x92" => "\xE2\xB2\x93",
      /* U+2C94 */ "\xE2\xB2\x94" => "\xE2\xB2\x95",
      /* U+2C96 */ "\xE2\xB2\x96" => "\xE2\xB2\x97",
      /* U+2C98 */ "\xE2\xB2\x98" => "\xE2\xB2\x99",
      /* U+2C9A */ "\xE2\xB2\x9A" => "\xE2\xB2\x9B",
      /* U+2C9C */ "\xE2\xB2\x9C" => "\xE2\xB2\x9D",
      /* U+2C9E */ "\xE2\xB2\x9E" => "\xE2\xB2\x9F",
      /* U+2CA0 */ "\xE2\xB2\xA0" => "\xE2\xB2\xA1",
      /* U+2CA2 */ "\xE2\xB2\xA2" => "\xE2\xB2\xA3",
      /* U+2CA4 */ "\xE2\xB2\xA4" => "\xE2\xB2\xA5",
      /* U+2CA6 */ "\xE2\xB2\xA6" => "\xE2\xB2\xA7",
      /* U+2CA8 */ "\xE2\xB2\xA8" => "\xE2\xB2\xA9",
      /* U+2CAA */ "\xE2\xB2\xAA" => "\xE2\xB2\xAB",
      /* U+2CAC */ "\xE2\xB2\xAC" => "\xE2\xB2\xAD",
      /* U+2CAE */ "\xE2\xB2\xAE" => "\xE2\xB2\xAF",
      /* U+2CB0 */ "\xE2\xB2\xB0" => "\xE2\xB2\xB1",
      /* U+2CB2 */ "\xE2\xB2\xB2" => "\xE2\xB2\xB3",
      /* U+2CB4 */ "\xE2\xB2\xB4" => "\xE2\xB2\xB5",
      /* U+2CB6 */ "\xE2\xB2\xB6" => "\xE2\xB2\xB7",
      /* U+2CB8 */ "\xE2\xB2\xB8" => "\xE2\xB2\xB9",
      /* U+2CBA */ "\xE2\xB2\xBA" => "\xE2\xB2\xBB",
      /* U+2CBC */ "\xE2\xB2\xBC" => "\xE2\xB2\xBD",
      /* U+2CBE */ "\xE2\xB2\xBE" => "\xE2\xB2\xBF",
      /* U+2CC0 */ "\xE2\xB3\x80" => "\xE2\xB3\x81",
      /* U+2CC2 */ "\xE2\xB3\x82" => "\xE2\xB3\x83",
      /* U+2CC4 */ "\xE2\xB3\x84" => "\xE2\xB3\x85",
      /* U+2CC6 */ "\xE2\xB3\x86" => "\xE2\xB3\x87",
      /* U+2CC8 */ "\xE2\xB3\x88" => "\xE2\xB3\x89",
      /* U+2CCA */ "\xE2\xB3\x8A" => "\xE2\xB3\x8B",
      /* U+2CCC */ "\xE2\xB3\x8C" => "\xE2\xB3\x8D",
      /* U+2CCE */ "\xE2\xB3\x8E" => "\xE2\xB3\x8F",
      /* U+2CD0 */ "\xE2\xB3\x90" => "\xE2\xB3\x91",
      /* U+2CD2 */ "\xE2\xB3\x92" => "\xE2\xB3\x93",
      /* U+2CD4 */ "\xE2\xB3\x94" => "\xE2\xB3\x95",
      /* U+2CD6 */ "\xE2\xB3\x96" => "\xE2\xB3\x97",
      /* U+2CD8 */ "\xE2\xB3\x98" => "\xE2\xB3\x99",
      /* U+2CDA */ "\xE2\xB3\x9A" => "\xE2\xB3\x9B",
      /* U+2CDC */ "\xE2\xB3\x9C" => "\xE2\xB3\x9D",
      /* U+2CDE */ "\xE2\xB3\x9E" => "\xE2\xB3\x9F",
      /* U+2CE0 */ "\xE2\xB3\xA0" => "\xE2\xB3\xA1",
      /* U+2CE2 */ "\xE2\xB3\xA2" => "\xE2\xB3\xA3",
      //------  U+FB00..U+FB4F : Alphabetic Presentation Forms
      /* U+FB1D */ "\xEF\xAC\x9D" => "\xD7\x99",
      /* U+FB2A */ "\xEF\xAC\xAA" => "\xD7\xA9",
      /* U+FB2B */ "\xEF\xAC\xAB" => "\xD7\xA9",
      /* U+FB2C */ "\xEF\xAC\xAC" => "\xD7\xA9",
      /* U+FB2D */ "\xEF\xAC\xAD" => "\xD7\xA9",
      /* U+FB2E */ "\xEF\xAC\xAE" => "\xD7\x90",
      /* U+FB2F */ "\xEF\xAC\xAF" => "\xD7\x90",
      /* U+FB30 */ "\xEF\xAC\xB0" => "\xD7\x90",
      /* U+FB31 */ "\xEF\xAC\xB1" => "\xD7\x91",
      /* U+FB32 */ "\xEF\xAC\xB2" => "\xD7\x92",
      /* U+FB33 */ "\xEF\xAC\xB3" => "\xD7\x93",
      /* U+FB34 */ "\xEF\xAC\xB4" => "\xD7\x94",
      /* U+FB35 */ "\xEF\xAC\xB5" => "\xD7\x95",
      /* U+FB36 */ "\xEF\xAC\xB6" => "\xD7\x96",
      /* U+FB38 */ "\xEF\xAC\xB8" => "\xD7\x98",
      /* U+FB39 */ "\xEF\xAC\xB9" => "\xD7\x99",
      /* U+FB3A */ "\xEF\xAC\xBA" => "\xD7\x9A",
      /* U+FB3B */ "\xEF\xAC\xBB" => "\xD7\x9B",
      /* U+FB3C */ "\xEF\xAC\xBC" => "\xD7\x9C",
      /* U+FB3E */ "\xEF\xAC\xBE" => "\xD7\x9E",
      /* U+FB40 */ "\xEF\xAD\x80" => "\xD7\xA0",
      /* U+FB41 */ "\xEF\xAD\x81" => "\xD7\xA1",
      /* U+FB43 */ "\xEF\xAD\x83" => "\xD7\xA3",
      /* U+FB44 */ "\xEF\xAD\x84" => "\xD7\xA4",
      /* U+FB46 */ "\xEF\xAD\x86" => "\xD7\xA6",
      /* U+FB47 */ "\xEF\xAD\x87" => "\xD7\xA7",
      /* U+FB48 */ "\xEF\xAD\x88" => "\xD7\xA8",
      /* U+FB49 */ "\xEF\xAD\x89" => "\xD7\xA9",
      /* U+FB4A */ "\xEF\xAD\x8A" => "\xD7\xAA",
      /* U+FB4B */ "\xEF\xAD\x8B" => "\xD7\x95",
      /* U+FB4C */ "\xEF\xAD\x8C" => "\xD7\x91",
      /* U+FB4D */ "\xEF\xAD\x8D" => "\xD7\x9B",
      /* U+FB4E */ "\xEF\xAD\x8E" => "\xD7\xA4",
      //------  U+FF00..U+FFEF : Halfwidth and Fullwidth Forms
      /* U+FF21 */ "\xEF\xBC\xA1" => "\xEF\xBD\x81",
      /* U+FF22 */ "\xEF\xBC\xA2" => "\xEF\xBD\x82",
      /* U+FF23 */ "\xEF\xBC\xA3" => "\xEF\xBD\x83",
      /* U+FF24 */ "\xEF\xBC\xA4" => "\xEF\xBD\x84",
      /* U+FF25 */ "\xEF\xBC\xA5" => "\xEF\xBD\x85",
      /* U+FF26 */ "\xEF\xBC\xA6" => "\xEF\xBD\x86",
      /* U+FF27 */ "\xEF\xBC\xA7" => "\xEF\xBD\x87",
      /* U+FF28 */ "\xEF\xBC\xA8" => "\xEF\xBD\x88",
      /* U+FF29 */ "\xEF\xBC\xA9" => "\xEF\xBD\x89",
      /* U+FF2A */ "\xEF\xBC\xAA" => "\xEF\xBD\x8A",
      /* U+FF2B */ "\xEF\xBC\xAB" => "\xEF\xBD\x8B",
      /* U+FF2C */ "\xEF\xBC\xAC" => "\xEF\xBD\x8C",
      /* U+FF2D */ "\xEF\xBC\xAD" => "\xEF\xBD\x8D",
      /* U+FF2E */ "\xEF\xBC\xAE" => "\xEF\xBD\x8E",
      /* U+FF2F */ "\xEF\xBC\xAF" => "\xEF\xBD\x8F",
      /* U+FF30 */ "\xEF\xBC\xB0" => "\xEF\xBD\x90",
      /* U+FF31 */ "\xEF\xBC\xB1" => "\xEF\xBD\x91",
      /* U+FF32 */ "\xEF\xBC\xB2" => "\xEF\xBD\x92",
      /* U+FF33 */ "\xEF\xBC\xB3" => "\xEF\xBD\x93",
      /* U+FF34 */ "\xEF\xBC\xB4" => "\xEF\xBD\x94",
      /* U+FF35 */ "\xEF\xBC\xB5" => "\xEF\xBD\x95",
      /* U+FF36 */ "\xEF\xBC\xB6" => "\xEF\xBD\x96",
      /* U+FF37 */ "\xEF\xBC\xB7" => "\xEF\xBD\x97",
      /* U+FF38 */ "\xEF\xBC\xB8" => "\xEF\xBD\x98",
      /* U+FF39 */ "\xEF\xBC\xB9" => "\xEF\xBD\x99",
      /* U+FF3A */ "\xEF\xBC\xBA" => "\xEF\xBD\x9A",
      //------  U+10400..U+1044F : Deseret
      /* U+10400 */ "\xF0\x90\x90\x80" => "\xF0\x90\x90\xA8",
      /* U+10401 */ "\xF0\x90\x90\x81" => "\xF0\x90\x90\xA9",
      /* U+10402 */ "\xF0\x90\x90\x82" => "\xF0\x90\x90\xAA",
      /* U+10403 */ "\xF0\x90\x90\x83" => "\xF0\x90\x90\xAB",
      /* U+10404 */ "\xF0\x90\x90\x84" => "\xF0\x90\x90\xAC",
      /* U+10405 */ "\xF0\x90\x90\x85" => "\xF0\x90\x90\xAD",
      /* U+10406 */ "\xF0\x90\x90\x86" => "\xF0\x90\x90\xAE",
      /* U+10407 */ "\xF0\x90\x90\x87" => "\xF0\x90\x90\xAF",
      /* U+10408 */ "\xF0\x90\x90\x88" => "\xF0\x90\x90\xB0",
      /* U+10409 */ "\xF0\x90\x90\x89" => "\xF0\x90\x90\xB1",
      /* U+1040A */ "\xF0\x90\x90\x8A" => "\xF0\x90\x90\xB2",
      /* U+1040B */ "\xF0\x90\x90\x8B" => "\xF0\x90\x90\xB3",
      /* U+1040C */ "\xF0\x90\x90\x8C" => "\xF0\x90\x90\xB4",
      /* U+1040D */ "\xF0\x90\x90\x8D" => "\xF0\x90\x90\xB5",
      /* U+1040E */ "\xF0\x90\x90\x8E" => "\xF0\x90\x90\xB6",
      /* U+1040F */ "\xF0\x90\x90\x8F" => "\xF0\x90\x90\xB7",
      /* U+10410 */ "\xF0\x90\x90\x90" => "\xF0\x90\x90\xB8",
      /* U+10411 */ "\xF0\x90\x90\x91" => "\xF0\x90\x90\xB9",
      /* U+10412 */ "\xF0\x90\x90\x92" => "\xF0\x90\x90\xBA",
      /* U+10413 */ "\xF0\x90\x90\x93" => "\xF0\x90\x90\xBB",
      /* U+10414 */ "\xF0\x90\x90\x94" => "\xF0\x90\x90\xBC",
      /* U+10415 */ "\xF0\x90\x90\x95" => "\xF0\x90\x90\xBD",
      /* U+10416 */ "\xF0\x90\x90\x96" => "\xF0\x90\x90\xBE",
      /* U+10417 */ "\xF0\x90\x90\x97" => "\xF0\x90\x90\xBF",
      /* U+10418 */ "\xF0\x90\x90\x98" => "\xF0\x90\x91\x80",
      /* U+10419 */ "\xF0\x90\x90\x99" => "\xF0\x90\x91\x81",
      /* U+1041A */ "\xF0\x90\x90\x9A" => "\xF0\x90\x91\x82",
      /* U+1041B */ "\xF0\x90\x90\x9B" => "\xF0\x90\x91\x83",
      /* U+1041C */ "\xF0\x90\x90\x9C" => "\xF0\x90\x91\x84",
      /* U+1041D */ "\xF0\x90\x90\x9D" => "\xF0\x90\x91\x85",
      /* U+1041E */ "\xF0\x90\x90\x9E" => "\xF0\x90\x91\x86",
      /* U+1041F */ "\xF0\x90\x90\x9F" => "\xF0\x90\x91\x87",
      /* U+10420 */ "\xF0\x90\x90\xA0" => "\xF0\x90\x91\x88",
      /* U+10421 */ "\xF0\x90\x90\xA1" => "\xF0\x90\x91\x89",
      /* U+10422 */ "\xF0\x90\x90\xA2" => "\xF0\x90\x91\x8A",
      /* U+10423 */ "\xF0\x90\x90\xA3" => "\xF0\x90\x91\x8B",
      /* U+10424 */ "\xF0\x90\x90\xA4" => "\xF0\x90\x91\x8C",
      /* U+10425 */ "\xF0\x90\x90\xA5" => "\xF0\x90\x91\x8D",
      /* U+10426 */ "\xF0\x90\x90\xA6" => "\xF0\x90\x91\x8E",
      /* U+10427 */ "\xF0\x90\x90\xA7" => "\xF0\x90\x91\x8F"
  );
  protected $endCharacters_utf8 = "\t\r\n !\"#\$%&'()+,-./:;<=>@[\]^_`{|}~£§¨°";

  public function get_indexer_bad_chars()
  {
    return $this->endCharacters_utf8;
  }

  public function has_indexer_bad_char($string)
  {
    return mb_strpos($this->endCharacters_utf8, $string);
  }

  public function remove_indexer_chars($string)
  {

    $so = "";

    $l = mb_strlen($string, "UTF-8");
    $lastwasblank = false;
    for ($i = 0; $i < $l; $i++)
    {
      $c = mb_substr($string, $i, 1, "UTF-8");
      $c = isset($this->map[$c]) ? $this->map[$c] : $c;
      if (mb_strpos($this->endCharacters_utf8, $c) !== FALSE)
      {
        $lastwasblank = true;
      }
      else
      {
        if ($lastwasblank && $so != "")
          $so .= " ";
        $so .= $c;
        $lastwasblank = false;
      }
    }

    return($so);
  }

  function remove_diacritics($string)
  {
    $no_diacritics = '';

    $l = mb_strlen($string);

    $regexp = '/[a-zA-Z0-9]{1}/';
    for ($i = 0; $i < $l; $i++)
    {
      $c = mb_substr($string, $i, 1);
      if (!preg_match($regexp, $c))
        $c = isset($this->map[$c]) ? $this->map[$c] : $c;
      $no_diacritics .= $c;
    }

    return $no_diacritics;
  }

  public function remove_nonazAZ09($string, $keep_underscores = true, $keep_minus = true)
  {
    $regexp = '/[a-zA-Z0-9';
    if ($keep_minus === true)
    {
      $regexp .= '-';
    }
    if ($keep_underscores === true)
    {
      $regexp .= '_';
    }
    $regexp .= ']{1}/';

    $string = $this->remove_diacritics($string);

    $out = '';

    $l = mb_strlen($string);
    for ($i = 0; $i < $l; $i++)
    {
      $c = mb_substr($string, $i, 1);
      if (preg_match($regexp, $c))
        $out .= $c;
    }

    return $out;
  }

  /**
   * Removes all digits a the begining of a string
   * @Example : returns 'soleil' for '123soleil' and 'bb2' for '1bb2'
   *
   * @param type $string
   * @return type
   */
  public function remove_first_digits($string)
  {
    while ($string != '' && ctype_digit($string[0]))
    {
      $string = substr($string, 1);
    }

    return $string;
  }

}



?>

