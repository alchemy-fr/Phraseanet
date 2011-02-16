<?php


class dashboard
{
	public $all_sbas_desc;
	public $dmax;
	public $dmin;
	public $dminv;
	public $dmaxv;
	public $dmax_req;
	public $dmin_req;
	public $liste_base;
	public $dashboard;
	public $nb_days;
	public $legendDay;
	public $legendHour;
	public $unorganised_dahsboard;
	public $base;
	public $total = array();	
	
	public function __construct($usr_id)
	{
		$datetime_req = new Datetime();
		$this->nb_days = 0;
		$this->dmax_req = phraseadate::format_mysql($datetime_req);
		$this->dmax = $datetime_req->format('d-m-Y');
		$this->dmaxv = phraseadate::getPrettyString(new Datetime($this->dmax));
		$datetime_req->modify('-1 month'); // Periode des requetes sur le dashboard
		$this->dmin_req = phraseadate::format_mysql($datetime_req);
		$this->dmin = $datetime_req->format('d-m-Y');
		$this->dminv = phraseadate::getPrettyString(new Datetime($this->dmin));
		$this->liste_base = ' - ';
		$this->base = array();
		$this->legendDay = $this->getPlotDayLegend($this->dmin);
		$this->legendHour = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);
		$this->all_sbas_desc = array();
		$this->getAllSbas($usr_id);
		$this->getDataFromAllSbas();
		$this->initDashboard();
		$this->fillDataInDahsboard();
		$this->sortDashboard();
		$this->formatDashboard();
	}	
	
	/**
	 * @desc return the legend for the activity by day
	 * @param $dmin minimum date of the current report
	 * @return array
	 */
	private function getPlotDayLegend($dmin)
	{
		$date = new Datetime();
		$dmax = $date->format('d-m-Y');
		$array = array();
		$array[] = phraseadate::getPrettyString($date);
		
		while ($dmin != $dmax) 
		{
			$date->modify('-1 day');
			$dmax = $date->format('d-m-Y');
			$array[] = phraseadate::getPrettyString($date);
			$this->nb_days++;
		}
		$array = array_reverse($array);
		return($array);
	}
	
	/**
	 * @desc return allsbas where the current user can report
	 * @param $usr_id the id of the user
	 * @return void
	 */
	private function getAllSbas($usr_id)
	{
		$lb = phrasea::bases();
		$user = user::getInstance($usr_id);
		$all_coll = array() ;
		foreach($lb['bases'] as $sbas)
		{
			if(connection::getInstance($sbas['sbas_id']))
			{
				foreach($sbas['collections'] as $coll)
				{
					if(!isset($user->_rights_bas[$coll['base_id']]) || !$user->_rights_bas[$coll['base_id']]['canreport'])
						continue;
					if(!isset($all_coll[$sbas['sbas_id']]))
					{
						$all_coll[$sbas['sbas_id']] = array();
					}
					$all_coll[$sbas['sbas_id']]['sbas_collections'][] = array(
						'base_id' => $coll['base_id'],
						'sbas_id' => $sbas['sbas_id'],
						'coll_id'=>$coll['coll_id'],
						'name'=>phrasea::bas_names($coll['base_id'])
					);
				}
			}
		}
		foreach($all_coll as $sbas => $info)
		{
			$liste ="";
			$this->liste_base .= phrasea::sbas_names($sbas)." - ";
			$this->all_sbas_desc[$sbas]['name'] =  phrasea::sbas_names($sbas);
			foreach($info['sbas_collections'] as $key => $value)
			{
				$liste .= (empty($liste) ? '': ',') . $value['coll_id'];
			}
			$this->all_sbas_desc[$sbas]['server_coll_list'] = $liste;
			$this->base[] = array('base_id' => $sbas, 'coll' => $liste);
		}
	}
	
	/**
	 * @desc recover all data 
	 * @return void
	 */
	private function getDataFromAllSbas()
	{
		foreach($this->all_sbas_desc as $sbas_id => $array)
		{
			$this->all_sbas_desc[$sbas_id]['nb_dl'] = report_download::getNbDl($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['nb_conn'] = report_connexion::getNbConn($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			if(GV_anonymousReport == false)
				$this->all_sbas_desc[$sbas_id]['top_ten_user'] = report_activity::topTenUser($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['activity'] = report_activity::activity($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['activity_day'] = report_activity::activityDay($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"], $this->nb_days);
			$this->all_sbas_desc[$sbas_id]['top_dl'] = report_download::getTopDl($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			if(GV_anonymousReport == false)
				$this->all_sbas_desc[$sbas_id]['ask'] = report_activity::activityQuestion($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['top_ten_site']= report_activity::activiteTopTenSiteView($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['top_ten_question']= report_activity::activiteTopQuestion($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['activity_added'] = report_activity::activiteAddedDocument($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
			$this->all_sbas_desc[$sbas_id]['activity_edited'] = report_activity::activiteEditedDocument($this->dmin_req, $this->dmax_req, $sbas_id, $array["server_coll_list"]);
		}
	}
	
	/**
	 * @desc init the dashboard
	 * @return void
	 */
	private function initDashboard()
	{
		$this->unorganised_dashboard = array();
		$this->unorganised_dashboard['top_ten_user']['nbdoc'] = array();
		$this->unorganised_dashboard['top_ten_user']['nbprev'] = array();
		$this->unorganised_dashboard['top_ten_user']['poiddoc'] = array();
		$this->unorganised_dashboard['top_ten_user']['poidprev'] = array();
		$this->unorganised_dashboard['top_dl']['document'] = array();
		$this->unorganised_dashboard['top_dl']['preview'] = array();
		$this->unorganised_dashboard['ask'] = array();
		$this->unorganised_dashboard['top_ten_site'] = array();
		$this->unorganised_dashboard['top_ten_question'] = array();
		
		$this->dashboard = array();
		$this->dashboard['nb_dl'] = 0;
		$this->dashboard['nb_conn'] = 0;
		$this->dashboard['top_ten_user']['nbdoc'] = array();
		$this->dashboard['top_ten_user']['nbprev'] = array();
		$this->dashboard['top_ten_user']['poiddoc'] = array();
		$this->dashboard['top_ten_user']['poidprev'] = array();
		$this->dashboard['activity'] = array();
		$this->dashboard['activity_day'] = array();
		$this->dashboard['activity_added'] = array();
		$this->dashboard['activity_edited'] = array();
		$this->dashboard['top_dl']['document'] = array();
		$this->dashboard['top_dl']['preview'] = array();
		$this->dashboard['ask'] = array();
		$this->dashboard['top_ten_site'] = array();
		$this->dashboard['top_ten_question'] = array();
	}
	
	/**
	 * @desc fill all data in the unoganised dashboard
	 * @return void
	 */
	private function fillDataInDahsboard()
	{
		foreach($this->all_sbas_desc as $sbas_id => $array)
		{
			$i = 0;
		////////////on ajoute toutes les connexions et les dl de chaque base
			$this->dashboard['nb_dl'] += $array['nb_dl'];
			$this->dashboard['nb_conn'] += $array['nb_conn'];
		////////////si il y a des resultats on cumul les users les plus actif  (doc)
			if(isset($array['top_ten_user']) && sizeof($array['top_ten_user']) > 0)
			{
				foreach($array['top_ten_user'] as $id => $stat)
				{
					if(array_key_exists($id, $this->unorganised_dashboard['top_ten_user']))
					{
						$this->unorganised_dashboard['top_ten_user'][$id]['nbdoc'] += $stat['nbdoc'];
						$this->unorganised_dashboard['top_ten_user'][$id]['poiddoc'] += $stat['poiddoc'];
						$this->unorganised_dashboard['top_ten_user'][$id]['nbprev'] += $stat['nbprev'];
						$this->unorganised_dashboard['top_ten_user'][$id]['poidprev'] += $stat['poidprev'];
					}
					else
					{
						$this->unorganised_dashboard['top_ten_user'][$id]['user'] = $stat['user'];
						$this->unorganised_dashboard['top_ten_user'][$id]['nbdoc'] = $stat['nbdoc'];
						$this->unorganised_dashboard['top_ten_user'][$id]['poiddoc'] = $stat['poiddoc'];
						$this->unorganised_dashboard['top_ten_user'][$id]['nbprev'] = $stat['nbprev'];
						$this->unorganised_dashboard['top_ten_user'][$id]['poidprev'] = $stat['poidprev'];
					}
				} 
			}
			
			if(sizeof($array['top_ten_site']) > 0)
			{
				foreach($array['top_ten_site'] as $referrer => $nb)
				{
					if(array_key_exists($referrer, $this->unorganised_dashboard['top_ten_site']))
					{
						$this->unorganised_dashboard['top_ten_site'][$referrer] += $nb;
					}
					else
					{
						$this->unorganised_dashboard['top_ten_site'][$referrer] = $nb;
					}
				} 
			}
		////////////si il y a des resultats on cumule tout les documents les plus dl 
			if(sizeof($array['top_dl']['document']) > 0)
			{
				foreach($array['top_dl']['document'] as $id => $stat)
				{
					if(!empty($id))
					{
						if(array_key_exists($id, $this->unorganised_dashboard['top_dl']['document']))
							$this->unorganised_dashboard['top_dl']['document'][$id]['nbdoc'] += $stat['nb'];
						else
						{
							$this->unorganised_dashboard['top_dl']['document'][$id]['user'] = $stat['file'];
							$this->unorganised_dashboard['top_dl']['document'][$id]['nbdoc'] = $stat['nb'];
							$this->unorganised_dashboard['top_dl']['document'][$id]['sbasid'] = $stat['sbasid'];
						}
					}
				} 
			}
		///////////si il y a des resultats on cumule tout les preview les plus dl 
			if(sizeof($array['top_dl']['preview']) > 0)
			{
				foreach($array['top_dl']['preview'] as $id => $stat)
				{
					if(!empty($id))
					{
						if(array_key_exists($id, $this->unorganised_dashboard['top_dl']['preview']))
							$this->unorganised_dashboard['top_dl']['preview'][$id]['nbdoc'] += $stat['nb'];
						else
						{
							$this->unorganised_dashboard['top_dl']['preview'][$id]['user'] = $stat['file'];
							$this->unorganised_dashboard['top_dl']['preview'][$id]['nbdoc'] = $stat['nb'];
							$this->unorganised_dashboard['top_dl']['preview'][$id]['sbasid'] = $stat['sbasid'];
						}
					}
				} 
			}
		///////si il ya des resultats on cumul le nombre de toutes les questions posees
			if(isset($array['top_ten_user']) && sizeof($array['ask']) > 0)
			{
				foreach($array['ask'] as $id => $stat)
				{
					if(!empty($id))
					{
						if(array_key_exists($id, $this->unorganised_dashboard['ask']))
							$this->unorganised_dashboard['ask'][$id]['question'] += $stat['nb'];
						else
						{
							$this->unorganised_dashboard['ask'][$id]['user'] = $stat['user'];
							$this->unorganised_dashboard['ask'][$id]['question'] = $stat['nb'];
						}
					}
				} 
			}
		//////
			if(sizeof($array['top_ten_question']) > 0)
			{
				foreach($array['top_ten_question'] as $key => $info)
				{
					if(array_key_exists($key, $this->unorganised_dashboard['top_ten_question']))
					{
						$this->unorganised_dashboard['top_ten_question'][$key]['nb'] += $info['nb'];
					}
					else
					{
						$this->unorganised_dashboard['top_ten_question'][$key]['question'] = $info['question'];
						$this->unorganised_dashboard['top_ten_question'][$key]['nb'] = $info['nb'];
					}
				}
			}
			
		/////////////on cumule toutes les resultats des activitees par heures
			$i = 0;
			while($i < 24)
			{
				if(sizeof($array['activity']) > 0)
				{
					if(!isset($this->dashboard['activity']['value'][$i]))
						$this->dashboard['activity']['value'][$i] = (float)0; 
						
					if(isset($array['activity'][$i]))
						$this->dashboard['activity']['value'][$i] += $array['activity'][$i];
					
					$i++;
				}
			}
			
			
			
				
		////////////on cumule toutes les resultats des activitees par jours
			
			$i = 0;
			foreach($this->legendDay as $key => $date)
			{
				
				if(sizeof($array['activity_day']) > 0)
				{
					if(!isset($this->total['activity_day'][$i]))
						$this->total['activity_day'][$i] = (float)0;
					
					if(array_key_exists($date, $array['activity_day']))
						$this->total['activity_day'][$i] += (float)$array['activity_day'][$date];
				}
				
				
				if(sizeof($array['activity_day']) > 0)
				{
					if(!isset($this->dashboard['activity_day'][$sbas_id][$i]))
						$this->dashboard['activity_day'][$sbas_id][$i] = 0;
					
					if(array_key_exists($date, $array['activity_day']))
						$this->dashboard['activity_day'][$sbas_id][$i] += $array['activity_day'][$date];
				}
				
				if(sizeof($array['activity_added']) > 0)
				{
					if(!isset($this->dashboard['activity_added']['value'][$i]))
						$this->dashboard['activity_added']['value'][$i] = 0;
					
					if(array_key_exists($date, $array['activity_added']))
						$this->dashboard['activity_added']['value'][$i] += $array['activity_added'][$date];
				}
				
				
				if(sizeof($array['activity_edited']) > 0)
				{
					if(!isset($this->dashboard['activity_edited']['value'][$i]))
						$this->dashboard['activity_edited']['value'][$i] = 0;
					
					if(array_key_exists($date, $array['activity_edited']))
						$this->dashboard['activity_edited']['value'][$i] += $array['activity_edited'][$date];
				}
				
				$i++;
			}
		}
		
		

		
	}
	
	private function sortDataTopTenNbDlWeight()
	{
		if(sizeof($this->unorganised_dashboard['top_ten_user']['nbdoc']) > 0)
		{
			
		/////On tri par nb document
			foreach($this->unorganised_dashboard['top_ten_user'] as $key => $value)
			{
				$tri[$key] = $value['nbdoc'];
			}
			arsort($tri);
			
		/////On tri par poid des documents
			foreach($this->unorganised_dashboard['top_ten_user'] as $key => $value)
			{
				$tri2[$key] = $value['poiddoc'];
			}
			arsort($tri2);
			
		/////on reorganise $dashboard en fonction du tri pour les docs
			$i = 0;
			foreach($tri as $key => $nbdoc)
			{
				if($i < 10 && $this->unorganised_dashboard['top_ten_user'][$key]['nbdoc'] != 0)
				{
					$this->dashboard['top_ten_user']['nbdoc'][$key]['user'] = $this->unorganised_dashboard['top_ten_user'][$key]['user'];
					$this->dashboard['top_ten_user']['nbdoc'][$key]['nbdoc'] = $this->unorganised_dashboard['top_ten_user'][$key]['nbdoc'];
					$i++;
				}
				else
					break;
			}
			
		//////on reorganise $dashboard en fonction du tri pour le poid des docs
			$i = 0;
			foreach($tri2 as $key => $poiddoc)
			{
				if($i < 10 && $this->unorganised_dashboard['top_ten_user'][$key]['poiddoc'] != 0)
				{
					$this->dashboard['top_ten_user']['poiddoc'][$key]['user'] = $this->unorganised_dashboard['top_ten_user'][$key]['user'];
					$this->dashboard['top_ten_user']['poiddoc'][$key]['poiddoc'] = $this->unorganised_dashboard['top_ten_user'][$key]['poiddoc'];
					$i++;
				}
				else
					break;
			}
		}
		
		if(sizeof($this->unorganised_dashboard['top_ten_user']['nbprev']) > 0)
		{
			
			/////On tri par nb document
			foreach($this->unorganised_dashboard['top_ten_user'] as $key => $value)
			{
				$tri8[$key] = $value['nbprev'];
			}
			
			arsort($tri8);
			
		/////On tri par poid des documents
			foreach($this->unorganised_dashboard['top_ten_user'] as $key => $value)
			{
				$tri9[$key] = $value['poidprev'];
			}
			arsort($tri9);		
			
		/////on reorganise $dashboard en fonction du tri pour les prev
			$i = 0;
			foreach($tri8 as $key => $nbprev)
			{
				if($i < 10 && $this->unorganised_dashboard['top_ten_user'][$key]['nbprev'] != 0)
				{
					$this->dashboard['top_ten_user']['nbprev'][$key]['user'] = $this->unorganised_dashboard['top_ten_user'][$key]['user'];
					$this->dashboard['top_ten_user']['nbprev'][$key]['nbprev'] = $this->unorganised_dashboard['top_ten_user'][$key]['nbprev'];
					$i++;
				}
				else
					break;
			}
		
		//////on reorganise $dashboard en fonction du tri pour le poid des docs
			$i = 0;
			foreach($tri9 as $key => $poidprev)
			{
				if($i < 10 && $this->unorganised_dashboard['top_ten_user'][$key]['poidprev'] != 0)
				{
					$this->dashboard['top_ten_user']['poidprev'][$key]['user'] = $this->unorganised_dashboard['top_ten_user'][$key]['user'];
					$this->dashboard['top_ten_user']['poidprev'][$key]['poidprev'] = $this->unorganised_dashboard['top_ten_user'][$key]['poidprev'];
					$i++;
				}
				else
					break;
				
			}
		}
	}
	
	private function sortTopTenDocDl()
	{
	//////si il y a des resultats le top ten doc dl
		if(sizeof($this->unorganised_dashboard['top_dl']['document']) > 0)
		{
		/////On tri top_dl
			foreach($this->unorganised_dashboard['top_dl']['document'] as $key => $value)
				$tri3[$key] = $value['nbdoc'];
			arsort($tri3);
		//////on reorganise $dashboard en fonction du tri
			$i = 0;
			foreach($tri3 as $key => $nb)
			{
				if($i < 10)
					$this->dashboard['top_dl']['document'][$key] = $this->unorganised_dashboard['top_dl']['document'][$key];
				else
					break;
				$i++;
			}
		}
	}
	
	
	private function sortTopTenPrevDl()
	{
	//////si il y a des resultats le top ten doc dl
		if(sizeof($this->unorganised_dashboard['top_dl']['preview']) > 0)
		{
		/////On tri top_dl
			foreach($this->unorganised_dashboard['top_dl']['preview'] as $key => $value)
				$tri3[$key] = $value['nbdoc'];
			arsort($tri3);
		//////on reorganise $dashboard en fonction du tri
			$i = 0;
			foreach($tri3 as $key => $nb)
			{
				if($i < 10)
					$this->dashboard['top_dl']['preview'][$key] = $this->unorganised_dashboard['top_dl']['preview'][$key];
				else
					break;
				$i++;
			}
		}
	}
	
	private function sortTopTenSite()
	{
	//////si il y a des resultats le top ten doc dl
		if(sizeof($this->unorganised_dashboard['top_ten_site']) > 0)
		{
		/////On tri top_dl
			foreach($this->unorganised_dashboard['top_ten_site'] as $referrer => $value)
				$tri5[$referrer] = $value;
			arsort($tri5);
			
		//////on reorganise $dashboard en fonction du tri
			$i = 0;
			foreach($tri5 as $key => $nb)
			{
				if($i < 10)
					$this->dashboard['top_ten_site'][$key] = $this->unorganised_dashboard['top_ten_site'][$key];
				else
					break;
				$i++;
			}
		}
	}
	
	private function sortTopTenQuestion()
	{
		if(sizeof($this->unorganised_dashboard['ask']) > 0)
		{
		/////On tri top_dl
			foreach($this->unorganised_dashboard['ask'] as $key => $value)
				$tri4[$key] = $value['question'];
			arsort($tri4);
		//////on reorganise $dashboard en fonction du tri
			$i = 0;
			foreach($tri4 as $key => $nb)
			{
				if($i < 10)
					$this->dashboard['ask'][$key] = $this->unorganised_dashboard['ask'][$key];
				else
					break;
				$i++;
			}
		}
	}
	
	private function sortTopTenNbQuestion()
	{
		if(sizeof($this->unorganised_dashboard['top_ten_question']) > 0)
		{
		/////On tri top_dl
			foreach($this->unorganised_dashboard['top_ten_question'] as $key => $value)
				$tri5[$key] = $value['nb'];
			arsort($tri5);
		//////on reorganise $dashboard en fonction du tri
			$i = 0;
			foreach($tri5 as $key => $nb)
			{
				if($i < 10)
					$this->dashboard['top_ten_question'][$key] = $this->unorganised_dashboard['top_ten_question'][$key];
				else
					break;
				$i++;
			}
		}
	}
	
	/**
	 * @desc sort the dashboard
	 * @return void
	 */
	private function sortDashboard()
	{
		$this->sortDataTopTenNbDlWeight();
		$this->sortTopTenDocDl();
		$this->sortTopTenQuestion();
		$this->sortTopTenSite();
		$this->sortTopTenNbQuestion();
		$this->sortTopTenPrevDl();
	}
	
	/**
	 * @desc draw html tabl for nb download and nb connexion on the site
	 * @return string
	 */
	
	
	private function formatDashboard()
	{
		if(sizeof($this->dashboard['activity']) > 0)
		{
			for($i =0; $i < sizeof($this->dashboard['activity']['value']); $i++)
			{
				if( is_float($this->dashboard['activity']['value'][$i]))
					$this->dashboard['activity']['value'][$i] = number_format($this->dashboard['activity']['value'][$i],2);
				else
					$this->dashboard['activity']['value'][$i] = (int)($this->dashboard['activity']['value'][$i]);
			}
		}
		
		if(sizeof($this->dashboard['activity_day']) > 0)
		{
			foreach($this->dashboard['activity_day'] as $key => $value)
			{
				if(is_array($value) && sizeof($value) > 0)
				{
					foreach($value as $k => $v)
					{
						if(is_float($v))
						{
							$this->dashboard['activity_day'][$key][$k] = number_format($v, 2);
						}
						else
							$v = (int)($v);
					}
				}
			}
		}
		
		if(sizeof($this->dashboard['activity_edited']) > 0)
		{
			for($i =0; $i < sizeof($this->dashboard['activity_edited']['value']); $i++)
			{
				if($this->dashboard['activity_edited']['value'][$i] < 1 && $this->dashboard['activity_edited']['value'][$i] != 0)
					$this->dashboard['activity_edited']['value'][$i] = number_format($this->dashboard['activity_edited']['value'][$i], 2);
				else
					$this->dashboard['activity_edited']['value'][$i] = (int)($this->dashboard['activity_edited']['value'][$i]);
			}
		}
		
		if(sizeof($this->dashboard['activity_added']) > 0)
		{
			for($i =0; $i < sizeof($this->dashboard['activity_added']['value']); $i++)
			{
				if($this->dashboard['activity_added']['value'][$i] < 1 && $this->dashboard['activity_added']['value'][$i] != 0)
					$this->dashboard['activity_added']['value'][$i] = number_format($this->dashboard['activity_added']['value'][$i], 2);
				else
					$this->dashboard['activity_added']['value'][$i] = (int)($this->dashboard['activity_added']['value'][$i]);
			}
		}
	}
}