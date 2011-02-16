<?php

class notify_autoregister extends notify
{
	public $events = array('__REGISTER_AUTOREGISTER__');
	
	public function icon_url()
	{
		return '/skins/icons/user.png';
	}
	
	public function fire($event,$params,&$object)
	{
		$conn = connection::getInstance();
						
						
		$default = array(
			'usr_id'		=> ''
			,'autoregister'		=> array()
		);
		
		$params = array_merge($default, $params);
		$base_ids = $params['autoregister'];
		
		if(count($base_ids) == 0)
			return;
		
		$mailColl = array();
		
		$sql = 'SELECT u.usr_id, b.base_id FROM usr u, basusr b WHERE u.usr_id = b.usr_id AND b.base_id IN ('.$conn->escape_string(implode(', ',array_keys($base_ids))).') AND model_of="0" AND b.actif="1"  AND b.canadmin="1" 
		AND u.usr_login NOT LIKE "(#deleted%"';	

		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				if(!isset($mailColl[$row['usr_id']]))
					$mailColl[$row['usr_id']] = array();
					
				$mailColl[$row['usr_id']][] = $row['base_id'];
			}
			$conn->free_result($rs);
		}	

		$dom_xml = new DOMDocument('1.0','UTF-8');
		
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput = true;
		
		$root 	 = $dom_xml->createElement('datas');
		
		$usr_id	 = $dom_xml->createElement('usr_id');
		$base_ids= $dom_xml->createElement('base_ids');
		
		$usr_id	->appendChild($dom_xml->createTextNode($params['usr_id']));
		
		foreach($params['autoregister'] as $base_id=>$is_ok)
		{
			$base_id_node	= $dom_xml->createElement('base_id');
			$base_id_node	->appendChild($dom_xml->createTextNode($base_id));
			$base_ids	->appendChild($base_id_node);
		}
		
		
		$root->appendChild($usr_id);
		$root->appendChild($base_ids);
		
		$dom_xml->appendChild($root);
		
		$events = eventsmanager::getInstance();
		$datas = $dom_xml->saveXml();

		foreach($mailColl as $usr_id=>$base_ids)
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

				$dest = $admin_user->email;
				
				if(trim($admin_user->firstname.' '.$admin_user->lastname) != '')
					$dest = $admin_user->firstname.' '.$admin_user->lastname;
				
				$to = array('email'=>$admin_user->email,'name'=>$dest);
				$from = array('email'=>GV_defaulmailsenderaddr,'name'=>GV_homeTitle);

				if(self::mail($to, $from, $datas))
					$mailed = true;
			}	
			
			
			$events->notify($usr_id, __CLASS__ , $datas, $mailed);
		}
		return;
	}
	
	public function datas($datas, $unread)
	{
		$sx = simplexml_load_string($datas);
		
		$usr_id = (string)$sx->usr_id;
		try{
			$registered_user = user::getInstance($usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}

		$sender = user::getInfos($usr_id);
			
		$ret = array(
			'text'			=> sprintf( _('%1$s s\'est enregistre sur une ou plusieurs %2$scollections%3$s') , $sender , '<a href="/admin/?section=users" target="_blank">','</a>') 
			,'class'		=> ''
		);
		
		return $ret;
	}
	

	public function get_name()
	{
		return _('AutoRegister information');
	}
	
	public function get_description()
	{
		return _('Recevoir des notifications lorsqu\'un utilisateur s\'inscrit sur une collection');
	}
	
	function mail($to,$from, $datas)
	{
		$subject = sprintf(_('admin::register: Inscription automatique sur %s'),GV_homeTitle);
		
		$body = "<div>"._('admin::register: un utilisateur s\'est inscrit')."</div>\n";
		
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
		
		$body .= "<br/>\n<div>"._('admin::register: l\'utilisateur s\'est inscrit sur les bases suivantes')."</div>\n";
		$body .= "<ul>";

		foreach($base_ids->base_id as $base_id)
		{
			$body .= "<li>".phrasea::sbas_names(phrasea::sbasFromBas((string)$base_id)).' - '.phrasea::bas_names((string)$base_id)."</li>\n";
		}
		
		$body .= "</ul>";
		
		$body .= "<br/>\n<div><a href='/login/admin' target='_blank'>" . _('admin::register: vous pourrez consulter son compte en ligne via l\'interface d\'administration') . "</a></div>\n" ;

		return mail::send_mail($subject, $body, $to, $from);
	}

	function is_avalaible()
	{
		$bool = false;
		$session = session::getInstance();
		if(!isset($session->usr_id) || !login::register_enabled())
			return false;
	
		try{
			$user = user::getInstance($session->usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($user->_global_rights['manageusers'] === true)
		{
			$bool = true;
		}
		
		return $bool;
	}
}