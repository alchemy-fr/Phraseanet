<?php
/*
 * short description
 * 
 */
class report_push extends report 
{
	
	protected $cor_query = array(
					'user'		=> 'log.user',
					'site' 		=> 'log.site',
					'societe'	=> 'log.societe',
					'pays' 		=> 'log.pays',
					'activite' 	=> 'log.activite',
					'fonction' 	=> 'log.fonction',
					'usrid' 	=> 'log.usrid',
					'getter' 		=> 'd.final',
					'date' 	=> "d.date",
					'id' 		=> 'd.id',
					'log_id' 	=> 'd.log_id',
					'record_id' => 'd.record_id',
					'final' 	=> 'd.final',
					'comment' 	=> 'd.comment',
					'size' 		=> 's.size'
	);
	
	/**
	 * constructor
	 * 
	 * @name download::__construct()
	 * @param $arg1 start date of the  report
	 * @param $arg2 end date of the report
	 * @param $sbas_id id of the databox
	 */
	public function __construct($arg1, $arg2, $sbas_id, $collist)
	{
		parent::__construct($arg1, $arg2, $sbas_id, $collist);
				
		$this->title = _('report:: push');
	}
	

	/**
	 * @desc build the specified requete
	 * @param $obj $conn the current connection to databox
	 * @return string
	 */
	protected function buildReq($groupby = false, $on = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter();	
		extract($tab_filter);
		
		if($groupby == false)
		{
			$this->req = 
			"
			SELECT log.usrid, log.user , d.final as getter,  d.record_id, d.date, s.*
			FROM (log_docs as d 
			INNER JOIN log ON log.site = '".$conn->escape_string(GV_sit)."' AND log.id = d.log_id AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')
			INNER JOIN record ON record.record_id = d.record_id
			LEFT JOIN subdef as s ON s.record_id=d.record_id and s.name='document'
			WHERE";

			if($finalfilter)
				$this->req .= $finalfilter." AND (d.action = 'push') AND (".$dl_coll_filter.")";
	
			if($order)
				$this->req .= " ORDER BY ".$order;
			
			$rs = $conn->query($this->req);
		
			$this->total = $conn->num_rows($rs);
			
			if($limit)
				$this->req .= " LIMIT ".$limit;
		}
		else
		{
			$name = $groupby;
			if(array_key_exists($groupby, $this->cor_query))
				$groupby = $this->cor_query[$groupby];
				
			$this->req = "SELECT TRIM(".$groupby.") as ".$name.", SUM(1) as nombre
								FROM (log_docs as d 
								INNER JOIN log ON log.site = '".$conn->escape_string(GV_sit)."' AND log.id = d.log_id AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')
								INNER JOIN record ON record.record_id = d.record_id
								LEFT JOIN subdef as s ON s.record_id=d.record_id and s.name='document'
								WHERE ";
		
			
			if($finalfilter)
				$this->req .= $finalfilter." AND (d.action = 'push') AND (".$dl_coll_filter.")";
						
			$this->req .= " GROUP BY ". $groupby;
			
			if($order)
				$this->req .= " ORDER BY ".$order;
			
			$rs = $conn->query($this->req);
			$this->total = $conn->num_rows($rs);
			
			if($limit)
				$this->req .= " LIMIT ".$limit;				
		}
	}
	
	private function getColFilterSql($field, $on)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);	
		// get all filter
		extract($tab_filter);
		//get "sql" field name 
		if(array_key_exists($field, $this->cor_query))
		{
			$field = $this->cor_query[$field];
		}
		
		$sql = "	
			SELECT	DISTINCT(".$field.") as val
			FROM (log_docs as d 
			INNER JOIN log ON log.site = '".$conn->escape_string(GV_sit)."' AND log.id = d.log_id AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')
			INNER JOIN record ON record.record_id = d.record_id
			LEFT JOIN subdef as s ON s.record_id=d.record_id and s.name='document'
			WHERE  ";
		
		$finalfilter ? $sql .= $finalfilter." AND (d.action = 'push')" : "";
		
		$order ? $sql .= " ORDER BY ".$order : "";

		$limit ? $sql .= " LIMIT ".$limit : "";
		
		return $sql;
	}
	
	public function colFilter($field, $on = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$sql = $this->getColFilterSql($field, $on);
		
		$rs = $conn->query($sql);
		$array_val = array();
			
		while($row = $conn->fetch_assoc($rs))
		{
			$value = $row['val'];
			if($field == "getter")
			{
				$caption = user::getInfos($row['val']);
			}
			elseif($field == 'date')
			{
				$caption = phraseadate::getPrettyString(new DateTime($row['val']));
			}
			elseif($field == 'size')
			{
				$caption = parent::unite($row['val']);
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
	protected function buildResult($rs)
	{
		$i = 0;
		$pref = parent::getPreff($this->sbas_id);
		$id_to_colname = parent::returnCollName();
		$conn = connection::getInstance($this->sbas_id);
	
		if($rs)
		{
			while(($row = $conn->fetch_assoc($rs)) && ($i < $this->nb_record))
			{
				foreach($this->champ as $key => $value)
				{
					if($row[$value])
					{
						if($value == 'getter')
						{
							$this->result[$i]['getter'] = user::getInfos($row[$value]);
						}
						elseif($value == 'date')
						{
							$this->result[$i][$value] =  phraseadate::getPrettyString(new DateTime($row[$value]));
						}
						elseif($value == 'size')
						{
							$this->result[$i][$value] = parent::unite($row[$value]);
						}
						else
							$this->result[$i][$value] = $row[$value];
					}
					else
					{
						if($value == 'comment')
						{
							$this->result[$i][$value] = '&nbsp;';
						}
						else
						{
							$this->result[$i][$value] = '<i>'._('report:: non-renseigne').'</i>';
						}
					}
				}
				$i++;
			}//end while
		}
	}
}
?>