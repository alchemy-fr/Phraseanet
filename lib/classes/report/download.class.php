<?php
/*
 * short description
 * 
 */
class report_download extends report 
{
	
	protected $cor_query = array(
					'user'		=> 'log.user',
					'site' 		=> 'log.site',
					'societe'	=> 'log.societe',
					'pays' 		=> 'log.pays',
					'activite' 	=> 'log.activite',
					'fonction' 	=> 'log.fonction',
					'usrid' 	=> 'log.usrid',
					'coll_id' 	=> 'record.coll_id',
					'xml' 		=> 'record.xml',
					'ddate' 	=> "DATE_FORMAT(log.date, '%Y-%m-%d')",
					'id' 		=> 'log_docs.id',
					'log_id' 	=> 'log_docs.log_id',
					'record_id' => 'log_docs.record_id',
					'final' 	=> 'log_docs.final',
					'comment' 	=> 'log_docs.comment',
					'size' 		=> 'subdef.size'
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
				
		$this->title = _('report:: telechargements');
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
			SELECT  log.user, log.site, log.societe, log.pays, log.activite, log.fonction,log.usrid, record.coll_id, record.xml, DATE_FORMAT(log.date, '%Y-%m-%d' ) as ddate,
					log_docs.id, log_docs.log_id, log_docs.record_id , log_docs.final , log_docs.comment
			FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id )
			WHERE ";

			if($finalfilter)
				$this->req .= $finalfilter." AND (log_docs.action = 'download' OR log_docs.action = 'mail') AND (".$dl_coll_filter.")";
	
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
			
			if($name == 'record_id' && $on == 'DOC')
			{	
				$this->req = "SELECT	 TRIM(".$groupby.") as ".$name.", SUM(1) as telechargement ,record.coll_id, record.xml , log_docs.final , log_docs.comment, subdef.size, subdef.file, subdef.mime 
					FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id inner join subdef ON (log_docs.record_id = subdef.record_id AND subdef.name = log_docs.final))
					WHERE ";
			}
			elseif($on == 'DOC')
			{
				$this->req = "SELECT	 TRIM(".$groupby.") as ".$name.", SUM(1) as telechargement 
					FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id inner join subdef ON (log_docs.record_id = subdef.record_id AND subdef.name = log_docs.final))
					WHERE ";
			}
			else
			{
				$this->req = "SELECT TRIM(".$groupby.") as ".$name.", SUM(1) as nombre
					FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id )
					WHERE ";
			}
			
			if($finalfilter)
				$this->req .= $finalfilter." AND (log_docs.action = 'download' OR log_docs.action = 'mail') AND (".$dl_coll_filter.")";
			($on == 'DOC') ? $this->req .= "AND subdef.name =  'document' " : $this->req.= "";
		
			$this->req .= " GROUP BY ". $groupby;
			($name == 'record_id' && $on == 'DOC') ? $this->req .= " , final" : $this->req.= "";
			
			if($order)
				$this->req .= " ORDER BY ".$order;
			
			$rs = $conn->query($this->req);
			$this->total = $conn->num_rows($rs);
			
			if($name == 'record_id' && $on == 'DOC' && $limit)
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
			FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id inner join subdef ON log_docs.record_id = subdef.record_id)
			WHERE ";
		
		$finalfilter ? $sql .= $finalfilter." AND (log_docs.action = 'download' OR log_docs.action = 'mail')" : "";
			
		$on == 'DOC' ? $sql .= "AND subdef.name =  'document' " : $sql .= "";
		
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
	
		if($field == 'coll_id')
			$colname = parent::returnCollName();
			
		while($row = $conn->fetch_assoc($rs))
		{
			$value = $row['val'];
			
			if($field == 'coll_id')
			{
				$caption = $colname[$row['val']];
			}
			elseif($field == 'ddate')
			{
				$date = explode("-", $row["val"]);
				$value = $date[0].'-'.$date[1].'-'.$date[2];
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
						if($value == 'coll_id')
						{
							$this->result[$i]['coll_id'] = $id_to_colname[$row[$value]];
						}
						elseif($value =='xml' && (sizeof($pref) > 0))
						{
							foreach($pref as $key => $field)
							{
								$this->result[$i][$field] = parent::getChamp($row['xml'], $field);
							}
						}
						elseif($value == 'ddate')
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
	

	public static function getNbDl($dmin, $dmax, $sbas_id, $list_coll_id)
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
		$sql = 
		"
		SELECT sum(1) as nb
		FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id )
		WHERE $finalfilter 
		AND (log_docs.action = 'download' OR log_docs.action = 'mail')";
		
		$rs = $conn->query($sql);
		
		while($row = $conn->fetch_assoc($rs))
			is_null($row['nb']) ? $nb = 0 : $nb = $row['nb'];
		return $nb;
	}
	
	public static function getTopDl($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$coll_filter = parent::getCollectionFilter($list_coll_id);
		$finalfilter = "";
		$array = array();
			
		//construct the final filter from the other ones
		if($datefilter)
			$finalfilter .= $datefilter.' AND ';
		if($coll_filter)
			$finalfilter .= '('.$coll_filter.') AND ';
		$finalfilter .= 'log.site="'.$conn->escape_string(GV_sit).'"';
		$sql = 
		"
		SELECT record.record_id as id, sum(1) as nb, subdef.name, record.xml
		FROM (log inner join log_docs on log.id = log_docs.log_id inner join record on log_docs.record_id = record.record_id inner join subdef on subdef.record_id = record.record_id)
		WHERE $finalfilter 
		AND (log_docs.action = 'download' OR log_docs.action = 'mail') 
		AND (subdef.name = log_docs.final) 
		GROUP BY id, name
		ORDER BY nb DESC";
		
		$array['preview'] = array();
		$array['document'] = array();
		$rs = $conn->query($sql);
		
		while($row = $conn->fetch_assoc($rs))
		{
			if($row['name'] == "document")
			{
				$array['document'][$row['id'].'_'.$sbas_id]['nb'] = $row['nb'];
				$array['document'][$row['id'].'_'.$sbas_id]['file'] = parent::getChamp($row['xml'],'doc', 'originalname');
				$array['document'][$row['id'].'_'.$sbas_id]['sbasid'] = $sbas_id;
			}
			elseif($row['name'] == "preview")
			{
				$array['preview'][$row['id'].'_'.$sbas_id]['nb'] = $row['nb'];
				$array['preview'][$row['id'].'_'.$sbas_id]['file'] = parent::getChamp($row['xml'],'doc', 'originalname');
				$array['preview'][$row['id'].'_'.$sbas_id]['sbasid'] = $sbas_id;
			}

		}
		return $array;
	}
}
?>