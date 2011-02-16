<?php
    
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require(GV_RootPath.'lib/countries.php');

$conn = connection::getInstance();

$lng =  !isset($session->locale)?GV_default_lng:$session->locale;
	
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!$session->report || count($session->report) == 0)
		phrasea::headers(403);
}
else
{
	header("Location: /login/report/");
	exit();
}

phrasea::headers();

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();
	
user::updateClientInfos(4);	

///Get all collections where the current user is able to see the reporting
$lb = phrasea::bases();
$user = user::getInstance($usr_id);
$all_coll = array() ;

foreach($lb['bases'] as $sbas)
{
	foreach($sbas['collections'] as $coll)
	{
		if(!isset($user->_rights_bas[$coll['base_id']]) || !$user->_rights_bas[$coll['base_id']]['canreport'])
			continue;
		if(!isset($all_coll[$sbas['sbas_id']]))
		{
			$all_coll[$sbas['sbas_id']] = array();
			$all_coll[$sbas['sbas_id']]['name_sbas'] = phrasea::sbas_names($sbas['sbas_id']);
		}
		$all_coll[$sbas['sbas_id']]['sbas_collections'][] = array(
			'base_id' => $coll['base_id'],
			'sbas_id' => $sbas['sbas_id'],
			'coll_id'=>$coll['coll_id'],
			'name'=>phrasea::bas_names($coll['base_id'])
		);
	}
}



///////Construct dashboard
$dashboard = new dashboard($usr_id);



$var = array(
	'usr_id'		=> $usr_id,
	'dashboard_array'	=> $dashboard,
	'all_coll'	=> $all_coll,
	'home_title'	=> GV_homeTitle,
	'module'	=> "report",
	"module_name" => "Report",
	'anonymous' => GV_anonymousReport,
	'g_anal'	=> GV_googleAnalytics,
	'ajax'		=> false,
	'ajax_chart'=> false
);

$twig = new supertwig();

$twig->addFilter(array('serialize' => 'serialize', 'sbas_names' => 'phrasea::sbas_names', 'unite' => 'report::unite', 'stristr' => 'stristr'));
$twig->display('report/table_content.twig', $var);

?>
