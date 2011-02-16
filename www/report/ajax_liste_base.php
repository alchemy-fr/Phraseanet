<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";


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
					); 
					
extract($parm);

$lng = isset($_SESSION['locale'])?$_SESSION['locale']:GV_default_lng;
$conn = connection::getInstance();
	
if(isset($_SESSION['usr_id']) && isset($_SESSION['ses_id']))
{
	$ses_id = $_SESSION['ses_id'];
	$usr_id = $_SESSION['usr_id'];
	if(!$_SESSION['report'])
	{
		header("Location: /client/");
		exit();
	}
}
else
{
	header("Location: /login/report/");
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

$twig = new supertwig(GV_RootPath.'www/report_mobile/templates/mobile', array('I18n' => true), array('debug' => true));
$twig->addFilter(array('sbas_names' => 'phrasea::sbas_names'));
$twig->display('liste_base.twig', array('selection' => $selection, 'param' => $parm ));

?>