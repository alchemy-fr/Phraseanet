<?php
/**
 * short description
 *  
 */
class report 
{
	
	/*~*~*~*~*~*~*~*~*~*~*/
    /* PROPERTIES PUBLIC */
    /*~*~*~*~*~*~*~*~*~*~*/
	
	
   /**
	* Start date of the report
	* @var string - timestamp
	*/
	protected $dmin;

   /**
	* End date of the report
	* @var string - timestamp
	*/
	protected $dmax;

   /**
	* Id of the base we want to connect
	* @var int
	*/
	protected $sbas_id;

   /**
	* Id of the current app's box user
	* @var int
	*/
	protected $user_id; 

   /**
	* The result of the report
	* @var array
	*/
	public $report = array();
	
	/**
	 * The title of the report
	 * @var string
	 */
	protected $title = '';
	
	/**
	 * default displayed value in the formated tab
	 * @var array
	 */
	protected $display = array();
	
	protected $default_display = array();
	/**
	 * Contain all the field from the sql request
	 * @var array
	 */
	protected $champ = array();
	
	/* result of the rerport */
	protected $result = array();
	/**
	 * The id of all collections from a databox
	 * @var string
	 */
	protected $list_coll_id = '';
	
	/**
	* The number of record displayed by page
	* @var int
	*/	
	protected $nb_record = 30;
	
	   /**
	* The current number of the page where are displaying the results
	* @var int
	*/	
	protected $nb_page = 1;
	
	protected $previous_page = false;
	
	protected $next_page = false;
	
	/**
	 * 
	 * @var int total of result
	 */
	protected $total = 0;
	
	/**
	 * the request executed
	 */
	protected $req = '';
	
	/**
	 * True to display a form for next and previous page
	 * @var bool
	 */
	protected $display_nav = false;
	
	/**
	 * 
	 * @var display config button ?
	 */
	protected $config = true;
	
	/**
	 * 
	 * @var translate format request
	 */
	protected $cor_filter = array(
		'date' 		=> "DATE_FORMAT(log.date,'%Y-%m-%d')",
		'ddate'		=> "DATE_FORMAT(log.date,'%Y-%m-%d')",
		'record_id'	=> "log_docs.record_id"
	);
	
	protected $cor = array();
	
	
	protected $jour = array();
	
	protected $month = array();
   /**
	* The name of the database
	* @var string
	*/	
	protected $dbname;
	
   /**
	* The periode displayed in a string of the report
	* @var string
	*/
	protected $periode;
	
	/**
	 * 
	 * @var filter executed on report
	 */
	protected $tab_filter = array();
	
	protected $active_column = array();
	
	protected $posting_filter = array();
	
	/**
	* The ORDER BY filters of the query
	* by default is empty
	* @var array
	*/	
	protected $tab_order = array();
	
	protected $bound = array();

	protected $print = true;
	
	protected $csv = true;
	
	protected $enable_limit = true;
	/*~*~*~*~*~*~*~*~*~*~*~*~*/
    /* METHODS, VARIABLES    */
    /*~*~*~*~*~*~*~*~*~*~*~*~*/
	
	
	/**
    * Constructor
    *
    * 
    * @name report::__construct()
    * @param $arg1 the minimal date of the report
    * @param $arg2 the maximal date of the report
    * @param $sbas_id the id of the base where we want to connect
    */ 
	public function __construct($d1, $d2, $sbas_id, $collist)
	{
		$session = session::getInstance();
		$this->dmin = $d1;		
		$this->dmax = $d2; 		
		$this->sbas_id = $sbas_id;
		$this->list_coll_id = $collist;
		$this->user_id = $session->usr_id;
		$this->periode = phraseadate::getPrettyString(new DateTime($d1)).' - '.phraseadate::getPrettyString(new DateTime($d2));
		$this->dbname = phrasea::sbas_names($sbas_id);
		$this->cor = $this->setCor();
		$this->jour = Array(	
				1	=> _('phraseanet::jours:: lundi'),
				2	=> _('phraseanet::jours:: mardi'), 
				3	=> _('phraseanet::jours:: mercredi'), 
				4	=> _('phraseanet::jours:: jeudi'), 
				5	=> _('phraseanet::jours:: vendredi'), 
				6	=> _('phraseanet::jours:: samedi'), 
				7	=> _('phraseanet::jours:: dimanche'));
		$this->month = array(
		 	_('janvier'),
			_('fevrier'),
		 	_('mars'),
		 	_('avril'),
		 	_('mai'),
		 	_('juin'),
		 	_('juillet'),
		 	_('aout'),
		 	_('septembre'),
		 	_('octobre'),
		 	_('novembre'),
		 	_('decembre')
		);
	}
	
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	public function setCsv($bool)
	{
		$this->csv = $bool;
	}
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getDisplay()
	{
		return $this->display;
	}
	public function getTransQueryString()
	{
		return $this->cor_query;
	}
	
