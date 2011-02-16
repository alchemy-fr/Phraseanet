<?php
class notify_validationdone extends notify
{
	public $events = array('__VALIDATION_DONE__');

	function __construct()
	{
		$this->group = _('Validation');
		return $this;
	}
	
	public function icon_url()
	{
		return '/skins/prod/000000/images/pushdoc_history.gif';
	}
	
	public function fire($event,$params,&$object)
	{
		$default = array(
			'from'	=> ''
			,'to'	=> ''
			,'ssel_id'	=> ''
//			,'url'	=> ''
		);
		
		$params = array_merge($default, $params);
		
		$dom_xml = new DOMDocument('1.0','UTF-8');
		
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput = true;
		
		$root 	 = $dom_xml->createElement('datas');
		
		$from	 = $dom_xml->createElement('from');
		$to		 = $dom_xml->createElement('to');
		$ssel_id = $dom_xml->createElement('ssel_id');
		
		$from	->appendChild($dom_xml->createTextNode($params['from']));
		$to		->appendChild($dom_xml->createTextNode($params['to']));
		$ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));
		
		$root	->appendChild($from);
		$root	->appendChild($to);
		$root	->appendChild($ssel_id);
		
		$dom_xml->appendChild($root);
		
		//mise en ofrme des datas
		
		//
		$events = eventsmanager::getInstance();
		$datas = $dom_xml->saveXml();
		
		$mailed = false;
				
		$send_notif = user::getPrefs('notification_'.__CLASS__,$params['to']) == '0' ? false : true;
		if($send_notif)
		{
			try{
				$user_from	= user::getInstance($params['from']);
				$user_to	= user::getInstance($params['to']);
			}
			catch(Exception $e)
			{
				return false;
			}
			
			$to = array('email'=>$user_to->email,'name'=>$user_to->display_name);
			$from = array('email'=>$user_from->email,'name'=>$user_from->display_name);
//			$url = $params['url'];
			
			if(self::mail($to, $from, $params['ssel_id']))
				$mailed = true;
		}	
		
		return $events->notify($params['to'], __CLASS__ , $datas, $mailed);
	}
	
	public function datas($datas, $unread)
	{
		$conn = connection::getInstance();
		
		$sx = simplexml_load_string($datas);
		
		$from = (string)$sx->from;
		$ssel_id = (string)$sx->ssel_id;
		
		try{
			$registered_user = user::getInstance($from);
		}
		catch(Exception $e)
		{
				return false;
		}	
		
		$sender = user::getInfos($from);
		
		try {
			$basket = basket::getInstance($ssel_id);
		}
		catch(Exception $e)
		{
			return false;
		}	
		
		$ret = array(
			'text'			=> sprintf( _('%1$s a envoye son rapport de validation de %2$s'), $sender, '<a href="/lightbox/validate/'.(string)$sx->ssel_id.'/" target="_blank">'.$basket->name.'</a>') 
			,'class'		=> ''
		);
		
		return $ret;
	}
	

	public function get_name()
	{
		return _('Rapport de Validation');
	}
	
	public function get_description()
	{
		return _('Reception d\'un rapport de validation');
	}
	
	function mail($to,$from, $ssel_id)
	{
		try {
			$basket = basket::getInstance($ssel_id);
		}
		catch(Exception $e)
		{
			return false;
		}	
		
		$subject = sprintf(_('push::mail:: Rapport de validation de %1$s pour %2$s'), $to['name'], $basket->name);
				
		$body = "<div>".sprintf(_('%s a rendu son rapport, consulter le en ligne a l\'adresse suivante'), $from['name'])."</div>\n";
		
		$body .= "<br/>\n".GV_ServerName.'lightbox/validate/'.$ssel_id;
				
		return mail::send_mail($subject, $body, $to, $from, array());
	}

	function is_avalaible()
	{
		$bool = false;

		$session = session::getInstance();
		if(!isset($session->usr_id) || !login::register_enabled())
			return false;
		
		try {
			$user = user::getInstance($session->usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}
		
		if($user->_global_rights['push'] === true)
		{
			$bool = true;
		}
		
		return $bool;
	}
}