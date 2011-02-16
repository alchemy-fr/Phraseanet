<?php
class patch_303 implements patch
{
	
	private $release = '3.0.3';
	private $concern = array('application_box');
	
	function get_release()
	{
		return $this->release;
	}
	
	function concern()
	{
		return $this->concern;
	}
	
	function apply($id)
	{
		$this->update_users_log_datas();
		$this->update_users_search_datas();
		return true;
	}

	
	function update_users_log_datas()
	{
		$conn = connection::getInstance();
		
		$col = array('fonction','societe','activite','pays');
		
		$sql = " SELECT * FROM sbas";
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$tab_sbas[$row['sbas_id']] = array('dbname' => $row['dbname']);
			$conn->free_result($rs);
		}

		$f_req = "";
		foreach($col as $key => $column)
			$f_req .= (($f_req) ? ',': '') . $column;
	
		$sql = "SELECT usr_id, ".$f_req." FROM usr";
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$tab_usr[$row['usr_id']] = array('fonction' => $row['fonction'], 'societe' => $row['societe'], 'activite' => $row['activite'], 'pays' => $row['pays']);
			$conn->free_result($rs);
		}

		foreach($tab_sbas as $sbasid => $name)
		{
			$f_req = '';
			
			$connbas = connection::getInstance($sbasid);
			
			if($connbas)
			{
				foreach($tab_usr as $id => $columns)
				{
					foreach($columns as $column => $value)
						$f_req .= (($f_req) ? ',': '') . $column." = '".$connbas->escape_string($value)."'" ;
						
					$sql = "UPDATE log SET ".$f_req." WHERE usrid = '".$connbas->escape_string($id)."' AND site='".GV_sit."'";
					$connbas->query($sql);
				}
			}
		}

	}
	
	function update_users_search_datas()
	{
		$conn = connection::getInstance();
		
		
		$sql = " SELECT * FROM sbas";
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$tab_sbas[$row['sbas_id']] = array('dbname' => $row['dbname']);
			$conn->free_result($rs);
		}
		
		foreach($tab_sbas as $sbasid => $name)
		{
			$f_req = "";
			
			$connbas = connection::getInstance($sbasid);
			
			if($connbas)
			{
				$date_debut = '0000-00-00 00:00:00';
				
				$sql = 'SELECT MAX(date) as debut FROM `log_search`';
				if($rs = $connbas->query($sql))
				{
					if($row = $connbas->fetch_assoc($rs))
						$date_debut = $row['debut'];
					$connbas->free_result($rs);
				}
				
				$sql = 'REPLACE INTO log_search (SELECT null as id, logid as log_id, date, askquest as search, nbrep as results, coll_id FROM quest WHERE `date` > "'.$date_debut.'")';
				$connbas->query($sql);
				
			}
		}
		
		
		
		
	}
	
	
	
}