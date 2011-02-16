<?php
class liste
{
	public static function filter($lst)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		if(!is_array($lst))
			explode(';',$lst);
		
		$okbrec = array();
		
		$user = user::getInstance($session->usr_id);
		
		foreach($lst as $basrec)
		{
			if(isset($user->_rights_records[$basrec]))
			{
				$okbrec[] = $basrec;
				continue;
			}
			
			$basrec = explode("_", $basrec);
			
			if(!$basrec || count($basrec) != 2)
				continue;
			if(!isset($user->_rights_bas[$basrec[0]]))
				continue;
				
			$connsbas = connection::getInstance(phrasea::sbasFromBas($basrec[0]));
			if(!$connsbas)
				continue;

			$sql = 'SELECT record_id FROM record WHERE ((status ^ '.$user->_rights_bas[$basrec[0]]['mask_xor'].') 
					& '.$user->_rights_bas[$basrec[0]]['mask_and'].')=0' .
					' AND record_id = "'.$connsbas->escape_string($basrec[1]).'"';
	
			if($rs = $connsbas->query($sql))
			{
				if(($connsbas->num_rows($rs)) > 0){
					$okbrec[] = implode('_',$basrec);
				}
				$connsbas->free_result($rs);
			}
		}
		return $okbrec;
	}
	
	public static function addType($lst)
	{
		if(!is_array($lst))
			explode(';',$lst);
			
		foreach($lst as $k=>$basrec)
		{
			$basrec = explode('_', $basrec);
			
			if(count($basrec) != 2)
				continue;
			
			$connbas = connection::getInstance(phrasea::sbasFromBas($basrec[0]));
			
			$sql = 'SELECT type FROM record WHERE record_id="'.$connbas->escape_string($basrec[1]).'"';
			if($rs = $connbas->query($sql))
			{
				if($row = $connbas->fetch_assoc($rs))
				{
					$basrec[2] = $row['type'];
					$lst[$k] = implode('_', $basrec);
				}
				$connbas->free_result($rs);
			}
		}
		return $lst;
	}
}