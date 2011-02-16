<?php
class notify_validationreminder extends notify
{
	public $events = array('__VALIDATION_REMINDER__');

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
			,'url'	=> ''
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
			$url = $params['url'];
			
			if(self::mail($to, $from, $url))
				$mailed = true;
		}	
		
		
		$conn = connection::getInstance();
		
		$conn->query('UPDATE validate SET last_reminder=NOW() WHERE id="'.$conn->escape_string($params['validate_id']).'"');
		
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
		
		try
		{
			$basket = basket::getInstance($ssel_id);
			$basket_name = (trim($basket->name) != '' ? $basket->name : _('Une selection'));
		}
		catch(Exception $e)
		{
			$basket_name = _('Une selection');
		}
		
		$bask_link = '<a href="#" onclick="openPreview(\'BASK\',1,\''.(string)$sx->ssel_id.'\');return false;">'.$basket_name.'</a>';
		
		$ret = array(
			'text'			=> sprintf( _('Rappel : Il vous reste %1$d jours pour valider %2$s de %3$s'), GV_validation_reminder, $bask_link, $sender) 
			,'class'		=> ($unread == 1 ? 'reload_baskets' : '')
		);
		
		return $ret;
	}
	

	public function get_name()
	{
		return _('Validation');
	}
	
	public function get_description()
	{
		return _('Rappel pour une demande de validation');
	}
	
	function mail($to,$from,$url)
	{
		$subject = _('push::mail:: Rappel de demande de validation de documents');
				
		$body = "<div>".sprintf(_('Il ne vous reste plus que %d jours pour terminer votre validation'), GV_validation_reminder)."</div>\n";
		
		if(trim($url) != '')
		{
			$body = '<div>'.sprintf(_('Le lien suivant vous propose de valider une selection faite par %s'),$from['name'])."</div>\n";
					
			$body .= "<br/>\n";
			
			$body .= '<div><a href="'.$url.'" target="_blank">'.$url."</a></div>\n";
		}
			 
		$body .= "<br/>\n<br/>\n<br/>\n"._('push::atention: ce lien est unique et son contenu confidentiel, ne divulguez pas');
				
		return mail::send_mail($subject, $body, $to, $from, array());
	}

	function is_avalaible()
	{
		return true;
	}
}