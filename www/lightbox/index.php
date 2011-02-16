<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('LOG');

if(isset($parm["LOG"])){

	$lng = isset($session->locale)?$session->locale:GV_default_lng;
	$logged = p4::signOnWithToken($parm['LOG']);
	
	if($logged['error'])
	{
		header("Location: /login/?error=".$logged['error']);
		exit();
	}	
	
	$datas = random::helloToken($parm['LOG']);
	$rssel_id = $datas['datas'];
	$rexp = $datas['expire_on'];
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		header("Location: /login/?error=".$logged['error']);
		exit();
	}
        header('Location: /lightbox/validate/'.$rssel_id.'/');
        exit;
}
else{
	$lng = isset($session->locale)?$session->locale:GV_default_lng;
	
	if(isset($session->usr_id) && isset($session->ses_id))
	{
		$ses_id = $session->ses_id;
		$usr_id = $session->usr_id;
		if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
		{
			header('/login/logout.php');
			exit();
		}
	}
	else{
		header("Location: /login/lightbox/");
		exit();
	}
	
	$rexp = 0;
}

user::updateClientInfos((6));


try
{
	$basket_collection = new basketCollection();
	
	$twig = new supertwig();
	$twig->addFilter(array('nl2br'=>'nl2br'));

	$browser = browser::getInstance();
	
	$template = 'lightbox/index.twig';
	if(!$browser->isNewGeneration())
		$template = 'lightbox/IE6/index.twig';
	
	$twig->display($template, array(
					'baskets_collection' 	=> $basket_collection,
					'module_name'			=> 'Lightbox',
					'module'				=> 'lightbox'
						)
				);
				
}
catch(Exception $e)
{
	echo $e;
}
