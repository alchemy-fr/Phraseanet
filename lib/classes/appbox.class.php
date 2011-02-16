<?php

class appbox extends base {
	
	var $id = false;
	var $structure = false;
	
	function __destruct()
	{
		
	}

	function __construct()
	{
		if($this->init_conn() == false)
			return false;

		if($this->load_schema('application_box')=== false)
			return false;
		
		require dirname( __FILE__ ) . '/../../config/connexion.inc';
		if(isset($dbname) && trim($dbname) !== '')
		{
			if(mysql_select_db($dbname,$this->conn))				
				$this->dbname = $dbname;
		}
		
		$this->type = 'application_box';
		return true;
	}
	
	function create($dbname)
	{
		
		$this->createDb($dbname);
		
	}
	
	function getSbas($sbas_id=array())
	{
		if(!is_integer($sbas_id) && !is_array($sbas_id))
			$sbas_id = array();
		if(!is_array($sbas_id))
			$sbas_id = array($sbas_id);

		$sql = 'SELECT sbas_id FROM sbas ';
		if(count($sbas_id)>0)
			$sql .= 'WHERE sbas_id="'.implode('", OR sbas_id="',$sbas_id).'"';
		
		$ret = array();	
		
		if($rs = mysql_query($sql, $this->conn))
			while($row = mysql_fetch_assoc($rs))
				$ret[$row['sbas_id']] = new databox($row['sbas_id']);
		
		return $ret;
	}
	
	
}