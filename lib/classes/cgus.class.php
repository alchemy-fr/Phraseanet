<?php
class cgus
{
	
	public static function askAgreement()
	{
		$terms = self::getUnvalidated();
		
		$out = '';
		
		foreach($terms as $name=>$term)
		{
			if(trim($term['terms']) == '')
				continue;

			$out .= '<div style="display:none;" class="cgu-dialog" title="'.str_replace('"','&quot;',sprintf(_('cgus:: CGUs de la base %s'),$name)).'">';

			$out .= '<blockquote>'.$term['terms'].'</blockquote>';
			$out .= '<div>'._('cgus:: Pour continuer a utiliser lapplication, vous devez accepter les conditions precedentes').'
				<input id="terms_of_use_'.$term['sbas_id'].'" type="button" date="'.$term['date'].'" class="cgus-accept" value="'._('cgus :: accepter').'"/>
				<input id="sbas_'.$term['sbas_id'].'" type="button" class="cgus-cancel" value="'._('cgus :: refuser').'"/>
				</div>';
			$out .= '</div>';
		}
		
		return $out;
	}
	
	public static function denyCgus($sbas_id)
	{
		
		$session = session::getInstance();
		if(!isset($session->usr_id))
			return '2';
		
		$ret = '1';
		
		$conn = connection::getInstance();
		
		$sql = 'DELETE FROM sbasusr WHERE sbas_id="'.$conn->escape_string($sbas_id).'" AND usr_id="'.$conn->escape_string($session->usr_id).'"';
		if(!$conn->query($sql))
			$ret = '0';
		$sql = 'DELETE FROM basusr WHERE base_id IN (SELECT base_id FROM bas WHERE sbas_id="'.$conn->escape_string($sbas_id).'") AND usr_id="'.$conn->escape_string($session->usr_id).'"';
		if(!$conn->query($sql))
			$ret = '0';
		
		p4::logout();
		
		return $ret;
	}
	
	private static function getUnvalidated($home=false)
	{
		$terms = array();
		
		$session = session::getInstance();
		if($home)
			$ph_session = phrasea::bases();
		else
			$ph_session = phrasea_open_session($session->ses_id,$session->usr_id);
		
		if(!$ph_session)
			return $terms;
		
		foreach($ph_session['bases'] as $base)
		{
			$connbas = connection::getInstance($base['sbas_id']);
			
			if($connbas)
			{
				
				$sql = 'SELECT value, updated_on FROM pref WHERE prop="ToU" AND locale="'.$connbas->escape_string($session->locale).'"';
				
				if($rs = $connbas->query($sql))
				{
					if($row = $connbas->fetch_assoc($rs))
					{
						$name = trim($base['viewname']) != '' ? $base['viewname'] : $base['dbname'] ;
						
						$userValidation = true;
						
						if(!$home)
						{
							$userValidation = (user::getPrefs('terms_of_use_'.$base['sbas_id']) !== $row['updated_on'] && trim($row['value']) !== '');
						}
						
						if($userValidation)
							$terms[$name] = array('sbas_id'=>$base['sbas_id'],'terms'=>$row['value'],'date'=>$row['updated_on']);
					}
					$connbas->free_result($rs);
				}
				
			}
			
		}
		
		return $terms;
	}
	
	
	
	
	public static function getHome()
	{
		$terms = self::getUnvalidated(true);
		
		$out = '';
		
		foreach($terms as $name=>$term)
		{
			if(trim($term['terms']) == '')
				continue;
			
			if($out != '')
				$out .= '<hr/>';
				
			$out .= '<div><h1 style="text-align:center;">'.str_replace('"','&quot;',sprintf(_('cgus:: CGUs de la base %s'),$name)).'</h1>';
			
			$out .= '<blockquote>'.$term['terms'].'</blockquote>';

			$out .= '</div>';
		}
		
		return $out;
	}
	
}