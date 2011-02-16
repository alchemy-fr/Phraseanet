<?php

class report_nav extends report{

	/**
	 * @desc total of record on current report
	 * @var string
	 */
	private $total_pourcent;
	public $config = false;
	
	/**
	 * constructor
	 * 
	 * @name nav::__construct()
	 * @param $arg1 start date of the report
	 * @param $arg2 end date of the report
	 * @param $sbas_id databox id
	 */
	public function __construct($arg1, $arg2, $sbas_id, $collist)
	{
		parent::__construct($arg1, $arg2, $sbas_id, $collist);
		$this->total_pourcent =$this->setTotalPourcent();
	}
	
	private function setTotalPourcent()
	{
		$conn = connection::getInstance($this->sbas_id);
		$tab_filter = parent::buildFilter($conn);	
		extract($tab_filter);
		$sql = 'SELECT SUM(1) as total
				FROM log
				WHERE date > "'.$conn->escape_string($this->dmin).'" AND date < "'.$conn->escape_string($this->dmax).'" AND nav != TRIM("")';
		$sql .=
		" AND log.site = '".$conn->escape_string(GV_sit)."' AND (".$collfilter.")";
		
		$rs = $conn->query($sql);
		$row = $conn->fetch_assoc($rs);
		
		return $row['total'];
	}
	/**
	 * @desc empty $champ, $result, $display, $display_value 
	 * @return void
	 */
	private function initialize()
	{
		$this->report['legend']	= array();
		$this->report['value']	= array();
		$this->result = array();
		$this->champ = array();
		$this->default_display = array();
		$this->display = array();
	}
	