	public function getCor()
	{
		return $this->cor;
	}
	
	public function getEnableLimit()
	{
		return $this->enable_limit;
	}
	
	public function setFilter($filter)
	{
		if(is_array($filter))
		{
			$this->tab_filter = $filter;
		}
	}
	
	public function setActiveColumn($active_column)
	{
		if(is_array($active_column))
		{
			$this->active_column = $active_column; 
		}
	}
	
	public function setConfig($bool)
	{
		$this->config = $bool;
	}
	
	public function setPrint($bool)
	{
		$this->print = $bool;
	}
	
	public function setHasLimit($bool)
	{
		$this->enable_limit = $bool;
	}
	
	public function setBound($column, $bool)
	{
		if($bool)
		{
			$this->bound[$column] = 1;
		}
		else
		{
			$this->bound[$column] = 0;
		}
	}
	
	/**
   * @desc Add an ORDER BY filter to $tab_order
   * @param string the field where the ORDER BY is carried out
   * @return void
   */
	
	public function setOrder($champ, $order)
	{
		$this->tab_order['champ'] = $champ;
		$this->tab_order['order'] = $order;
	}
	
	
	/**
   * @desc set $nb_page, $nb_record in order of construct the query LIMIT
   * @param int $arg1 the number of current page
   * @param int $arg2 the number of record displayed per pages
   * @return void
   */
	public function setLimit($page, $limit)
	{
		$this->nb_page = $page;
		$this->nb_record = $limit;	
	}
	
	public function setPeriode($periode)
	{
		$this->periode = $periode;
	}
	
	public function getReq()
	{
		return $this->req;
	}
	
	public function setpostingFilter($filter)
	{
		$this->posting_filter = $filter;	
	}
	
	   
	protected function setChamp($result_set)
	{
		$this->champ = array();
		$this->default_display = array();
		$conn = connection::getInstance($this->sbas_id);
		$num_field = $conn->num_fields($result_set);
		$j = 0;
		while ($j < $num_field)
		{
			array_push($this->champ, $conn->field_name($result_set, $j));
			array_push($this->default_display, $conn->field_name($result_set, $j));
			$j++;
		}
	}
	
	protected function setReport()
	{
		$this->report['dbid'] = $this->sbas_id;
		$this->report['periode'] = $this->periode;
		$this->report['dbname'] = $this->dbname;
		$this->report['dmin'] = $this->dmin;
		$this->report['dmax'] = $this->dmax;
		$this->report['server'] = GV_ServerName;
		$this->report['filter'] = $this->tab_filter;
		$this->report['posting_filter'] = $this->posting_filter;
		$this->report['active_column'] = $this->active_column;
		$this->report['default_display'] = $this->default_display;
		$this->report['config'] = $this->config;
		$this->report['total'] = $this->total;
		$this->report['display_nav'] = $this->display_nav;
		$this->report['nb_record'] = $this->nb_record;
		$this->report['page'] = $this->nb_page;
		$this->report['previous_page'] = $this->previous_page;
		$this->report['next_page'] = $this->next_page;
		$this->report['title'] = $this->title;
		$this->report['allChamps'] = $this->champ;
		$this->report['display'] = $this->display;
		$this->report['result'] = $this->result;
		$this->report['print'] = $this->print;
		$this->report['csv'] = $this->csv;
	}
	
