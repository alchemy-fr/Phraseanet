<?php

class notify_order extends notify
{
	public $events = array('__NEW_ORDER__');
	
	public function icon_url()
	{
		return '/skins/icons/user.png';
	}
	
	public function fire($event,$params,&$object)
	{
		$conn = connection::getInstance();
						
						
		$default = array(
			'usr_id'		=> ''
			,'order_id'		=> array()
		);
		
		$params = array_merge($default, $params);
		$order_id = $params['order_id'];
		
		$sql = 'SELECT DISTINCT m.usr_id 
				FROM order_elements e, order_masters m 
				WHERE e.order_id = "'.$conn->escape_string($order_id).'"
				AND e.base_id = m.base_id';
		
		$usr_ids = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$usr_ids[] = $row['usr_id'];
			}
			$conn->free_result($rs);
		}

		if(count($usr_ids) == 0)
			return;

			
		$dom_xml = new DOMDocument('1.0','UTF-8');
		
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput = true;
		
		$root 	 = $dom_xml->createElement('datas');
		
		$usr_id_dom	 = $dom_xml->createElement('usr_id');
		$order_id_dom = $dom_xml->createElement('order_id');
		
		$usr_id_dom	->appendChild($dom_xml->createTextNode($params['usr_id']));
		
		$order_id_dom->appendChild($dom_xml->createTextNode($order_id));
		
		
		$root->appendChild($usr_id_dom);
		$root->appendChild($order_id_dom);
		
		$dom_xml->appendChild($root);
		
		$events = eventsmanager::getInstance();
		$datas = $dom_xml->saveXml();

		foreach($usr_ids as $usr_id)
		{
			$mailed = false;
			
			$send_notif = user::getPrefs('notification_'.__CLASS__,$usr_id) == '0' ? false : true;
			if($send_notif)
			{
				try{
					$admin_user = user::getInstance($usr_id);
				}
				catch(Exception $e)
				{
					continue;
				}
				
				$dest = user::getInfos($usr_id);
				
				$to = array('email'=>$admin_user->email,'name'=>$dest);
				$from = array('email'=>GV_defaulmailsenderaddr,'name'=>GV_homeTitle);

				if(self::mail($to, $from, $datas))
				{
					$mailed = true;
				}
			}
			
			
			$events->notify($usr_id, __CLASS__ , $datas, $mailed);
		}
		return;
	}
	
	public function datas($datas, $unread)
	{
		$sx = simplexml_load_string($datas);
		
		$usr_id = (string)$sx->usr_id;
		$order_id = (string)$sx->order_id;
		
		try{
			$registered_user = user::getInstance($usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}

		$sender = user::getInfos($usr_id);
			
		$ret = array(
			'text'			=> sprintf( _('%1$s a passe une %2$scommande%3$s') , $sender , '<a href="#" onclick="load_order('.$order_id.')">','</a>') 
			,'class'		=> ''
		);
		
		return $ret;
	}
	

	public function get_name()
	{
		return _('Nouvelle commande');
	}
	
	public function get_description()
	{
		return _('Recevoir des notifications lorsqu\'un utilisateur commande des documents');
	}
	
	function mail($to,$from, $datas)
	{
		$subject = sprintf(_('admin::register: Nouvelle commande sur %s'),GV_homeTitle);
		
		$body = "<div>"._('admin::register: un utilisateur a commande des documents')."</div>\n";
		
		$sx = simplexml_load_string($datas);
		
		$usr_id = (string)$sx->usr_id;
		
		try{
			$registered_user = user::getInstance($usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}

		$body .= "<br/>\n<div>Login : " . $registered_user->login."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur nom')." : " . $registered_user->firstname."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur prenom')." : " . $registered_user->lastname."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur email')." : " . $registered_user->email."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur adresse')." : " . $registered_user->address."</div>\n";
		$body .= "<div>" . $registered_user->city." ".$registered_user->zip."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur telephone')	." : " . $registered_user->tel."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur fax')." : " . $registered_user->fax."</div>\n";
		$body .= "<div>"._('admin::compte-utilisateur poste')."/"._('admin::compte-utilisateur societe')." " . $registered_user->job." ". $registered_user->company."</div>\n";
		
		$base_ids = $sx->base_ids;
		
		$body .= "<br/>\n<div>"._('Retrouvez son bon de commande dans l\'interface')."</div>\n";
		

		return mail::send_mail($subject, $body, $to, $from);
	}

	function is_avalaible()
	{
		$bool = false;
		$session = session::getInstance();
		if(!isset($session->usr_id))
			return false;
		
		try{
			$user = user::getInstance($session->usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($user->_global_rights['order_master'] === true)
		{
			$bool = true;
		}
		
		return $bool;
	}
}