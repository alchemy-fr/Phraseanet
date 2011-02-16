<?php

/**
 *short description
 */

class report_activity extends report{
	
	/* number of questions displayed for the most asked questions*/
	private $nb_top = 20;
	private $total_connexion = 0;
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

	
	public function __construct($arg1, $arg2, $sbas_id, $collist)
	{
		parent::__construct($arg1, $arg2, $sbas_id, $collist);
	}
	
	
	public function setTop($nb_top)
	{
		$this->nb_top = $nb_top;
	}
	
	/**
	 * 
	 *
	 */
	private function setDisplayForActivity($rs)
	{
		$conn = connection::getInstance($this->sbas_id);
		$num_fields = $conn->num_fields($rs);
		$j = 0;
		$hours = array();
		
		for($i = 0;$i < 24; $i++)
		{
			array_push($this->display, $i);
			$hours[$i] = 0;
		}
	
		while ($j < $num_fields)
		{
			array_push($this->champ, $conn->field_name($rs, $j));
			$j++;
		}
		return $hours;
	}
	
	private function setTotal($sql)
	{
		$conn = connection::getInstance($this->sbas_id);
		if($rs_total = $conn->query($sql))
			$this->total = $conn->num_rows($rs_total);
	}
	
	
	/**
	 * @desc get the site activity per hours
	 * @return array
	 */
	public function getActivityPerHours()
	{
		$result = array();
		$this->title = _('report:: activite par heure');
		
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		//get all filter
		extract($tab_filter);
		
		$sql = "
				SELECT DATE_FORMAT( log.date, '%k' ) AS heures, SUM(1) AS nb
				FROM log
				WHERE (log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."') 
				AND (".$collfilter.")
				AND log.site = '".$conn->escape_string(GV_sit)."'
				GROUP BY heures
				ORDER BY heures ASC";
		
		if($rs = $conn->query($sql))
		{
			$res = $this->setDisplayForActivity($rs);
			$this->initDefaultConfigColumn($this->display);
			
			while( ($row = $conn->fetch_assoc($rs)))
			{
				$row['heures'] = (string)$row['heures'];
				$res[$row['heures']] = round(($row['nb'] / 24), 2);
				if($res[$row['heures']] < 1)
					$res[$row['heures']] = number_format($res[$row['heures']],2);
				else
					$res[$row['heures']] = (int)$res[$row['heures']];
			}						
			
			$this->result[] = $res;
			//calculate prev and next page
			$this->calculatePages($rs);
			//do we display navigator ?
			$this->setDisplayNav();
			//set report
			$this->setReport();
			$conn->free_result($rs);
			
			$this->report['legend'] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);
		}
		return $this->report;
	}
	
	/**
	 * @desc get all questions by user 
	 * @param string $idUser
	 */
	public function getAllQuestionByUser($value, $what)
	{
		$result = array();
	 	$conn = connection::getInstance($this->sbas_id);
	 	$tab_filter = parent::buildFilter($conn);
	 	//get all filter	
		extract($tab_filter);
		$sql = "
				SELECT DATE_FORMAT(log_search.date,'%Y-%m-%d %H:%i:%S') as date ,log_search.search ,log_search.results
				FROM (log_search inner join log on log.id = log_search.log_id)
				WHERE log_search.date > '".$conn->escape_string($this->dmin)."' AND log_search.date < '".$conn->escape_string($this->dmax)."' AND log.".$what." = '".$conn->escape_string($value)."'
				AND log.site ='".$conn->escape_string(GV_sit)."'
				AND (".$collfilter.")
				ORDER BY date";
		$this->getTotal($sql);		
		$sql .= " LIMIT ".$limit;

	
		
		if($rs = $conn->query($sql))
		{
			$this->setDisplay($rs);
			$this->initDefaultConfigColumn($this->display);
			$i = 0;
			
			while($row = $conn->fetch_assoc($rs))
			{
				foreach($this->champ as $key => $value)
					$result[$i][$value] = $row[$value];
				$i++;
			}
			$conn->free_result($rs);
		}
		
		$this->setResult(_('report:: questions'), $result);
		return $this->result;
	}
	
	/**
	 * get the most asked question
	 * @param array $tab config for html table
	 * @param bool $no_answer true for question with no answer 
	 */
	public function getTopQuestion($tab = false, $no_answer = false)
	{
		$this->report['value']	= array();
		$this->report['value2']	= array();
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);	
		extract($tab_filter);
		$i = 0;
		($no_answer) ? $this->title = _('report:: questions sans reponses') : $this->title = _('report:: questions les plus posees');
		