	/**
	 * @desc return the filter to generate the good request
	 * @param object $conn the current connexion to appbox
	 * @return string
	 */
	private function getFilter($conn)
	{
		$finalfilter = '';
		$coll_filter = '';
		$filter = '';
			
		//construct  $datefilter from $dmin $dmax
		$datefilter = ((($this->dmin) && ($this->dmax)) ? "date > '".$conn->escape_string($this->dmin)."' AND date < '".$conn->escape_string($this->dmax)."' ": '');
					
		//construct filter on available collections that the appbox's user can report		
		if(($this->user_id != '') &&  ($this->list_coll_id != ''))
		{
			$tab = explode(",", $this->list_coll_id);
			if(is_array($tab))
			{
				foreach($tab as $val)
					$coll_filter .= (($coll_filter) ? ' OR ' : '' ) . " position(',".$conn->escape_string($val).",' in concat(',' ,coll_list, ',')) > 0 ";
			}
			else
				$coll_filter = (($coll_filter) ? ' OR ' : '' ) . " position(',".$conn->escape_string($this->list_coll_id).",' in concat(',' ,coll_list, ',')) > 0 ";
		}
		//construct the final filter from the other ones
		if($datefilter)
			$finalfilter .= $datefilter.' AND ';
		if($coll_filter)
			$finalfilter .= '('.$coll_filter.')  AND ';
		$finalfilter .= 'log.site="'.$conn->escape_string(GV_sit).'"';

		return($finalfilter);
	}
	
	
	/**
	 * @desc report the browser used by users
	 * @param array $tab config  for the html table
	 * @return tab
	 */
	public function buildTabNav($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$i = 0;
		$filter = $this->getFilter($conn);
		$this->title = _('report:: navigateur');
		if(is_null($this->total_pourcent))
		{
			return $this->report;
		}
		
		$sql = "SELECT nav, COUNT(nav) AS nb,ROUND((COUNT(nav)/$this->total_pourcent*100), 1) AS pourcent
				FROM log
				WHERE $filter AND nav != TRIM('')
				GROUP BY nav
				ORDER BY pourcent DESC";
		
		$this->initialize();

		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
		
			while($row = $conn->fetch_assoc($rs))
			{
				if(floatval($row['pourcent']) >= 1)
				{
					foreach($this->champ as $key => $value)
						($value == 'pourcent') ? $this->result[$i][$value] = $row[$value].'%' : $this->result[$i][$value] = $row[$value];
					$this->report['value'][] = $row['nb'];
					$this->report['legend'][]	= $row['nav'];
					$i++;
				}
			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		
		return $this->report;
		
	}
	
	
	/**
	 * @desc report the OS from user
	 * @param array $tab config for the html table
	 * @return array
	 */ 
	public function buildTabOs($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$filter = $this->getFilter($conn);
		$i = 0;
		$this->title = _('report:: Plateforme');
		
		if(is_null($this->total_pourcent))
		{
			return $this->report;
		}
		
		$sql = 
			"SELECT os, COUNT(os) AS nb, ROUND((COUNT(os)/$this->total_pourcent*100),1) AS pourcent
			FROM log
			WHERE $filter AND os != TRIM('')
			GROUP BY os
			ORDER BY pourcent DESC";
		
		$this->initialize();

		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
			
			while($row = $conn->fetch_assoc($rs))
			{
				if(floatval($row['pourcent']) >= 1)
				{
					foreach($this->champ as $key => $value)
						($value == 'pourcent') ? $this->result[$i][$value] = $row[$value].'%' : $this->result[$i][$value] = $row[$value];
					$i++;
					$this->report['value'][] = $row['nb'];
					$this->report['legend'][] = $row['os'];
				}
			} 
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		
		return $this->report;
	}

	
	/**
	 * @desc report the resolution that are using the users
	 * @param array $tab config for the html table
	 * @return array
	 */
	public function buildTabRes($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$filter = $this->getFilter($conn);
		$this->title = _('report:: resolution');
		$i = 0;
		if(is_null($this->total_pourcent))
		{
			return($this->report);
		}
		
		$sql = "SELECT res, COUNT(res) AS nb, ROUND((COUNT(res)/$this->total_pourcent*100),1) AS pourcent
				FROM log
				WHERE $filter AND res != TRIM('')
				GROUP BY res
				ORDER BY pourcent DESC
				LIMIT 0, 10";
		
		$this->initialize();

		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
		
			while($row = $conn->fetch_assoc($rs))
			{
				if(floatval($row['pourcent']) >= 1)
				{
					foreach($this->champ as $key => $value)
						($value == 'pourcent') ? $this->result[$i][$value] = $row[$value].'%' : $this->result[$i][$value] = $row[$value];
					$i++;
					$this->report['value'][] = $row['nb'];
					$this->report['legend'][]	= $row['res'];
				}
			}
	
//			$x = array_sum($this->report['value']);
//			if( $x < 100)
//			{
//				array_push($this->report['value'], (100 - $x));
//				array_push($this->report['legend'], 'autres');
//				$nb = (intval($this->total_pourcent) * ((100 - $x) / 100));
//				$res = array('res' => 'autres', 'nb' => round($nb), 'pourcent' => round((100 - $x)));
//				$this->result[$i++] = $res;
//			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
	
	
	/**
	 * @desc report the combination (OS - Navigateur) that are using the users
	 * @param array $tab config for the html table
	 */
	public function buildTabCombo($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$filter = $this->getFilter($conn);
		$this->title = _('report:: navigateurs et plateforme');
		$i = 0;
		if(is_null($this->total_pourcent))
		{
			return($this->report);
		}
		
		$sql = "
				SELECT CONCAT( nav, '-', os ) AS combo, COUNT( CONCAT( nav, '-', os ) ) AS nb, ROUND((COUNT( CONCAT( nav ,'-', os ))/$this->total_pourcent*100), 1) AS pourcent
				FROM log
				WHERE $filter AND nav != TRIM( '' )
				AND os != TRIM( '' )
				GROUP BY combo
				ORDER BY nb DESC
				LIMIT 0 , 10";
				
		$this->initialize();

		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
			
			while($row = $conn->fetch_assoc($rs))
			{
				if(floatval($row['pourcent']) >= 1)
				{
					foreach($this->champ as $key => $value)
						($value == 'pourcent') ? $this->result[$i][$value] = $row[$value].'%' : $this->result[$i][$value] = $row[$value];
					$i++;
					$this->report['value'][] = $row['nb'];
					$this->report['legend'][]	= $row['combo'];
				}
			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
	
	/**
	 * @desc report the most consulted module by the users
	 * @param array $tab
	 * @return array
	 */
	public function buildTabModule($tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$filter = $this->getFilter($conn);
		$this->title = _('report:: modules');
		$i = 0;
		if(is_null($this->total_pourcent))
		{
			return($this->report);
		}
		
		$sql = "SELECT appli
				FROM log
				WHERE $filter AND appli != 'a:0:{}'
				GROUP BY appli
				ORDER BY appli DESC
				";
		
		$this->initialize();
		
		$x = array();
		$tab_appli = array();
	
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$applis = false;
				if(($applis = @unserialize($row['appli'])) !== false)
					array_push($x, phrasea::modulesName($applis));					
				else
					array_push($x, 'NULL');
			}
			foreach($x as $key => $tab_value)
			{
				if(is_array($tab_value)){
					foreach($tab_value as $key2 => $value)
					{
						if(!isset($tab_appli[$value]))
							$tab_appli[$value] = 0;
						$tab_appli[$value]++;
					}
				}
			}
			$total = array_sum($tab_appli);
		
			$this->setChamp($rs);
			$this->setDisplay($tab);
			
			foreach($tab_appli as $appli => $nb)
			{
				$pourcent = round(($nb/$total)*100,1);
				if(floatval($pourcent >= 1))
				{
					foreach($this->champ as $key => $value)
					{
						$this->result[$i]['appli'] = $appli;
						$this->result[$i]['nb'] = $nb;
						$this->result[$i]['pourcent'] = $pourcent.'%';
					}
					$i++;
					$this->report['value'][] = $nb;
					$this->report['legend'][] = $appli;
				}
			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
	
	/**
	 * @desc report basic user informations
	 * @param int $val user id
	 * @param array $tab config for the html table
	 * @param string $on the field
	 * @return array 
	 */
	
	
	public function buildTabGrpInfo($req, $val, $tab = false, $on = false)
	{
		empty($on) ? $on = false : "";
		$id_user = array();
		$filter_id_apbox = "";
		$filter_id_datbox = "";
		$conn = connection::getInstance();
		$conn2 = connection::getInstance($this->sbas_id);
		$datefilter = ((($this->dmin) && ($this->dmax)) ? "date > '".$conn->escape_string($this->dmin)."' AND date < '".$conn->escape_string($this->dmax)."' ": '');
		$this->title = sprintf(_('report:: Information sur les utilisateurs correspondant a %s'),$val);
		
		if($on)
		{
			if($rsu = $conn2->query($req))
			{
				while($row_user = $conn2->fetch_assoc($rsu))
				{
					$id_user[$row_user['usrid']] = 0;
				}
				foreach($id_user as $key => $id)
				{
					$filter_id_apbox .= (empty($filter_id_apbox) ? " " : " OR ") . "usr_id = '".$key."'";
					$filter_id_datbox .= (empty($filter_id_datbox) ? " " : " OR ") . "log.usrid = '".$key."'";
				}
			}

			$sql = "SELECT usr_login as identifiant, usr_nom as nom, usr_mail as mail, adresse, tel
					FROM usr
					WHERE $on = '".$val."' AND ($filter_id_apbox)";
			
			
			$rs = $conn->query($sql);
		}
		else
		{
			$sql = 'SELECT usr_login as identifiant, usr_nom as nom, usr_mail as mail, adresse, tel
					 FROM usr WHERE usr_id="'.$conn->escape_string($val).'"';
			
			$rs = $conn->query($sql);
		}
		
		if($rs)
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
			$j = 0;
		
			while($row = $conn->fetch_assoc($rs))
			{
				$i = 0;
				while($i < $conn->num_fields($rs))
				{
					if($row[$conn->field_name($rs, $i)])
						$this->result[$j][$conn->field_name($rs, $i)] = $row[$conn->field_name($rs, $i)];
					else
						$this->result[$j][$conn->field_name($rs, $i)] = _('report:: non-renseigne');
					$i++;
				}
				$j++;
			}
			if($on == false)
			{
				empty($this->result[0]['identifiant']) ? $login = _('phraseanet::utilisateur inconnu') : $login = $this->result[0]['identifiant'];
				$this->title = sprintf(_('report:: Information sur l\'utilisateur %s'), $login);
			}
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}

	/**
	 * @desc return basic information about a record
	 * @param $ses session id
	 * @param $bid base id
	 * @param $rid record id
	 * @param $tab config for the html table
	 * @return array
	 */
	public function buildTabUserWhat($ses, $bid, $rid, $tab = false)
	{
		$conn = connection::getInstance($this->sbas_id);
		$this->title = sprintf(_('report:: Information sur l\'enregistrement numero %d'),(int)$rid);
		$sql = " 
		SELECT subdef.mime AS type, record.xml AS xml, record.record_id AS record_id, record.credate AS crea 
		FROM (subdef INNER JOIN record ON subdef.record_id = record.record_id) 
		WHERE record.record_id = '".$rid."' LIMIT 0,1";
		
		
		if($rs = $conn->query($sql))
		{
			$this->champ = array('photo', 'record_id', 'date', 'type', 'titre', 'taille');
			$this->setDisplay($tab);
				
			$i = 0;	
			if($row = $conn->fetch_assoc($rs))
			{
				$x = answer::getThumbnail($ses, $bid, $rid);
				$this->result[$i]['photo'] = "<img style='width:".$x['w']."px;height:".$x['h']."px;' src='".GV_ServerName."/".$x['thumbnail']."'>";
				$this->result[$i]['record_id'] = $row['record_id'];
				$this->result[$i]['date'] = phraseadate::getPrettyString(new DateTime($row['crea']));//substr($date[2],0, 2).'-'.$date[1].'-'.$date[0];
				$this->result[$i]['type'] = $row['type'];
				$this->result[$i]['titre'] = answer::format_title(phrasea::sbasFromBas($bid), $rid, $row['xml']);//parent::getChamp($row['xml'], 'Titre');
				$this->result[$i]['taille'] = report_activity::unite(parent::getChamp($row['xml'],'doc', 'size'));
				$i++;
			}
		}
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
			
		return $this->report;
	}
	
	public function buildTabInfoNav($tab = false, $navigator)
	{
		$conn = connection::getInstance($this->sbas_id);
		$this->title =  sprintf(_('report:: Information sur le navigateur %s'),$navigator);
		
		$sql = " 
		SELECT DISTINCT(version) as version, COUNT(version) as nb
		FROM log 
		WHERE nav = '".$navigator."' 
		GROUP BY version
		ORDER BY nb DESC";
		
		
		if($rs = $conn->query($sql))
		{
			$this->setChamp($rs);
			$this->setDisplay($tab);
				
			$i = 0;
			while($row = $conn->fetch_assoc($rs))
			{
				$this->result[$i]['version'] =$row['version'];
				$this->result[$i]['nb'] = $row['nb'];
				$i++;
			}
			$this->total = sizeof($this->result);
			$this->calculatePages($rs);
			$this->setDisplayNav();
			$this->setReport();
			$conn->free_result($rs);
		}
		return $this->report;
	}
}


?>