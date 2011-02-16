
<?php


/*
 * short description
 * 
 */
class report_question extends report 
{

	protected $cor_query = array(
		'user' 			=> 'log.user'
		,'usrid' 		=> 'log.usrid'
		,'ddate' 		=> 'DATE_FORMAT(log_search.date, "%Y-%m-%d")'
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
	 * @name download::__construct()
	 * @param $arg1 start date of the  report
	 * @param $arg2 end date of the report
	 * @param $sbas_id id of the databox
	 */
	public function __construct($arg1, $arg2, $sbas_id, $collist)
	{
		parent::__construct($arg1, $arg2, $sbas_id, $collist);
		$this->title = _('report:: question');
	}
	

	
	
	/**
	 * @desc build the specified requete
	 * @param $obj $conn the current connection to databox
	 * @return string
	 */
	protected function buildReq($conn, $groupby = false, $on = false)
	{
		$tab_filter = parent::buildFilter($conn);	
		extract($tab_filter);
		$conn = connection::getInstance($this->sbas_id);

		if($groupby == false)
		{
			$this->req = 
			"
			SELECT DATE_FORMAT(log_search.date, '%Y-%m-%d') AS ddate, search, usrid, user, pays, societe, activite, fonction 
			FROM `log_search`
			INNER JOIN log 
			ON log.id = log_search.log_id
			WHERE ";

			if($finalfilter)
				$this->req .= $finalfilter ;
	
			if($order)
				$this->req .= " ORDER BY ".$order;
				
			$rs = $conn->query($this->req);
			$this->total = $conn->num_rows($rs);
			
			if($limit)
				$this->req .= " LIMIT ".$limit;
		}
		else
		{
			if($groupby == 'ddate')
				$field = "DATE_FORMAT(log_search.date,'%Y-%m-%d')";
			else
				$field = $groupby;
				
			$this->req = 
			"
			SELECT	TRIM(".$field.") as ".$groupby.", SUM(1) as nb
			FROM `log_search`
			INNER JOIN log 
			ON log.id = log_search.log_id
			WHERE ";

			$finalfilter ? $this->req .= $finalfilter : "";
			$this->req .= " GROUP BY ". $groupby;
			$this->req .= " ORDER BY nb DESC";
			
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
		
		$sql = "
			SELECT DISTINCT(".$field.") as val
			FROM `log_search`
			INNER JOIN log 
			ON log.id = log_search.log_id
			WHERE ";

		$finalfilter ? $sql .= $finalfilter : "";

		$sql .= " ORDER BY ".$field." ASC";
		
		$limit ? $sql .= " LIMIT ".$limit : "";
		
		return $sql;
		
	}
	
	public function colFilter($field, $on = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$array_val = array();
		$sql = $this->getColFilterSql($field, $on);
		$rs = $conn->query($sql);
		
		while($row = $conn->fetch_assoc($rs))
		{
			$value = $row['val'];
			
			if($field == 'appli')
			{
				$caption= implode(' ',phrasea::modulesName(@unserialize($row['val'])));
			}
			elseif($field == "ddate")
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
	protected function buildResult($rs)
	{
		$tab = array();
		$i = 0;
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
						if($value == 'ddate')
						{
							$this->result[$i][$value] =  phraseadate::getPrettyString(new DateTime($row[$value]));
						}
						else
						{
							$this->result[$i][$value] = $row[$value];
						}
					}	
					else
					{
						$this->result[$i][$value] = "<i>"._('report:: non-renseigne')."</i>";
					}
				}
				$i++;
			}
		}
	}
}	

?>