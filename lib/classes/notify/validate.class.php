<?php
class notify_validate extends notify
{
	public $events = array('__PUSH_VALIDATION__');
	
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
			,'message'	=> ''
			,'ssel_id'	=> ''
		);
		
		$params = array_merge($default, $params);
		
		$dom_xml = new DOMDocument('1.0','UTF-8');
		
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput = true;
		
		$root 	 = $dom_xml->createElement('datas');
		
		$from	 = $dom_xml->createElement('from');
		$to		 = $dom_xml->createElement('to');
		$message = $dom_xml->createElement('message');
		$ssel_id = $dom_xml->createElement('ssel_id');
		
		$from	->appendChild($dom_xml->createTextNode($params['from']));
		$to		->appendChild($dom_xml->createTextNode($params['to']));
		$message->appendChild($dom_xml->createTextNode($params['message']));
		$ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));
		
		$root	->appendChild($from);
		$root	->appendChild($to);
		$root	->appendChild($message);
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
			$to = array('email'=>$params['to_email'],'name'=>$params['to_name']);
			$from = array('email'=>$params['from_email'],'name'=>$params['from_email']);
			$message = $params['message'];
			$url = $params['url'];
			$accuse = $params['accuse']; 
			
			if(self::mail($to, $from, $message, $url, $accuse))
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
			$basket_name = (trim($basket->name) != '' ? $basket->name : _('Une selection'));
		}
		catch(Exception $e)
		{
			$basket_name = _('Une selection');
		}
		
		$bask_link = '<a href="'.GV_ServerName.'lightbox/validate/'.(string)$sx->ssel_id.'/" target="_blank">'.$basket_name.'</a>';
		
		$ret = array(
			'text'			=> sprintf( _('%1$s vous demande de valider %2$s') , $sender , $bask_link) 
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
		return _('Recevoir des notifications lorsqu\'on me demande une validation');
	}
	
	function mail($to,$from,$message,$url, $accuse)
	{
		$subject = _('push::mail:: Demande de validation de documents');
				
		$body = '<div>'.sprintf(_('Le lien suivant vous propose de valider une selection faite par %s'),$from['name'])."</div>\n";
				
		$body .= "<br/>\n";
		$body .= '<div><a href="'.$url.'" target="_blank">'.$url."</a></div>\n".$message;
				 
		$body .= "<br/>\n<br/>\n<br/>\n"._('push::atention: ce lien est unique et son contenu confidentiel, ne divulguez pas');
				
		return mail::send_mail($subject, $body, $to, $from, array(), $accuse);
	}

	function is_avalaible()
	{
		return true;
	}
}