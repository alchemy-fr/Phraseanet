<?php

require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
$request = httpRequest::getInstance();
$parm = $request->get_parms('id');

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

$id = $parm['id'];

$dashboard = new dashboard($usr_id);

$var = array(
	'rs'		=> $dashboard->dashboard['activity_day'][$id],
	'legendDay' => $dashboard->legendDay,
	"sbas_id" => $id,
	'ajax_chart'=> true
);

$twig = new supertwig();

$twig->addFilter(array('serialize' => 'serialize', 'sbas_names' => 'phrasea::sbas_names', 'unite' => 'report::unite', 'stristr' => 'stristr'));
$html = $twig->render('report/chart.twig', $var);
$t = array( "rs" => $html);
echo json_encode($t);
?>