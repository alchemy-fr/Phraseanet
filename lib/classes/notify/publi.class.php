<?php
class notify_publi extends notify
{
	
	
	
	public $events = array('__INTERNAL_PUBLI__');

	public function icon_url()
	{
		return '/skins/icons/rss16.png';
	}
	
	public function fire($event,$params,&$object)
	{
		$default = array(
			'from'	=> ''
			,'ssel_id'	=> ''
		);
		
		$params = array_merge($default, $params);
		
		$dom_xml = new DOMDocument('1.0','UTF-8');
		
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput = true;
		
		$root 	 = $dom_xml->createElement('datas');
		
		$from	 = $dom_xml->createElement('from');
		$ssel_id = $dom_xml->createElement('ssel_id');
		
		$from	->appendChild($dom_xml->createTextNode($params['from']));
		$ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));
		
		$root	->appendChild($from);
		$root	->appendChild($ssel_id);
		
		$dom_xml->appendChild($root);
		
		$conn = connection::getInstance();
		
		//
		$events = eventsmanager::getInstance();
		$datas = $dom_xml->saveXml();
		
		$from_email = '';
		$sql = 'SELECT usr_mail FROM usr WHERE usr_id="'.$params['from'].'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$from_email = $row['usr_mail'];
			$conn->free_result($rs);
		}
		
		$from_email = trim($from_email) !== '' ? $from_email : GV_defaulmailsenderaddr;
		
		$sql = 'SELECT DISTINCT u.usr_id, u.usr_mail, u.usr_nom, u.usr_prenom FROM basusr b, usr u 
			WHERE b.actif="1" AND u.model_of="0" AND b.base_id IN (SELECT distinct base_id FROM sselcont WHERE ssel_id="'.$conn->escape_string($params['ssel_id']).'") 
			AND u.usr_id = b.usr_id';
		
		if($rs = $conn->query($sql))
		{	
			while($row = $conn->fetch_assoc($rs))
			{
				$mailed = false;
				
				$send_notif = user::getPrefs('notification_'.__CLASS__,$row['usr_id']) == '0' ? false : true;
				if($send_notif && trim($row['usr_mail']) !== '')
				{
					
					$email = array('email'=>$row['usr_mail'],'name'=>$row['usr_mail']);
					$from = array('email'=>$from_email,'name'=>$from_email);

					if(self::mail($email,$from))
						$mailed = true;
				}
				
				$events->notify($row['usr_id'], __CLASS__ , $datas, $mailed);
			}
			$conn->free_result($rs);
		}
		return;
	}
	
	public function datas($datas, $unread)
	{
		$conn = connection::getInstance();
		
		$sx = simplexml_load_string($datas);
		
		$from = (string)$sx->from;
		
		$ssel_id = (string)$sx->ssel_id;
		
		try
		{
			$registered_user = user::getInstance($from);
			
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
			if(!$registered_user->id)
			{
					return false;
			}	
			
			$sender = user::getInfos($from);
			$ret = array(
				'text'			=> sprintf(_('%1$s a publie %2$s'),$sender,$bask_link) 
				,'class'		=> ($unread == 1 ? 'reload_baskets' : '')
			);
			
			return $ret;
		}
		catch(Exception $e)
		{
			return false;
		}

	}
	

	public function get_name()
	{
		return _('Publish');
	}
	
	public function get_description()
	{
		return _('Recevoir des notifications lorsqu\'une selection est publiee');
	}

	function is_avalaible()
	{
		return true;
	}
	
	function mail($to,$from)
	{
		$subject = _('Une nouvelle publication est disponible');
				
		$body = "<div>"._('Vous pouvez vous connecter a l\'adresse suivante afin de consulter cette publication')."</div><br/>\n";

		$body .= '<div><a href="'.GV_ServerName.'">'.GV_ServerName."</a></div>\n";
		
		$body .= " <br/> ";
		
		return mail::send_mail($subject, $body, $to, $from, array());
	}
}