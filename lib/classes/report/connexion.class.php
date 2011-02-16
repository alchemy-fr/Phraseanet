<?php


class report_connexion extends report
{
	protected $cor_query = array(
		'user' 			=> 'log.user'
		,'usrid' 		=> 'log.usrid'
		,'ddate' 		=> 'DATE_FORMAT(log.date,"%Y-%m-%d")'
		,'societe' 		=> 'log.societe'
		,'pays'			=> 'log.pays'
		,'activite' 	=> 'log.activite'
		,'fonction' 	=> 'log.fonction'
		,'site' 		=> 'log.site'
		,'sit_session' 	=> 'log.sit_session'
		,'coll_list' 	=> 'log.coll_list'
		,'appli' 		=> 'log.appli'
		,'ip' 			=> 'log.ip'
	);
	/**
	 * constructor
	 * 
	 * @name connexion::__construct()
	 * @param $arg1 start date of the  report
	 * @param $arg2 end date of the report
	 * @param $sbas_id id of the databox
	 */
	
	public function __construct($arg1, $arg2, $sbas_id, $collist)
	{
		parent::__construct($arg1, $arg2, $sbas_id, $collist);
		//title :D
		$this->title = _('report::Connexions');
	}
	
	
	/**
	 * @desc build the specified requete
	 * @param $obj $conn the current connection to databox
	 * @return string
	 */
	protected function buildReq($groupby = false, $on = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		extract($tab_filter);
		
		if($groupby == false)
		{
			// construct the request
			$this->req = "SELECT		user, usrid, log.date as ddate, log.societe,
					log.pays,
					log.activite,
					log.fonction, site, sit_session, coll_list, appli, ip
					FROM log  ";
			if($finalfilter)
				$this->req .= " WHERE ".$finalfilter;
			if($order)
				$this->req .= " ORDER BY ".$order;
				
			$rs = $conn->query($this->req);
			$this->total = $conn->num_rows($rs);
			
			if($this->enable_limit && $limit)
			{
				$this->req .= " LIMIT ".$limit;
			}
		}
		else
		{
			if($groupby == 'ddate')
				$field = "DATE_FORMAT(log.date,'%Y-%m-%d')";
			else
				$field = $groupby;
				
			$this->req = "SELECT	TRIM(".$field.") as ".$groupby.", SUM(1) as nb
					FROM	log  ";
			
			if($finalfilter)
				$this->req .= " WHERE ".$finalfilter;
	
			$this->req .= " GROUP BY ". $groupby;

			if($order)
				$this->req .= " ORDER BY ".$order;
				
			$rs = $conn->query($this->req);
			$this->total = $conn->num_rows($rs);
		}
	}
	 
	private function getColFilterSql($field, $on)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		extract($tab_filter);
		
		if(array_key_exists($field, $this->cor_query))
		{
			$field = $this->cor_query[$field];
		}
		// construct the request
		$sql = "SELECT	DISTINCT(".$field.") as val
				FROM	log ";
		
		$finalfilter ? $sql .= " WHERE ".$finalfilter : "";
		
		$order ? $sql .= " ORDER BY val ASC" : "";
		
		return $sql;
	}
	/**
	 * @desc build the list with all distinct result
	 * @param string $field the field from the request displayed in a array
	 * @return string $liste
	 */
	public function colFilter($field, $on = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$array_val = array();
		$sql = $this->getColFilterSql($field, $on);
		$rs = $conn->query($sql);
		
		while($row = $conn->fetch_assoc($rs))
		{
			$value = $row['val'];
			
			if($field == "appli")
			{
				$caption= implode(' ',phrasea::modulesName(@unserialize($row['val'])));
			}
			elseif($field == "DATE_FORMAT(log.date,'%Y-%m-%d')")
			{
				$date = explode("-", $row["val"]);
				$value = $date[0].'-'.$date[1].'-'.$date[2];
				$caption = phraseadate::getPrettyString(new DateTime($row['val']));
			}
			else
			{
				$caption = $row['val'];
			}
			$array_val[] = array('val' => $caption, 'value' => $value);
		}
		return $array_val;
	}

	/**
	 * @desc build the result from the specified sql
	 * @param array $champ all the field from the request displayed in a array
	 * @param string $sql the request from buildreq
	 * @return $this->result
	 */
	protected  function buildResult($rs)
	{
		$id_to_colname = $this->returnCollName();
		$i = 0;
		$conn = connection::getInstance($this->sbas_id);
		
		if($rs)
		{
			while(($row = $conn->fetch_assoc($rs)))
			{
				
				foreach($this->champ as $key => $value)
				{
					if($row[$value])
					{
						if($value == 'coll_list')
						{
							$coll = explode(",", $row[$value]);
							$this->result[$i][$value] = "";
							foreach($coll as $id)
							{
								if(($this->result[$i][$value] != "") && (isset($id_to_colname[$id])))
								{
									$this->result[$i][$value].= " / ";
									$this->result[$i][$value] .= $id_to_colname[$id];
								}
								elseif(($this->result[$i][$value] == "") && (isset($id_to_colname[$id])))
									$this->result[$i][$value] = $id_to_colname[$id];
								else
									$this->result[$i][$value] .= '';
							}		
						}
						elseif($value == 'appli')
						{
							$applis = false;
							if(($applis = @unserialize($row[$value])) !== false)
								if(empty($applis))
									$this->result[$i][$value] = '<i>'._('report:: non-renseigne').'</i>';
								else
									$this->result[$i][$value] = implode(' ',phrasea::modulesName($applis));					
							else
								$this->result[$i][$value] = '<i>'._('report:: non-renseigne').'</i>';
						}
						elseif($value == 'ddate')
						{
							$this->result[$i][$value] =  phraseadate::getPrettyString(new DateTime($row[$value]));
						}
						else
							$this->result[$i][$value] = $row[$value];	
					}
					else
						$this->result[$i][$value] = '<i>'._('report:: non-renseigne').'</i>';	
				}
				$i++;
			}
		}	
	}
	
	
	public static function getNbConn($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$coll_filter = parent::getCollectionFilter($list_coll_id);
		$finalfilter = "";
		
		//construct the final filter from the other ones
		if($datefilter)
			$finalfilter .= $datefilter.' AND ';
		if($coll_filter)
			$finalfilter .= '('.$coll_filter.') AND ';
		$finalfilter .= 'log.site="'.$conn->escape_string(GV_sit).'"';
		
		$sql = "	SELECT		COUNT(usrid) as nb
					FROM		log  
					WHERE ".$finalfilter;
		
		$rs = $conn->query($sql);	
		while($row = $conn->fetch_assoc($rs))
			$nb = $row['nb'];

		return $nb;
	}
}
?>