	private function setCor()
	{
		return array(
				'user' 			=> _('report:: utilisateur'),
				'coll_id' 		=> _('report:: collections'),
				'connexion'	=> _('report:: Connexion'),
				'comment' 	=> _('report:: commentaire'),
				'search'			=> _('report:: question'), 
				'date' 			=> _('report:: date'),
				'ddate' 			=> _('report:: date'),
				'fonction' 		=> _('report:: fonction'), 
				'activite' 		=> _('report:: activite'),
				'pays' 			=> _('report:: pays'), 
				'societe' 		=> _('report:: societe'),
				'nb' 				=> _('report:: nombre'),
				'pourcent'		=> _('report:: pourcentage'),
				'telechargement' => _('report:: telechargement'),
				'record_id' 	=> _('report:: record id'), 
				'final' 			=> _('report:: type d\'action'),
				'xml' 				=> _('report:: sujet'), 
				'file' 				=> _('report:: fichier'), 
				'mime' 			=> _('report:: type'), 
				'size' 				=> _('report:: taille'),
				'copyright' 	=> _('report:: copyright'),
				'final' 			=> _('phraseanet:: sous definition')
			);
	}
	
	protected function setDisplay($tab, $groupby = false)
	{
		if($tab == false && $groupby == false)
			$this->initDefaultConfigColumn($this->default_display);
		elseif($groupby != false && $tab == false)
			$this->initDefaultConfigColumn($this->champ);
		elseif($tab != false)
			$this->setConfigColumn($tab);
	}
	
	protected function calculatePages($rs)
	{
		if($this->nb_record && $this->total > $this->nb_record)
		{
			$this->previous_page = $this->nb_page - 1;
			if($this->previous_page == 0)
				$this->previous_page = false;
			
			$test = ($this->total / $this->nb_record); 
			if($this->nb_page == intval(ceil($test)))
			{
				$this->next_page = false;
			}
			else
			{
				$this->next_page = $this->nb_page + 1;	
			}
		}
	}
	
	protected function setDisplayNav()
	{
		if($this->total > $this->nb_record)
			$this->display_nav = true;
	}
	

	/**
   * @desc Initialize the configuration foreach column displayed in the report
   * @param array $display  contain the conf's variables
   * @return void
   */
	protected function initDefaultConfigColumn($display)
	{
		$array = array();
		foreach($display as $key => $value)
		{
			$array[$value] = array("", 0, 0, 0, 0);
		}
		$this->setConfigColumn($array);
	}
	
	
	
	protected function constructDateFilter()
	{
		$conn = connection::getInstance($this->sbas_id);
		return((($this->dmin) && ($this->dmax)) ? "(log.date > '".$conn->escape_string($this->dmin)."' AND log.date < '".$conn->escape_string($this->dmax)."')  ": '');
	}
	
	protected function constructUserFilter()
	{	
		if(sizeof($this->tab_filter) > 0)
		{
			$conn = connection::getInstance($this->sbas_id);
			$filter = "";
			
			foreach ($this->tab_filter as $field => $value)
			{
				if(array_key_exists($value['f'], $this->cor_filter))
					$value['f'] = $this->cor_query[$value['f']];
				
				if($value['o'] == 'LIKE')
					$str = $value['f'].' '.$value['o'].' \'%'.$conn->escape_string($value['v']).'%\'';
				elseif($value['o'] == 'OR')
					$str = $value['f'].' '.$value['o'].' '.$value['v'];
				else
					$str = $value['f'].' '.$value['o'].' \''.$conn->escape_string($value['v']).'\'';
					
				$filter .= (($filter) ? ' AND ': '') . $str;
			}
			return $filter;
		}
		else
		{
			return false;
		}
	}
	
	protected function constructCollUserFilter()
	{
		$conn = connection::getInstance($this->sbas_id);
		$coll_filter = '';
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
		return '('.$coll_filter.')';
	}
	
	protected function constructDlUserFilter()
	{
		$conn = connection::getInstance($this->sbas_id);
		$dl_coll_filter ='';
		if(($this->user_id != '') &&  ($this->list_coll_id != ''))
		{
			$tab = explode(",", $this->list_coll_id);
			if(is_array($tab))
			{
				foreach($tab as $val)
					$dl_coll_filter .= (($dl_coll_filter) ? ' OR ' : '') . "record.coll_id = '".$conn->escape_string($val)."'" ;
			}
			else
				$dl_coll_filter .= (($dl_coll_filter) ? ' OR ' : '') . "record.coll_id = '".$conn->escape_string($this->list_coll_id)."'" ;
		}
		return $dl_coll_filter;
	}
	

	
	protected function constructOrderFilter()
	{
		return((sizeof($this->tab_order) > 0) ? $this->cor_query[$this->tab_order['champ']].' '.$this->tab_order['order'] : false);
	}
	
