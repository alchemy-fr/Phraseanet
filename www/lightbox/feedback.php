<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/client/");
	exit();
}

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'ssel_id', 'sselcont_ids', 'sselcont_id', 'agreement', 'note');
		 
$output = "";


	$act = $parm['action'];


	switch($act){
		case 'LOAD_REPORT':
			$twig = new supertwig();
			$twig->addFilter(array('nl2br'=>'nl2br'));

			$browser = browser::getInstance();
			
			$template = 'lightbox/basket_content_report.twig';
//			if(!$browser->isNewGeneration())
//				$template = 'lightbox/IE6/basket_content_report.twig';
	
			$basket = basket::getInstance($parm['ssel_id']);

			$output = $twig->render($template,array('basket'=> $basket));
			break;
			
		case 'SET_RELEASE':

			$basket = basket::getInstance($parm['ssel_id']);
			
			$datas = array('error'=>true, 'datas'=>_('Erreur lors de l\'enregistrement des donnees'));
			if($basket->set_released())
				$datas = array('error'=>false,'datas'=>_('Envoie avec succes'));

			$output = p4string::jsonencode($datas);
			break;
			
		case 'LOAD_BASKET_ELEMENT':

			$twig = new supertwig();
			$twig->addFilter(array('nl2br'=>'nl2br'));
			

			$browser = browser::getInstance();
			
			if($browser->isMobile())
			{
				$basket_element = new basketElement($parm['sselcont_id']);
				
				$output = $twig->render('lightbox/basket_element.twig',array('basket_element'=> $basket_element));
			}
			else
			{
				$template_options = 'lightbox/sc_options_box.twig';
				$template_agreement = 'lightbox/agreement_box.twig';
				$template_selector = 'lightbox/selector_box.twig';
				$template_note = 'lightbox/sc_note.twig';
				
				if(!$browser->isNewGeneration())
				{
					$template_options = 'lightbox/IE6/sc_options_box.twig';
					$template_agreement = 'lightbox/IE6/agreement_box.twig';
//					$template_selector = 'lightbox/IE6/selector_box.twig';
//					$template_note = 'lightbox/IE6/sc_note.twig';
				}
	
				$basket = basket::getInstance($parm['ssel_id']);
				$sselcont_ids = array();
				
				foreach($basket->elements as $basket_element)
				{
					if(!in_array($basket_element->sselcont_id, $parm['sselcont_ids']))
						continue;
					$sselcont_ids[$basket_element->sselcont_id] 					= $basket_element->to_array();
					$sselcont_ids[$basket_element->sselcont_id]['options_html'] 	= $twig->render($template_options,	array('basket_element'=> $basket_element));
					$sselcont_ids[$basket_element->sselcont_id]['agreement_html'] 	= $twig->render($template_agreement,array('basket'=>$basket, 'basket_element'=> $basket_element));
					$sselcont_ids[$basket_element->sselcont_id]['selector_html'] 	= $twig->render($template_selector,	array('basket_element'=> $basket_element));
					$sselcont_ids[$basket_element->sselcont_id]['note_html'] 		= $twig->render($template_note,		array('basket_element'=> $basket_element));
				}
				$output = p4string::jsonencode($sselcont_ids);
			}

			break;
		case 'SET_ELEMENT_AGREEMENT':

			$basket_element = new basketElement($parm['sselcont_id']);
			$output = p4string::jsonencode($basket_element->set_agreement($parm['agreement']));
			break;
		case 'SET_NOTE':

			$basket_element = new basketElement($parm['sselcont_id']);
			if($basket_element->set_note($parm['note']))
			{
				$twig = new supertwig();
				$twig->addFilter(array('nl2br'=>'nl2br'));
							
				$browser = browser::getInstance();
			
				if($browser->isMobile())
				{
					$datas = $twig->render('lightbox/sc_note.twig', array('basket_element' => $basket_element));
					
					$output = array('error'=>false,'datas'=>$datas);
				}
				else
				{
					$template = 'lightbox/sc_note.twig';
//					if(!$browser->isNewGeneration())
//						$template = 'lightbox/IE6/sc_note.twig';
						
					$datas = $twig->render($template, array('basket_element' => $basket_element));
					
					$output = array('error'=>false,'datas'=>$datas);
				}
			}
			else
			{
				$output = array('error'=>true,'datas'=>_('Erreur lors de l\'enregistrement des donnees'));
			}
			
			
			
			$output = p4string::jsonencode($output);
			break;
	}


	echo $output;

