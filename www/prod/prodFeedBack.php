<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/prod/");
	exit();
}
$output = '';

$request = httpRequest::getInstance();
$parm = $request->get_parms('action');

	$action = $parm['action'];
	
	switch($action)
	{
		case 'LANGUAGE':
			require (GV_RootPath.'lib/prodUtils.php');
			$output = getLanguage($lng);
			break;
		case 'CSS':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('color');
			$output = setCss($usr_id,$ses_id,$parm['color']); 
			break;
		case 'MYRSS':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('renew');
			$output = p4string::jsonencode(user::getMyRss(($parm['renew']=='true' ? true : false)));
			break;
			
		case 'SAVEPREF':
			$parm = $request->get_parms('prop','value');
			$ret = user::setPrefs($parm['prop'], $parm['value']);
			if(isset($ret[$parm['prop']]) && $ret[$parm['prop']]=$parm['value'])
				$output = "1";
			else
				$output = "0";
			break;
			
		case 'SAVETEMPPREF':
			$prefs_datas = $session->prefs;
			$parm = $request->get_parms('prop','value');
			$prefs_datas[$parm['prop']] = $parm['value'];
			$session->prefs = $prefs_datas;
			$output = 1;
			break;
		
		case 'BASKETS':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('id','sort');
			$output = baskets($parm['id'], $parm['sort']);
			break;
		case 'BASKETNAME':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('ssel_id');
			$basket = basket::getInstance($parm['ssel_id']);
			$output = p4string::jsonencode(array('name'=>$basket->name,'description'=>$basket->desc));
			break;
		case 'BASKETRENAME':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('ssel_id', 'name', 'description');
			$basket = basket::getInstance($parm['ssel_id']);
			$basket->name = $parm['name'];
			$basket->desc = $parm['description'];
			$output = $basket->save();
			break;
		
		case 'GETBASKET':
			require (GV_RootPath.'lib/prodUtils.php');
			
			$twig = new supertwig();
			$twig->addFilter(array('nl2br'=>'nl2br'));
			
			$parm = $request->get_parms('id', 'ord');
			
			$basket = basket::getInstance($parm['id']);
			$basket->set_read();
			
			$session = session::getInstance();
			$user = user::getInstance($session->usr_id);
			$order = $parm['ord'];
			
			if(trim($order) == '' || !in_array($order, array('asc', 'desc', 'nat')))
				$order = user::getPrefs('bask_val_order');
			else
				$user->setPrefs('bask_val_order', $order);
			
			$basket->sort($order);
			
			$output = p4string::jsonencode(array('content'=>$twig->render('prod/basket.twig',array('basket'=> $basket, 'ordre'=>$order))));
			break;
		
		case 'DELETE':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('lst');
			$output = whatCanIDelete($parm['lst']);
			break;
		case 'DODELETE':
			require (GV_RootPath.'lib/prodUtils.php');
			$parm = $request->get_parms('lst', 'del_children');
			$output = deleteRecord($parm['lst'],$parm['del_children']);
			break;
			
		case 'MAIL_REQ':
			$parm = $request->get_parms('user', 'contrib', 'message', 'query');
			$output = query::mail_request($parm['user'],$parm['contrib'],$parm['message'], $parm['query']);
			break;
			
			
		case 'REORDER_DATAS':
			$parm = $request->get_parms('ssel_id');
			$basket = basket::getInstance($parm['ssel_id']);
			$output = $basket->getOrderDatas();
			break;
		case 'SAVE_ORDER_DATAS':
			$parm = $request->get_parms('ssel_id', 'value');
			$basket = basket::getInstance($parm['ssel_id']);
			$output = $basket->saveOrderDatas($parm['value']);
			break;
			
			
		case 'DENY_CGU':
			$parm = $request->get_parms('sbas_id');
			$output = cgus::denyCgus($parm['sbas_id']);
			break;
		case 'READ_NOTIFICATIONS':
			$evt_mngr = eventsmanager::getInstance();
			$parm = $request->get_parms('notifications');
			$output = $evt_mngr->read(explode('_',$parm['notifications']), $session->usr_id);
			break;
		case 'NOTIFICATIONS_FULL':
			$evt_mngr = eventsmanager::getInstance();
			$parm = $request->get_parms('page');
			$output = $evt_mngr->get_json_notifications($parm['page']);
			break;
			
			
			
			
			
		case 'VIDEOTOKEN':
			$parm = $request->get_parms('base_id', 'record_id');
			$url = answer::renew_token($parm['base_id'],$parm['record_id']);
			$output = p4string::jsonencode(array('url'=>$url));
			break;
			
			
			
		case 'ANSWERTRAIN':
			include(GV_RootPath.'lib/clientUtils.php');
			$parm = $request->get_parms('pos', 'record_id');
			$output = p4string::jsonencode(array('current'=>getAnswerTrain($parm['pos'])));
			break;
			
			
		case 'REGTRAIN':
				include(GV_RootPath.'lib/clientUtils.php');
				$parm = $request->get_parms('cont', 'pos');
				$contid = explode('_',$parm['cont']);
				if(count($contid) == 2)
					$output = p4string::jsonencode(array('current'=>getRegTrain($ses_id,$contid[0],$contid[1],$usr_id,$parm['pos'])));
			break;
		case 'UNFIX':
				$parm = $request->get_parms('lst');
				$output = basket::unfix_grouping($parm['lst']);
			break;
		case 'FIX':
				$parm = $request->get_parms('lst');
				$output = basket::fix_grouping($parm['lst']);
			break;
		case 'ADDIMGT2CHU':
		case 'ADDCHU2CHU':
		case 'ADDREG2CHU':
				$parm = $request->get_parms('dest', 'lst');
				$basket = basket::getInstance($parm['dest']);
				$output = p4string::jsonencode($basket->push_list($parm['lst'], false));
				
			break;			
		case 'ADDIMGT2REG':
		case 'ADDCHU2REG':
		case 'ADDREG2REG':
				$parm = $request->get_parms('dest', 'lst');
				$basket = basket::getInstance($parm['dest']);
				$output = p4string::jsonencode($basket->push_list($parm['lst'], false));
			break;
		case 'DELFROMBASK':
				$parm = $request->get_parms('ssel_id', 'sselcont_id');
				$basket = basket::getInstance($parm['ssel_id']);
				$output = p4string::jsonencode($basket->remove_from_ssel($parm['sselcont_id']));
			break;			
		case 'DELBASK':
				$parm = $request->get_parms('ssel');
				$basket = basket::getInstance($parm['ssel']);
				$output = $basket->delete();
				unset($basket);
			break;
			
		case 'ADD_PUBLI_PRESET':
				require (GV_RootPath.'lib/prodUtils.php');
				$parm = $request->get_parms('datas', 'ssel');
				$output = p4publi::addPubliPreset($parm['datas'],$parm['ssel']);	
			break;
		case 'TEST_PUBLI_PRESET':
				require (GV_RootPath.'lib/prodUtils.php');
				$parm = $request->get_parms('publi_id');
				$output = p4string::jsonencode(array('status'=>p4publi::testPubliPreset($parm['publi_id'])));	
			break;
		case 'DELETE_PUBLI_PRESET':
				require (GV_RootPath.'lib/prodUtils.php');
				$parm = $request->get_parms('publi_id', 'ssel_id');
				$output = p4publi::deletePubliPreset($parm['publi_id'],$parm['ssel_id']);	
			break;
			
		case 'LOAD_PUBLI':
				require (GV_RootPath.'lib/prodUtils.php');
				$parm = $request->get_parms('ssel');
				$output = p4string::jsonencode(p4publi::getForm($parm['ssel']));	
			break;
			
		case 'PUBLISH':
				$parm = $request->get_parms('ssel', 'status');
				$output = p4publi::publishBasket($parm['ssel'],$parm['status']);	
			break;
			
		case 'HOMELINK':
				$parm = $request->get_parms('ssel', 'status');
				$basket = basket::getInstance($parm['ssel']);
				$output = $basket->homelink($parm['status']);
			break;
		case 'MOVCHU2CHU':
				$parm = $request->get_parms('from', 'dest', 'sselcont');
				$from_basket = basket::getInstance($parm['from']);
				$to_basket = basket::getInstance($parm['dest']);
				
				$ret = array('error'=>_('phraseanet :: une erreur est survenue'), 'datas'=>array());
				if(!is_array($parm['sselcont']))
					$parm['sselcont'] = explode(';',$parm['sselcont']);
				
				foreach($parm['sselcont'] as $sselcont_id)
				{
				
					if(!isset($from_basket->elements[$sselcont_id]))
						continue;
							
					$element = $from_basket->elements[$sselcont_id];
					
					if($to_basket->push_element($element->base_id, $element->record_id, false, false))
					{
						$from_basket->remove_from_ssel($sselcont_id);
					
						$ret['error'] = false;
						$ret['datas'][] = $sselcont_id;
					}
				}
				$output = p4string::jsonencode($ret);
			break;
		case 'MOVREG2REG':
				require (GV_RootPath.'lib/prodUtils.php');
				$lst =array();
				$parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
				$basket = basket::getInstance($parm['dest']);
				$res = $basket->push_list($parm['lst'], false);
				if(!$res['error'])		
				{
					$basket = basket::getInstance($parm['from']);
					
					$sselcont_ids = explode(';',$parm['sselcont']);
					foreach($sselcont_ids as $sselcont_id)
					{
						$basket->remove_from_ssel($sselcont_id);
					}
				}
				$output =  p4string::jsonencode(array('error'=>$res['error'],'datas'=>explode(';',$parm['sselcont'])));
			break;
		case 'MOVCHU2REG':
				require (GV_RootPath.'lib/prodUtils.php');
				$parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
				$basket = basket::getInstance($parm['dest']);
				$res = $basket->push_list($parm['lst'], false);
				if(!$res['error'])
				{
					$basket = basket::getInstance($parm['from']);
				
					$sselcont_ids = explode(';',$parm['sselcont']);
					foreach($sselcont_ids as $sselcont_id)
					{
						$basket->remove_from_ssel($sselcont_id);
					}
				}
				$output = p4string::jsonencode(array('error'=>$res['error'],'datas'=>explode(';',$parm['sselcont'])));
			break;
		case 'MOVREG2CHU':
				require (GV_RootPath.'lib/prodUtils.php');
	
				$parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
				$basket = basket::getInstance($parm['dest']);
				$add = $basket->push_list($parm['lst'], false);
				
				if(!$add['error'])	
				{
					
					$basket = basket::getInstance($parm['from']);

					$ret['error'] = false;
					$ret['datas'] = array();
					
					$sselcont_ids = explode(';',$parm['sselcont']);
					foreach($sselcont_ids as $sselcont_id)
					{
						$rem = $basket->remove_from_ssel($sselcont_id);
						if(!$rem['error'])
						{
							$ret['datas'][] = $sselcont_id;
						}
						else
						{
							$ret['error'] = true;
						}
					}
				}
				else
					$ret = array('datas'=>array(),'error'=>$add['error']);
				$output =  p4string::jsonencode($ret);
			break;
			
			
			
	  
	case 'GET_ORDERMANAGER':
			try
			{
				$parm = $request->get_parms('sort', 'page');
				$orders = new ordermanager($parm['sort'],$parm['page']);
				$twig = new supertwig();
				$twig->addFilter(array('phraseadate'=>'phraseadate::getPrettyString'));
				$render = $twig->render('prod/orders/order_box.twig', array('ordermanager'=>$orders));
				$ret = array('error'=>false,'datas'=>$render);
			}
			catch(Exception $e)
			{
				$ret = array('error'=>true,'datas'=>$e->getMessage());
			}
			
			$output = p4string::jsonencode($ret);
			break;
	  
	case 'GET_ORDER':
			try
			{
				$parm = $request->get_parms('order_id');
				$order = new order($parm['order_id']);
				
				$twig = new supertwig();
				$twig->addFilter(array('phraseadate'=>'phraseadate::getPrettyString'));
				$twig->addFilter(array('nl2br'=>'nl2br'));
				$render = $twig->render('prod/orders/order_item.twig', array('order'=>$order));
				$ret = array('error'=>false,'datas'=>$render);
			}
			catch(Exception $e)
			{
				$ret = array('error'=>true,'datas'=>$e->getMessage());
			}
			
			$output = p4string::jsonencode($ret);
		break;
	  
	case 'SEND_ORDER':
			try
			{
				$parm = $request->get_parms('order_id', 'elements', 'force');
				$order = new order($parm['order_id']);
				$order->send_elements($parm['elements'], $parm['force']);
				$ret = array('error'=>false,'datas'=>'');
			}
			catch(Exception $e)
			{
				$ret = array('error'=>true,'datas'=>$e->getMessage());
			}
			
			$output = p4string::jsonencode($ret);
		break;
	  
	case 'DENY_ORDER':
			try
			{
				$parm = $request->get_parms('order_id', 'elements');
				$order = new order($parm['order_id']);
				$order->deny_elements($parm['elements']);
				$ret = array('error'=>false,'datas'=>'');
			}
			catch(Exception $e)
			{
				$ret = array('error'=>true,'datas'=>$e->getMessage());
			}
			
			$output = p4string::jsonencode($ret);
		break;
	  
	  
	  
	  case "ORDER":
			$parm = $request->get_parms('lst', 'ssttid', 'use', 'deadline');
	  		$order = new exportorder($parm['lst'], $parm['ssttid']);
	  		
	  		if($order->order_avalaible_elements($session->usr_id, $parm['use'], $parm['deadline']))
	  		{
	  			$ret = array('error'=>false, 'message'=>_('les enregistrements ont ete correctement commandes')); 
	  		}
	  		else
	  		{
	  			$ret = array('error'=>true, 'message'=>_('Erreur lors de la commande des enregistrements'));
	  		}
	  		
	  		$output = p4string::jsonencode($ret);
	  		
	  		break;
	  case "FTP_EXPORT":

			$request = httpRequest::getInstance();
			$parm = $request->get_parms(
						"addr"  	// addr du srv ftp
						,"login"	// login ftp
						,"pwd"		// pwd ftp 
						,"passif"	// mode passif ou non
						,"nbretry"	// nb retry
						,"ssl"	// nb retry
						,"obj"	// les types d'obj a exporter
						,"destfolder"// le folder de destination
						,"usr_dest"		// le mail dudestinataire ftp			
						,"lst"		// la liste des objets
						,"ssttid"
						,"sendermail"
						,"namecaract"
						,"NAMMKDFOLD"
						);
			
			$download = new exportftp($parm['lst'], $parm['ssttid']);
			
			if(count($download->display_ftp) == 0)
			{
				$output = p4string::jsonencode(array('error'=>true,'message'=>_('Les documents ne peuvent etre envoyes par FTP')));
			}
			else
			{
				try
				{
					$download->prepare_export($parm['obj']);
					$download->export_ftp($parm['usr_dest'], $parm['addr'], $parm['login'], $parm['pwd'], $parm['ssl'], $parm['nbretry'], $parm['passif'], $parm['destfolder'], $parm['NAMMKDFOLD']);
				
					$output = p4string::jsonencode(array('error'=>false,'message'=>_('Export enregistre dans la file dattente')));
				}
				catch(Exception $e)
				{
					$output = p4string::jsonencode(array('error'=>true,'message'=>$e->getMessage()));
				}
			}
	  		break;
	  case "FTP_TEST":

			$request = httpRequest::getInstance();
			$parm = $request->get_parms(
						"addr"  	// addr du srv ftp
						,"login"	// login ftp
						,"pwd"		// pwd ftp 
						,"ssl"	// nb retry
						);
			
			$ssl = $parm['ssl'] == '1';
			
			try
			{
				$ftp_client = new ftpclient($parm['addr'], 21, 90, $ssl = false);
				$ftp_client->login($parm['login'], $parm['pwd']);
				$ftp_client->close();
				$output = _('Connection au FTP avec succes');
			}
			catch(Exception $e)
			{
				$output = sprintf(_('Erreur lors de la connection au FTP : %s'), $e->getMessage());
			}
	  		
	  		break;
	}
echo $output;





?>
