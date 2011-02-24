<?php
include '../../lib/bootstrap.php';
$session = session::getInstance();

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

try
{
        user::updateClientInfos((6));
	$request = httpRequest::getInstance();
	$parm = $request->get_parms('ssel_id');
	
	$browser = browser::getInstance();
	
	$basket_collection = new basketCollection();
	$basket = basket::getInstance($parm['ssel_id']);
	
	if($basket->valid)
	{
          $first_elem = $basket->get_first_element();
          if($first_elem)
            $first_elem->load_users_infos();
	}

        if($basket->is_grouping)
                throw new Exception (_('Impossible d\'ouvrir un reportage dans lightbox'));
	
	$twig = new supertwig();
	
	$twig->addFilter(array('nl2br'=>'nl2br'));
	
	$template = 'lightbox/validate.twig';

	if(!$browser->isNewGeneration())
		$template = 'lightbox/IE6/validate.twig';
		
	$render = $twig->render($template, array(
					'baskets_collection' 	=> $basket_collection,
					'basket' 		=> $basket,
					'local_title'		=> strip_tags($basket->name),
					'module'		=> 'lightbox',
					'module_name'		=> _('admin::monitor: module validation')
						)
				);
	echo $render;
}
catch(Exception $e)
{
	try{
		$twig = new supertwig();

		$template = 'lightbox/error.twig';
	
		if(GV_debug)
		{
			$options = array(
						'module'	=> 'validation',
						'module_name'	=> _('admin::monitor: module validation'),
						'error'		=> $e->getMessage()
						);
		}
		else 
		{
			$options = array(
						'module'	=> 'validation',
						'module_name'	=> _('admin::monitor: module validation'),
						'error'		=> ''
						);
		}
		$twig->display($template, $options);
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
	}
}