	protected function constructLimitFilter()
	{
		if(!$this->nb_page || !$this->nb_record)
		{
			return false;
		}
		$conn = connection::getInstance($this->sbas_id);
		$limit_inf = ($conn->escape_string($this->nb_page) - 1) * $conn->escape_string($this->nb_record);
		$limit_sup = $conn->escape_string($this->nb_record);
		return $limit_inf.', '.$limit_sup;
	}
  /**
   * @desc Build the entire query filter from $tab_filter, $tab_order, $limit
   * @return array $tab_filter which contain the final WHERE, ORDER and LIMIT filter
   */
	protected function buildFilter()
	{
		$finalfilter = "";
		$conn = connection::getInstance($this->sbas_id);
		
		$datefilter = $this->constructDateFilter();
		$filter = $this->constructUserFilter();
		$coll_filter = $this->constructCollUserFilter();
		$order = $this->constructOrderFilter();
		$limit = $this->constructLimitFilter();
		$dl = $this->constructDluserFilter();
		
		if($datefilter)
			$finalfilter .= $datefilter.' AND ';
			
		if($filter)
			$finalfilter .= $filter.' AND ';
			
		if($coll_filter)
			$finalfilter .= '('.$coll_filter.') AND ';
			
		$finalfilter .= 'log.site="'.$conn->escape_string(GV_sit).'"';
		
		$tab_filter = array(	
			'finalfilter' 		=> $finalfilter
			,'datefilter' 		=> $datefilter
			,'filter' 			=> $filter
			,'collfilter' 		=> $coll_filter
			,'order' 			=> $order
			,'limit' 			=> $limit
			,'dl_coll_filter' 	=> $dl
		);
		return $tab_filter;
	}
	

  /**
   * @desc Set your own configuration for each column displayed in the html table
   * @param array $tab contain your conf's variables array( 'field' => array('title of the colum', '1 = order ON / 0 = order OFF', '1 = bound ON / 0 = bound OFF')
   * @example $tab = array('user' => array('user list', 1, 0),
   * 					   'id'   => array(user id, 0, 0)); etc ..
   * @return void
   */
	protected function setConfigColumn($tab)
	{
		
		foreach($tab as $column => $row)
		{
			foreach($row as $ind => $value)
			{
				$def = false;
				if(array_key_exists($column, $this->cor))
				{
					$title_text = $this->cor[$column];
					$def = true;	
				}
				empty($row[0]) ? $title = $column : $title = $row[0];
				
				$sort = $row[1];
				array_key_exists($column, $this->bound) ? $bound = $this->bound[$column] : $bound = $row[2];
				$filter = (isset($row[3]) ? $row[3] : 0);
				$groupby = $row[4];
				$config = array('title' => $title, 'sort' => $sort, 'bound' => $bound, 'filter' => $filter, 'groupby' => $groupby);
				$def ? $config['title'] = $title_text : "";

				$this->display[$column] = $config;
			}
		}		
	}

	
  /**
   * @desc Get the collection name by id .
   * @return array( id_coll => name_coll ) etc..
   */
	protected function returnCollName()
	{
		$sql = "
			SELECT coll_id as id, htmlname as name
			FROM coll
			ORDER BY htmlname";
		
		$id_coll_to_coll_name = array();
		
		$conn = connection::getInstance($this->sbas_id);
		
		if(!$conn || !$conn->isok())
			throw new Exception('<br />La base '.phrasea::sbas_names($this->sbas_id).' n\'existe pas');
		else
		{
			$rs = $conn->query($sql);
			while($row = $conn->fetch_assoc($rs))
				$id_coll_to_coll_name[$row['id']] = $row['name'];
		}
		return $id_coll_to_coll_name;
	}

	
   /**
	* @desc build the final formated array which contains all the result, we construct the html code from this array 
	* @param array $tab pass the configcolumn parameter to this tab
	* @return the formated array
	*/
	public function buildReport($tab = false, $groupby = false, $on = false)
	{
		//get connection
		$conn = connection::getInstance($this->sbas_id);
		//contruct request
		$this->buildReq($groupby, $on);
		//get result set
		try{
			if($rs = $conn->query($this->req))
			{
				//set request field
				$this->setChamp($rs);
				//set display
				$this->setDisplay($tab, $groupby);
				//construct results
				$this->buildResult($rs);
				//calculate prev and next page
				$this->calculatePages($rs);
				//do we display navigator ?
				$this->setDisplayNav();
				//assign all variables
				$this->setReport();
				//
				$conn->free_result($rs);
				
				return $this->report;
			}
			else
			{
				throw new Exception();
			}
		}
		catch (Exception $e)
		{
			echo  $e->getMessage()."\n";
		}
	}
	
	
	
		
	/**
	 * @desc return the text between the node we are looking for
	 * @param string $unXml the XML string
	 * @param string $champ the node
	 * @return string
	 */

