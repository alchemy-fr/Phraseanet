<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					 "dmin" 
					, "dmax" 
					, "baslst" 
					, "popbases"
					, "tbl"
					, "precise" 
					, "preciseWord" 
					, "preciseUser"
					, "page"
					, "limit"
					, "fonction"
					, "pays"
					, "societe"
					, "activite"
					, "on"
					,"docwhat"
					); 
		
extract($parm);

$lng = isset($session->locale)?$session->locale:GV_default_lng;
$conn = connection::getInstance();
	
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!$session->report)
	{
		header("Location: /client/");
		exit();
	}
}
else
{
	header("Location: /.GV_ServerName./login/report/");
	exit();
}

/*Initialise les dates par defaults min au 1er jour du mois courant et max a la date courante*/
if($parm['dmin']=="")
	$parm['dmin'] = "01-".date("m")."-".date("Y"); 
if($parm['dmax'] == "")
	$parm['dmax'] = date("d")."-".date("m")."-".date("Y");				
		
$td = explode("-",$parm['dmin']);
$parm['dmin'] = date('Y-m-d H:i:s', mktime(0,0,0, $td[1], $td[0], $td[2]));

$td = explode("-",$parm['dmax']);
$parm['dmax'] = date('Y-m-d H:i:s', mktime(23,59,59, $td[1], $td[0], $td[2]));

//get user's sbas & collections selection from popbases
$selection = array();
$popbases = array_fill_keys($popbases, 0);
$liste = '';
$i = 0;
$id_sbas = "";
foreach($popbases as $key => $val)
{
	$exp = explode("_", $key);
	if($exp[0] != $id_sbas && $i != 0)
	{
		$selection[$id_sbas]['liste'] = $liste;
		$liste = ''; 
	}
	$selection[$exp[0]][] = $exp[1];
	$liste .= (empty($liste) ? '' : ',').$exp[1] ; 
	$id_sbas = $exp[0];
	$i++;
}
//fill the last entry
$selection[$id_sbas]['liste'] = $liste;

$twig = new supertwig();
$twig->addFilter(array('sbas_names' => 'phrasea::sbas_names'));
$twig->display('report/ajax_report_content.twig', array('selection' => $selection, 'param' => $parm, 'anonymous' => GV_anonymousReport ,'ajax' => true));

?>