		$sql =  "
				SELECT TRIM(log_search.search) as search, SUM(1) as nb, ROUND(avg(results)) as nb_rep 
				FROM (log_search inner join log on log_search.log_id = log.id)
				WHERE log_search.date > '".$conn->escape_string($this->dmin)."' AND log_search.date < '".$conn->escape_string($this->dmax)."'
				AND log_search.search != 'all'
				AND (".$collfilter.")";
		
		($no_answer) ? $sql .= " AND log_search.results = 0 " : "";
		$sql .= "
				GROUP BY log_search.search 
				ORDER BY nb DESC";
		
		(!$no_answer) ? $sql .= " LIMIT 0,".$this->nb_top : "";
		
		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				foreach($this->champ as $key => $value)
					$this->result[$i][$value] = $row[$value];
				$i++;
				$this->report['legend'][] = $row['search'];
				$this->report['value'][] = $row['nb'];
				$this->report['value2'][] = $row['nb_rep'];
			}
			
			$this->total = sizeof($this->result);
			//calculate prev and next page
			$this->calculatePages($rs);
			//do we display navigator ?
			$this->setDisplayNav();
			//set report
			$this->setReport();
			$conn->free_result($rs);
		}	
		return $this->report;
	}
	
	/**
	 * @desc get all downloads from one specific user
	 * @param $usr user id
	 * @param array $config config for the html table
	 * @return array
	 */
	public function getAllDownloadByUserBase($usr, $config = false)
	{
		$result = array();
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		//get all filter
		extract($tab_filter);	
		$sql =  "
				SELECT record.xml as titre, log_docs.record_id, log_docs.date, log_docs.final as objets 
				FROM (`log_docs` inner join log on log_docs.log_id = log.id inner join record on log_docs.record_id = record.record_id)
				WHERE log_docs.action = 'download'
				AND log_docs.date > '".$conn->escape_string($this->dmin)."' AND log_docs.date < '".$conn->escape_string($this->dmax)."'
				AND log.usrid = '".$conn->escape_string($usr)."'
				AND log.site ='".$conn->escape_string(GV_sit)."'
				AND (".$collfilter.")";
		$this->getTotal($sql);
		$sql .= "
				ORDER BY date DESC
				LIMIT 0, 30";
		
		$this->initialize();

		if($rs = $conn->query($sql))
		{
			$login = parent::getUsrLogin($usr);
			$this->setDisplay($rs);
			($config) ? $this->setConfigColumn($config) : $this->initDefaultConfigColumn($this->display);
			$i = 0;
			
			while($row = $conn->fetch_assoc($rs))
			{
				foreach($this->champ as $key => $value)
				{
					if($value == 'titre')
						$result[$i][$value] = parent::getChamp($row[$value], 'Titre');
					else
						$result[$i][$value] = $row[$value];
				}
				$i++;
			} 
			$conn->free_result($rs);
			$this->setResult(sprintf(_('report:: Telechargement effectue par l\'utilisateur %s'),$login), $result);
		}
		
		return $this->result;
	}
	/**
	 * @desc get all download by base by day
	 * @param array $tab config for html table
	 * @return array
	 */
	public function getDownloadByBaseByDay($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		extract($tab_filter);
		$this->title = _('report:: telechargements par jour');
		
		$sql = "SELECT DISTINCT (
				DATE_FORMAT( log_docs.date,  '%Y-%c-%d' )
				) AS ddate, COUNT( DATE_FORMAT( log_docs.date,  '%d' ) ) AS download, final
				FROM (
				log_docs 
				INNER JOIN record ON record.record_id = log_docs.record_id
				INNER JOIN log ON log.site =  '".$conn->escape_string(GV_sit)."'
				AND log.id = log_docs.log_id
				LEFT JOIN subdef AS s ON log_docs.action =  'download'
				AND s.record_id = log_docs.record_id
				AND s.name = log_docs.final)
				WHERE log_docs.date > '".$conn->escape_string($this->dmin)."' AND log_docs.date < '".$conn->escape_string($this->dmax)."' AND (log_docs.final ='document' OR log_docs.final = 'preview')
				AND (".$collfilter.") AND (".$dl_coll_filter.")
				GROUP BY ddate, final
				WITH rollup";

		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
			$save_date = "";
			$total = array('tot_doc' => 0, 'tot_prev' => 0,'tot_dl' => 0);
			$i = -1;
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				$date = $row['ddate'];
				(($save_date != $date) && !is_null($date)) ? $i++ : "";
					
				if(!is_null($date))
				{
					$this->result[$i]['ddate'] = phraseadate::getPrettyString(new DateTime($date));
				}
				
				if($row['final'] == 'document')
				{
					$this->result[$i]['document'] = $row['download'];
					$total['tot_doc'] += $row['download'];
				}
				elseif($row['final'] == 'preview')
				{
					$this->result[$i]['preview'] = $row['download'];
					$total['tot_prev'] += $row['download'];
				}
				
				(is_null($row['final']) && !is_null($row['ddate'])) ? $this->result[$i]['total'] = $row['download'] : "";
					
				(is_null($row['final']) && is_null($row['ddate'])) ? $total['tot_dl'] = $row['download']: "";
					
				(!isset($this->result[$i]['preview'])) ? $this->result[$i]['preview'] = 0 : "";
				
				(!isset($this->result[$i]['document'])) ? $this->result[$i]['document'] = 0 : "";

				$save_date = $date;
			}
			
			$nb_row = $i + 1;
			$this->total = $nb_row;
			
			if($this->total > 0)
			{
				$this->result[$nb_row]['ddate']  = '<b>TOTAL</b>';
				$this->result[$nb_row]['document']  = '<b>'.$total['tot_doc'].'</b>';
				$this->result[$nb_row]['preview']  = '<b>'.$total['tot_prev'].'</b>';
				$this->result[$nb_row]['total']  = '<b>'.$total['tot_dl'].'</b>';
			}
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
	/**
	 * @desc get nb connexion by user , fonction ,societe etc..
	 * @param array $tab config for html table
	 * @param string $on choose the field on what you want the result
	 * @return array
	 */
	public function getConnexionBase($tab = false, $on )
	{	
		//default group on user column
		if(empty($on))
		{
			$on = "user";
		}
		
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);	
		//get all filter
		extract($tab_filter);
		
		$this->req = "SELECT  DISTINCT(log.".$on.") as ".$on.", usrid, SUM(1) as connexion
				FROM log 
				WHERE log.user != 'API' 
				AND log.site = '".$conn->escape_string(GV_sit)."'
				AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."'
				AND (".$collfilter.")
				GROUP BY ".$on."
				ORDER BY connexion DESC ";
		//set total
		$this->setTotal($this->req);		
		//get limit
		$limit ? $this->req .= "LIMIT 0,".$this->nb_record: "";
		
		if($rs = $conn->query($this->req))
		{
			$i = 0;
			//set title
			$this->title = _('report:: Detail des connexions');
			//set champ
			$this->champ = array($on,'connexion');
			//set display
			$this->default_display = array($on,'connexion');
			($tab) ? $this->setConfigColumn($tab) : $this->initDefaultConfigColumn($this->default_display);
			//build result
			while(($row = $conn->fetch_assoc($rs)) && ($i < $this->nb_record))
			{
				foreach($this->champ as $key => $value)
				{
					$this->result[$i][$value] = empty($row[$value]) ? "<i>"._('report:: non-renseigne')."</i>" : $row[$value];
					if($value == 'connexion')
						$this->total_connexion += $row['connexion'];
				}
				$this->result[$i]['usrid'] = $row['usrid'];
				$i++;
			}
			$conn->free_result($rs);
			
			if ($this->total > 0)
			{
				$this->result[$i]['usrid'] = 0;
				$this->result[$i]['connexion'] = '<b>'.$this->total_connexion.'</b>';
				$this->result[$i][$on] ='<b>TOTAL</b>';
			}
			//calculate prev and next page
			$this->calculatePages($rs);
			//do we display navigator ?
			$this->setDisplayNav();
			//set report
			$this->setReport();
		}
		return $this->report;
	}
	
	/**
	 * @desc get the deail of download by users
	 * @param bool $ext false for your appbox conn, true for external connections
	 * @param array $tab config for the html table
	 * @return array
	 */
	
	public function getDetailDownload($tab = false, $on)
	{
		empty($on) ? $on = "user" :""; //by default always report on user
		
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		//get all filter
		extract($tab_filter);
		$this->title = _('report:: Detail des telechargements');
					
		$sql = "
				SELECT usrid, TRIM(".$on.") as ".$on.", final, sum(1) as nb, sum(size) as poid 
				FROM (log_docs as d 
				INNER JOIN log ON log.site = '".$conn->escape_string(GV_sit)."' AND log.id = d.log_id AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')
				INNER JOIN record ON record.record_id = d.record_id 
				LEFT JOIN subdef as s on (d.action = 'download' OR d.action = 'mail') AND s.record_id=d.record_id and s.name=d.final
				WHERE (".$collfilter.") AND (".$dl_coll_filter.") 
				GROUP BY ".$on.", final, usrid 
				WITH rollup";
		
		if($rs = $conn->query($sql))
		{
			$save_user = "";
			$i = -1;
			$total = array('nbdoc' => 0, 'poiddoc' => 0, 'nbprev' => 0, 'poidprev' => 0);
			
			$this->setChamp($rs);
			
			$this->setDisplay($tab);
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				$user = $row[$on];
				if(($save_user != $user) && !is_null($user))
					$i++;
				//doc info
				if($row['final'] == 'document' && !is_null($user) && !is_null($row['usrid'])) 
				{
					$this->result[$i]['nbdoc'] = (!is_null($row['nb']) ? $row['nb'] : 0);
					$this->result[$i]['poiddoc'] = (!is_null($row['poid']) ? parent::unite($row['poid']) : 0);
					if(!isset($this->result[$i]['nbprev']))
						$this->result[$i]['nbprev'] = 0;
					if(!isset($this->result[$i]['poidprev']))
						$this->result[$i]['poidprev'] = 0;
					$this->result[$i]['user'] = empty($row[$on]) ? "<i>"._('report:: non-renseigne')."</i>" : $row[$on];
					$total['nbdoc'] += $this->result[$i]['nbdoc'];
					$total['poiddoc'] += (!is_null($row['poid']) ? $row['poid'] : 0);
					$this->result[$i]['usrid'] = $row['usrid'];
				}
				//preview info
				if($row['final'] == 'preview' && !is_null($user) && !is_null($row['usrid']))
				{
					if(!isset($this->result[$i]['nbdoc']))
						$this->result[$i]['nbdoc'] = 0;
					if(!isset($this->result[$i]['poiddoc']))
						$this->result[$i]['poiddoc'] = 0;
					$this->result[$i]['nbprev'] = (!is_null($row['nb']) ? $row['nb'] : 0);
					$this->result[$i]['poidprev'] = (!is_null($row['poid']) ? parent::unite($row['poid']) : 0);
					$this->result[$i]['user'] = empty($row[$on]) ? "<i>"._('report:: non-renseigne')."</i>" : $row[$on];
					$total['nbprev'] += $this->result[$i]['nbprev'];
					$total['poidprev'] += (!is_null($row['poid']) ? $row['poid'] : 0);
					$this->result[$i]['usrid'] = $row['usrid'];
				}
				$save_user = $user;
			}
			
			$nb_row = $i + 1;
			$this->total = $nb_row;
			
			if($this->total > 0)
			{
				$this->result[$nb_row]['user']  = '<b>TOTAL</b>';
				$this->result[$nb_row]['nbdoc']  = '<b>'.$total['nbdoc'].'</b>';
				$this->result[$nb_row]['poiddoc']  = '<b>'.parent::unite($total['poiddoc']).'</b>';
				$this->result[$nb_row]['nbprev']  = '<b>'.$total['nbprev'].'</b>';
				$this->result[$nb_row]['poidprev']  = '<b>'.parent::unite($total['poidprev']).'</b>';
			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
	
	
	public function getPush($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);
		$id_to_colname = parent::returnCollName();
		//get all filter
		extract($tab_filter);
		$push = array();
		$sql = "
			SELECT log.usrid, log.user , d.final as getter,  d.record_id, d.date, s.*
			FROM (log_docs as d 
			INNER JOIN log ON log.site = '".$conn->escape_string(GV_sit)."' AND log.id = d.log_id AND log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')
			INNER JOIN record ON record.record_id = d.record_id
			LEFT JOIN subdef as s ON s.record_id=d.record_id and s.name='document'
			WHERE (".$collfilter.") AND (".$dl_coll_filter.") AND d.action='push'
		";
	
		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			
			$this->setDisplay($tab);
			 $i = 0;
			while($row = $conn->fetch_assoc($rs))
			{
				foreach($this->champ as $key => $value)
				{
					$value == "getter" ? $this->result[$i][$value] = user::getInfos($row[$value]) : $this->result[$i][$value] = $row[$value];
					if($value == "size")
						$this->result[$i][$value] = parent::unite($row[$value]);
					elseif($value == "date")
						$this->result[$i][$value] =  phraseadate::getPrettyString(new DateTime($row[$value]));
				}
				$i++;
			}
			
			
			$this->total = sizeof($this->result);
			//calculate prev and next page
			$this->calculatePages($rs);
			//do we display navigator ?
			$this->setDisplayNav();
			//set report
			$this->setReport();
			
			$conn->free_result($rs);
		}
		
		return($this->report);
	}
	
	
	public static function topTenUser($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql =  "
				SELECT log.usrid, user, final, sum(1) AS nb, sum(size) AS poid 
				FROM (log_docs AS d 
				INNER JOIN log ON log.site='".$conn->escape_string(GV_sit)."' 
				AND log.id = d.log_id 
				AND ".$datefilter.")
				LEFT JOIN subdef AS s ON d.action = 'download' 
				AND s.record_id = d.record_id 
				AND s.name = d.final		
				AND (".$collfilter.") 
				GROUP BY user, final 
				WITH rollup";
		
		if($rs = $conn->query($sql))
		{
			$save_id ="";
			while(($row = $conn->fetch_assoc($rs)))
			{
				$id = $row['usrid'];
				if(!is_null($row['usrid']) && !is_null($row['user']) && !is_null($row['final']) && !is_null($row['nb']) && !is_null($row['poid']) )
				{
					$result[$id]['user'] = $row['user'];
					if($row['final'] == 'document') 
					{
						$result[$id]['nbdoc'] = (!is_null($row['nb']) ? $row['nb'] : 0);
						$result[$id]['poiddoc'] = (!is_null($row['poid']) ? $row['poid'] : 0);
						if(!isset($result[$id]['nbprev']))
							$result[$id]['nbprev'] = 0;
						if(!isset($result[$id]['poidprev']))
							$result[$id]['poidprev'] = 0;
					}
					if($row['final'] == 'preview')
					{
						if(!isset($result[$id]['nbdoc']))
							$result[$id]['nbdoc'] = 0;
						if(!isset($result[$id]['poiddoc']))
							$result[$id]['poiddoc'] = 0;
						$result[$id]['nbprev'] = (!is_null($row['nb']) ? $row['nb'] : 0);
						$result[$id]['poidprev'] = (!is_null($row['poid']) ? $row['poid'] : 0);
					}
				}
				$save_id = $id;
			}
			$conn->free_result($rs);
		}
		return $result;
	}
	

	public static function activity($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$res = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql =  "SELECT log.id, HOUR(log.date) as heures 
				FROM log
				WHERE ".$datefilter."
				AND (".$collfilter.") 
				AND log.site = '".$conn->escape_string(GV_sit)."'";

		if($rs = $conn->query($sql))
		{
			for($i = 0;$i < 24; $i++)
				$res[$i] = 0;
				
			while( ($row = $conn->fetch_assoc($rs)) )
			{
				$total = $conn->num_rows($rs);
				if($total > 0)
					$res[$row["heures"]]++;
			}
		
			foreach($res as $heure => $value)
				$res[$heure] = (float)number_format(round(($value / 24), 2), 2);
			$conn->free_result($rs);
		}
		return $res;			
	}
	
	public static function activityDay($dmin, $dmax, $sbas_id, $list_coll_id, $nb_days)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$res = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT DISTINCT (
				DATE_FORMAT( log.date, '%d-%m-%Y' )
				) AS ddate, COUNT( DATE_FORMAT( log.date, '%d' ) ) AS activity
				FROM log
				WHERE ".$datefilter."
				AND log.site ='".$conn->escape_string(GV_sit)."'
				AND (".$collfilter.")
				GROUP by ddate
				ORDER BY ddate DESC";

		if($rs = $conn->query($sql))
		{
			$num_fields = $conn->num_fields($rs);
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				$c = 0;
				while($c < $num_fields)
				{
					$value = $conn->field_name($rs, $c);
					if($value == 'ddate')
					{
						$date = phraseadate::getPrettyString(new DateTime($row['ddate']));
					}
					else	
						$connexions = $row[$value];
					$c++;
				}
				$result[$date] = $connexions;
			}
			
			foreach($result as $key => $act)
			{
				$res[$key] = (float)number_format($act,2);
			}
			$conn->free_result($rs);
		}
		return $res;
	}
	
	public static function activityQuestion($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT log.usrid, log.user, sum(1) AS nb 
				FROM `log_search` 
				INNER JOIN log 
				ON log_search.log_id = log.id
				WHERE ".$datefilter."
				AND log.site ='".$conn->escape_string(GV_sit)."' 
				AND (".$collfilter.")
				GROUP BY log.usrid 
				ORDER BY nb DESC";
		
		if($rs = $conn->query($sql))
		{
			while( ($row = $conn->fetch_assoc($rs)) )
			{
				$result[$row['usrid']]['user'] = $row['user'];
				$result[$row['usrid']]['nb'] = $row['nb'];
			}
			$conn->free_result($rs);
		}
		return $result;			
	}
	
	public static function activiteTopQuestion($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);	
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT TRIM(log_search.search) as question, log.usrid, log.user, sum(1) AS nb 
				FROM `log_search` 
				INNER JOIN log 
				ON log_search.log_id = log.id
				WHERE ".$datefilter."
				AND log.site ='".$conn->escape_string(GV_sit)."' 
				AND (".$collfilter.")
				GROUP BY log_search.search 
				ORDER BY nb DESC";
		
		if($rs = $conn->query($sql))
		{
			$conv = array(" " => "");
			while( ($row = $conn->fetch_assoc($rs)) )
			{
				$question = $row['question'];
				$question = mb_strtolower(strtr($question, $conv));
				$result[$question]['question'] = $row['question'];
				$result[$question]['nb'] = $row['nb'];
			}
			$conn->free_result($rs);
		}
		return $result;
	}
	//AND referrer NOT LIKE '".$conn->escape_string(GV_ServerName)."%'
	public static function activiteTopTenSiteView($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT referrer, COUNT(referrer) as nb_view
				FROM log_view 
				INNER JOIN log
				ON log_view.log_id = log.id
				WHERE ".$datefilter."
				AND (".$collfilter.")
				GROUP BY referrer
				ORDER BY nb_view DESC ";

		if($rs = $conn->query($sql))
		{
			while( ($row = $conn->fetch_assoc($rs)) )
			{
				($row['referrer'] != 'NO REFERRER') ? $host = parent::getHost($row['referrer']) : $host = 'NO REFERRER';
				!isset($result[$host]) ? $result[$host] = 0 : "";
				$result[$host] += ($row['nb_view']);
			}
			$conn->free_result($rs);
		}
		return $result;
	}	 
	
	public static function activiteAddedDocument($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT DISTINCT (
				DATE_FORMAT( log_docs.date, '%d-%m-%Y' )
				) AS ddate, COUNT( DATE_FORMAT( log_docs.date, '%d' ) ) AS activity
				FROM log_docs
				INNER JOIN log
				ON log_docs.log_id = log.id
				WHERE ".$datefilter." AND log_docs.action = 'add'
				AND (".$collfilter.")
				GROUP BY ddate
				ORDER BY activity DESC ";
		
		if($rs = $conn->query($sql))
		{
			$num_fields = $conn->num_fields($rs);
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				$c = 0;
				while($c < $num_fields)
				{
					$value = $conn->field_name($rs, $c);
					if($value == 'ddate')
					{
						$date= phraseadate::getPrettyString(new DateTime($row['ddate']));
					}
					else	
						$download = $row[$value];
					$c++;
				}
				$result[$date] = $download;
			}
		}
		return $result;
	}
	
	public static function activiteEditedDocument($dmin, $dmax, $sbas_id, $list_coll_id)
	{
		$conn = connection::getInstance($sbas_id);
		$result = array();
		$datefilter = parent::getDateFilter($dmin, $dmax);
		$collfilter = parent::getCollectionFilter($list_coll_id);
		$sql = "SELECT DISTINCT (
				DATE_FORMAT( log_docs.date, '%d-%m-%Y' )
				) AS ddate, COUNT( DATE_FORMAT( log_docs.date, '%d' ) ) AS activity
				FROM log_docs
				INNER JOIN log
				ON log_docs.log_id = log.id
				WHERE ".$datefilter." AND log_docs.action = 'edit'
				AND (".$collfilter.")
				GROUP BY ddate
				ORDER BY activity DESC ";

		if($rs = $conn->query($sql))
		{
			$num_fields = $conn->num_fields($rs);
			
			while(($row = $conn->fetch_assoc($rs)))
			{
				$c = 0;
				while($c < $num_fields)
				{
					$value = $conn->field_name($rs, $c);
					if($value == 'ddate')
					{
						$date= phraseadate::getPrettyString(new DateTime($row['ddate']));
					}
					else	
						$download = $row[$value];
					$c++;
				}
				$result[$date] = $download;
			}
		}
		return $result;
	}
}