	public static function getChamp($unXml,$champ, $attribut = false)
	{		
		$ret = "";
		if($sxe = simplexml_load_string($unXml))
		{
			if($attribut)
			{
				foreach($sxe->$champ->attributes() as $a => $b)
				{
					if($a == $attribut)
					{
						$ret.= $b;
					}
				}
			}
			else
			{
			$z = $sxe->xpath('/description/'.$champ);
			
				if(!$z)
					$z = $sxe->xpath('/record/description/'.$champ);
					
				if($z && is_array($z))
				{
					$ret .= $z[0];
				}
			}
		}
		$ret = trim($ret);
		if($ret==""||$ret==null)
			$ret="<i>"._('report:: non-renseigne')."</i>";
		return $ret;
	}
	
	/**
	 *@desc get filds to display from structure xml 
	 *@param int $sbasid id of current sbas
	 */
	
	public function getResult()
	{
		return $this->result;
	}
	public static function getPreff($sbasid)
	{
		$conn2 = connection::getInstance($sbasid);
		$tab["struct"]="";
		$tab['champs'] = array();
		$sql2 = "SELECT value AS struct FROM pref WHERE prop='structure' LIMIT 1";
		if($rs2 = $conn2->query($sql2))
		{
			if(($row2 = $conn2->fetch_assoc($rs2)) )
			{
				$tab["struct"] = $row2["struct"];
			}
			$conn2->free_result($rs2);
		}   
		                      
		if($sxe = simplexml_load_string($tab["struct"]))
		{
			$z = $sxe->xpath('/record/description');                               
			if($z && is_array($z))
			{                                       
				foreach($z[0] as $ki => $vi)
				{                                                       
					foreach( $vi->attributes() as $a => $b ) 
					{
						if($a=="report" && $b==1)               
							$tab['champs'][] = $ki;
					}       
				}
			}                              
		}
		return $tab['champs'];
	}
	
	public static function getDateFilter($dmin, $dmax)
	{
		return ((($dmin) && ($dmax)) ? "log.date > '$dmin' AND log.date < '$dmax' ": "");
	}
	
	public static function getCollectionFilter($list_coll_id)
	{
		$coll_filter = "";
		if(!empty($list_coll_id))
		{
			$tab = explode(",", $list_coll_id);
			if(is_array($tab))
			{
				foreach($tab as $val)
					$coll_filter .= (($coll_filter) ? ' OR ' : '' ) . " position(',$val,' in concat(',' ,coll_list, ',')) > 0 ";
			}
			else
				$coll_filter = (($coll_filter) ? ' OR ' : '' ) . " position(',$list_coll_id,' in concat(',' ,coll_list, ',')) > 0 ";
		}
		return ('('.$coll_filter.')');
	}
	
	public static function unite($valeur)
	{
	    if( $valeur >= pow(1024, 3) )
	    {
	        $valeur = round( $valeur / pow(1024, 3), 2);
	        return ($valeur . ' go');
	    }
	    elseif( $valeur >=  pow(1024, 2) )
	    {
	        $valeur = round( $valeur / pow(1024, 2), 2);
	        return ($valeur . ' mo');
	    }
	    else
	    {
	        $valeur = round( $valeur / 1024, 2);
	        return ($valeur . ' ko');
	    }
	}
	
	public static function getHost($url) 
	{ 
	   	$parse_url = parse_url(trim($url)); 
	   	return trim($parse_url['host'] ? $parse_url['host'] : array_shift(explode('/', $parse_url['path'], 2))); 
	}
	
	public static function getUsrLogin($id_usr)
	{
		$conn = connection::getInstance();
				
		$sql = '
				SELECT usr_nom, usr_prenom, usr_mail, usr_login 
				FROM usr 
				WHERE usr_id="'.$conn->escape_string($id_usr).'"';
				
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$login = $row['usr_login'];
		}
		return (isset($login) ? $login : _('phraseanet:: inconnu'));
	}	
}


?>