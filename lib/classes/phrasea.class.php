<?php
class phrasea
{
	private static $_bases = false;
	private static $_bas2sbas = false;
	private static $_sbas_names = false;
	private static $_coll2bas = false;
	private static $_bas2coll = false;
	private static $_bas_names = false;
	private static $_basesettings = false;
	private static $_sbas_params = false;

	public static function start()
	{
		require (dirname(__FILE__).'/../../config/connexion.inc'); 

		if( !extension_loaded('phrasea2')	)
			printf("Missing Extension php-phrasea");
		
		if(function_exists('phrasea_conn'))
			if(phrasea_conn($hostname,$port,$user,$password,$dbname)!==true)
				self::headers(500);
		
	}
	
	function getHome($type='PUBLI',$context='prod')
	{
		$session = session::getInstance();
		if($type == 'HELP')
		{
			if( file_exists(GV_RootPath."config/help_".$session->usr_i18n.".php") )
			{
				require(GV_RootPath."config/help_".$session->usr_i18n.".php") ;
			}
			elseif( file_exists(GV_RootPath.'config/help.php') )// on verifie si il y a une home personnalisee sans langage
			{
				require(GV_RootPath.'config/help.php') ;
			}
			else 
			{
				require(GV_RootPath.'www/client/help.php') ;
			}
		}
		
		if($type == 'PUBLI')
		{
			$conn = connection::getInstance();
			if($context == 'prod')
				require(GV_RootPath."www/prod/homeinterpubbask.php") ;
			else
				require(GV_RootPath."www/client/homeinterpubbask.php") ;
		}
		
		if(in_array($type, array('QUERY', 'LAST_QUERY')))
		{
			$context = in_array($context,array('client','prod')) ? $context : 'prod';
			$parm = array();
			
			$bas = array();
			
			$searchSet = json_decode(user::getPrefs('search'));
			
			if($searchSet && isset($searchSet->bases))
			{
				foreach($searchSet->bases as $bases)
					$bas = array_merge($bas, $bases);
			}
			else
			{
			
				$lb = phrasea_open_session($session->ses_id,$session->usr_id);
				
				foreach($lb['bases'] as $base)
					foreach($base['collections'] as $coll)
						$bas[] = $coll['base_id'];
			}
			
			$start_page_query = user::getPrefs('start_page_query');
			
			if($context == "prod")
			{
				$parm["bas"] = $bas;
				$parm["qry"] = $start_page_query;
				$parm["pag"] = 0;
				$parm["sel"] = '';
				$parm["ord"] = null;
				$parm["search_type"] = 0;
				$parm["recordtype"] = '';
				$parm["status"] = array();
				$parm["fields"] = array();
				$parm["datemin"] = '';
				$parm["datemax"] = '';
				$parm["datefield"] = '';
			}
			if($context == "client")
			{
				$parm["mod"] = user::getPrefs('client_view');
				$parm["bas"] = $bas;
				$parm["qry"] = $start_page_query;
				$parm["pag"] = '';
				$parm["search_type"] = 0;
				$parm["qryAdv"] = '';
				$parm["opAdv"] = array();
				$parm["status"] = '';
				$parm["nba"] = '';
				$parm["datemin"] = '';
				$parm["datemax"] = '';
				$parm["dateminfield"] = array();
				$parm["datemaxfield"] = array();
				$parm["infield"] = '';
				$parm["regroup"] = null;
				$parm["ord"] = 0;
				
			}
			
			
			require(GV_RootPath.'www/'.$context."/answer.php") ;
		}
		
		return;
	}	
		
	public static function sbas_params()
	{
		if(self::$_sbas_params)
			return self::$_sbas_params;
		
		self::$_sbas_params = array();
		
		$conn = connection::getInstance();
		
		$sql = 'SELECT sbas_id, host, port, user, pwd, dbname FROM sbas';// WHERE sbas_id="'.$conn->escape_string($name).'"';
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				self::$_sbas_params[$row['sbas_id']] = $row;
			}
		}
		
		return self::$_sbas_params;
	}
	
	public static function guest_allowed()
	{
		$conn = connection::getInstance();
		
		$ret = false;
		
		$sql = 'SELECT basusr.* FROM basusr, bas, usr WHERE usr.usr_login="invite" AND bas.active="1" AND basusr.actif="1" AND basusr.base_id = bas.base_id AND usr.usr_id=basusr.usr_id';
		if($rs = $conn->query($sql))
		{
			if($conn->num_rows($rs) > 0)
				$ret = true;
			$conn->free_result($rs);
		}
		
		return $ret;
	}
	
	public static function load_events()
	{
		$events = eventsmanager::getInstance();
		$events->start();
	}
	
	public static function use_i18n($locale = false, $textdomain = 'phraseanet')
	{
		$session = session::getInstance();
			
		$codeset = "UTF8"; 
		
		$default = 'en_GB';
		if(defined('GV_default_lng'))
			$default = GV_default_lng;
		
		if(!$locale)
			$locale = isset($session->locale) ? $session->locale : $default;
		
		putenv('LANG='.$locale.'.'.$codeset);
		putenv('LANGUAGE='.$locale.'.'.$codeset);
		bind_textdomain_codeset($textdomain, $codeset);
		
		bindtextdomain($textdomain, dirname( __FILE__ ).'/../../locale/');
		setlocale(LC_ALL, $locale.'.'.$codeset);
		textdomain($textdomain); 
	}
	
	public static function modulesName($array_modules)
	{
		$array = array();
		
		$modules = array(
			1	=>	_('admin::monitor: module production'),
			2	=>	_('admin::monitor: module client'),
			3	=>	_('admin::monitor: module admin'),
			4	=>	_('admin::monitor: module report'),
			5	=>	_('admin::monitor: module thesaurus'),
			6	=>	_('admin::monitor: module comparateur'),
			7	=>	_('admin::monitor: module validation'),
			8	=>	_('admin::monitor: module upload')
		);
		
		foreach($array_modules as $a)
		{
			if(isset($modules[$a]))
				$array[] = $modules[$a];
		}
		
		
		return $array;
	}
	
	public static function bases()
	{
		if(!self::$_bases)
		{
			$cache = cache_appbox::getInstance();
			if(($tmp = $cache->get('list_bases')) != false)
			{
				self::$_bases = $tmp;
			}
			else
			{
				self::$_bases = phrasea_list_bases();
			
				$cache->set('list_bases', self::$_bases);
			}
		}
		return self::$_bases;
	}
	
				
				
	public static function sbasFromBas($base_id)
	{
		if(!self::$_bas2sbas)
		{
			$cache = cache_appbox::getInstance();
			if(($tmp = $cache->get('sbas_from_bas')) != false)
			{
				self::$_bas2sbas = $tmp;
			}
			else
			{
				$bases = self::bases();
				foreach($bases['bases'] as $base)
					foreach($base['collections'] as $coll)
						self::$_bas2sbas[$coll['base_id']] = $base['sbas_id'];
			
				$cache->set('sbas_from_bas', self::$_bas2sbas);
			}
		}
		return isset(self::$_bas2sbas[$base_id]) ? self::$_bas2sbas[$base_id] : false;
	}
	
				
	public static function baseFromColl($sbas_id, $coll_id)
	{
		if(!self::$_coll2bas)
		{
				$bases = self::bases();
				foreach($bases['bases'] as $base)
				{
					if(!isset(self::$_coll2bas[$base['sbas_id']]))
						self::$_coll2bas[$base['sbas_id']] = array();
					foreach($base['collections'] as $coll)
						self::$_coll2bas[$base['sbas_id']][$coll['coll_id']] = $coll['base_id'];
				}
			
		}
		return isset(self::$_coll2bas[$sbas_id][$coll_id]) ? self::$_coll2bas[$sbas_id][$coll_id] : false;
	}
	
	public static function collFromBas($base_id)
	{
		if(!self::$_bas2coll)
		{
				$bases = self::bases();
				foreach($bases['bases'] as $base)
				{
					foreach($base['collections'] as $coll)
						self::$_bas2coll[$coll['base_id']] = $coll['coll_id'];
				}
			
		}
		return isset(self::$_bas2coll[$base_id]) ? self::$_bas2coll[$base_id] : false;
	}
	
	public static function sbas_names($sbas_id)
	{
		if(!self::$_sbas_names)
		{
			$cache = cache_appbox::getInstance();
			if(($tmp = $cache->get('sbas_names')) != false)
			{
				self::$_sbas_names = $tmp;
			}
			else
			{
				$bases = self::bases();
				foreach($bases['bases'] as $base)
					self::$_sbas_names[$base['sbas_id']] = $base['viewname'];
			
				$cache->set('sbas_names', self::$_sbas_names);
			}
		}

		return isset(self::$_sbas_names[$sbas_id]) ? self::$_sbas_names[$sbas_id] : 'Unknown base';
	}
	
	public static function bas_names($base_id)
	{
		if(!self::$_bas_names)
		{
			$cache = cache_appbox::getInstance();
			if(($tmp = $cache->get('bas_names')) != false)
			{
				self::$_bas_names = $tmp;
			}
			else
			{
				$bases = self::bases();
				foreach($bases['bases'] as $base)
					foreach($base['collections'] as $coll)
						self::$_bas_names[$coll['base_id']] = $coll['name'];
			
				$cache->set('bas_names', self::$_bas_names);
			}
		}

		return isset(self::$_bas_names[$base_id]) ? self::$_bas_names[$base_id] : 'Unknown collection';
	}
	
	public static function getBasesOrder()
	{
		$ret = array();
		
		$conn = connection::getInstance();
		$sql = 'SELECT b.base_id, b.sbas_id, b.server_coll_id  FROM bas b, sbas s WHERE b.sbas_id = s.sbas_id ORDER BY s.ord ASC, b.ord ASC, b.server_coll_id ASC';
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$ret[$row['base_id']] = array('base_id'=>$row['base_id'],'sbas_id'=>$row['sbas_id'],'server_coll_id'=>$row['server_coll_id']);	
			}
			$conn->free_result($rs);
		}
		
		return $ret;
	}
	
	public static function headers($code = 200,$nocache=false)
	{
		switch((int)$code)
		{
			case 204:
			case 403:
			case 404:
			case 400:
			case 500:
					$request = httpRequest::getInstance();
					$request->set_code($code);
					include(dirname( __FILE__ ) . '/../../www/include/error.php');
					die();
				break;
			
			case 200:
					if($nocache)
					{
						header("Content-Type: text/xml; charset=UTF-8");
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
						header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
						header("Cache-Control: post-check=0, pre-check=0", false);
						header("Pragma: no-cache");                          // HTTP/1.0
					}
					header("Content-Type: text/html; charset=UTF-8");
					echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
				break;
		}
		return;
	}
	
	public static function write_connection($host,$port,$usr,$pwd,$db)
	{
		$EOL = "\n";
	
		$connexionINI = '<?php'.$EOL;
		$connexionINI .= '$hostname = \''.$host.'\';'.$EOL;
		$connexionINI .= '$port = \''.$port.'\';'.$EOL;
		$connexionINI .= '$user = \''.$usr.'\';'.$EOL;
		$connexionINI .= '$password = \''.$pwd.'\';'.$EOL;
		$connexionINI .= '$dbname = \''.$db.'\';'.$EOL;
		
		$connection = dirname( __FILE__ ) . '/../../config/connexion.inc';
		
		if(file_put_contents($connection, $connexionINI) !== FALSE)
		{
			return true;
		}	
		return false;
	}
	
	public static function load_settings($locale)
	{
		
		$i18n_code = array_pop(array_reverse(explode('_',$locale)));
		
		$appbox_memcache = cache_appbox::getInstance();
	
		if(self::$_basesettings != false)
		{
			return self::$_basesettings;
		}
		if(($basesettings = $appbox_memcache->get('bases_settings_'.$locale)) != false)
		{
			return $basesettings;
		}
		
		if(!$basesettings)
		{
			$basesettings = array("bases"=>array(), "colls"=>array(), "xsltinfo"=>null);
			
			
			$xml = new DomDocument();
			if($xml->load(GV_RootPath . "www/skins/lng/".$locale."_docinfo.xsl"))
			{
				$basesettings["xsltinfo"] = $xml->saveXML();
			}
			
			$lb = self::bases();

			foreach($lb["bases"] as $base)
			{
				$connbas = connection::getInstance($base['sbas_id']);
				if(!$connbas)
					continue;
				$structure = false;
				if($rs = $connbas->query('SELECT value FROM pref WHERE prop="structure"'))
				{
					if($row = $connbas->fetch_assoc($rs))
						$structure = trim($row['value']);
					$connbas->free_result($rs);
				}
				if(!$structure)
					continue;
				
				$sbas_id = $base["sbas_id"];
				$basesettings["bases"][$sbas_id] = array();
				$zxmlbase = answer::getXslRollOver2($structure, "PRODUCTION_grid", $i18n_code);
				$homelink_xsl = answer::getXslRollOver2($structure, "homelink", $i18n_code);
				$xmlbase = answer::getXslRollOver2Grp($structure, "PRODUCTION_grid", $i18n_code);
			
				$basesettings["bases"][$sbas_id]["xsl_title"] = false;
				$basesettings["bases"][$sbas_id]["structure"] = $structure;
				
				if($sxe = simplexml_load_string($structure))
				{
					$array_fields = array();
					foreach($sxe->description->children() as $fldname=>$fld)
					{
						if($fld["thumbTitle"]=="1" || mb_strtolower($fld["thumbTitle"])==mb_strtolower($i18n_code))
						{
							$array_fields[] = $fldname;
						}
					}
					
					if(count($array_fields) > 0 )
					{
						$default_xml = '<?xml version="1.0" encoding="utf-8"?>
							<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
							<xsl:output method="html"/>';
						
						$array_xml = array();
						
						$idxTitle = 0;
						foreach($array_fields as $fldname)
						{
							$array_xml[] = '<xsl:template match="description/'.$fldname.'">
									'.($idxTitle++?' - ':'').'<xsl:apply-templates />
									</xsl:template>';
						}
					
						$default_xml .= implode('',$array_xml);
						
						$default_xml .= '<xsl:template match="description/*" />
									</xsl:stylesheet>';
						if($xml->loadXML($default_xml))
						{
							$basesettings["bases"][$sbas_id]["defaultxml"]	= false;
							$basesettings["bases"][$sbas_id]["xsl_title"] 	= $default_xml;
						}
					}
					
				}
	
				if(!$basesettings["bases"][$sbas_id]["xsl_title"])
				{
					$default_xml = '<?xml version="1.0" encoding="UTF-8"?>' .
						'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' .
						'<xsl:output method="html"/>' .
						'<xsl:decimal-format name="french" decimal-separator="," grouping-separator="" />' .
						'<xsl:decimal-format name="us" decimal-separator="." grouping-separator="" />' .
						'<xsl:template match="/">' .
						'<xsl:value-of select="record/doc/@originalname"/>' .
						'</xsl:template>' .
						'</xsl:stylesheet>';
					
					if($xml->loadXML($default_xml))
					{
						$basesettings["bases"][$sbas_id]["defaultxml"]	= true;
						$basesettings["bases"][$sbas_id]["xsl_title"] 	= $default_xml;
					}
				}
				
				
				foreach($base["collections"] as $coll)
				{
					$base_id = $coll["base_id"];
					$basesettings["colls"][$base_id] = array("sbas_id"=>$sbas_id, "prefs"=>$coll["prefs"]);
					
					$zxmlcoll = answer::getXslRollOver2($basesettings["colls"][$base_id]["prefs"], "PRODUCTION_grid", $i18n_code);
					
					// prefs de collection non (encore) connues : on essaye de charger
					$basesettings["colls"][$base_id]["xsltRollOver"] = null;
					
					$xml = new DomDocument();
					if(trim($zxmlcoll) !== '' && $xml->loadXML($zxmlcoll))
					{
						$basesettings["colls"][$base_id]["xsltRollOver"] = $zxmlcoll;
					}
					elseif(trim($zxmlbase) !== '' && $xml->loadXML($zxmlbase))
					{
						$basesettings["colls"][$base_id]["xsltRollOver"] = $zxmlbase;
					}
					else
					{
						$xsl = '<?xml version="1.0" encoding="utf-8"?>
							<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
							<xsl:output method="html"/>
							<xsl:key name="kk" match="/record/description/*[.!=\'\']" use="name()" />
							
							<xsl:template match="/record/description">
								<xsl:for-each select="*[count( . | key(\'kk\', name())[1] )=1]">
									<xsl:variable name="xx" select="count(key(\'kk\', name()))"/>
									<xsl:if test="$xx > 0">
										<xsl:choose> 
											<xsl:when test="position() mod 2!=\'0\'">
												<div class=\'descpair\'>
														<b><xsl:value-of select="name()" /> : </b>
										<xsl:apply-templates select="key(\'kk\', name())" />
										</div>
												</xsl:when> 
												<xsl:otherwise>
												<div class=\'descimpair\'>
														<b><xsl:value-of select="name()" /> : </b>
										<xsl:apply-templates select="key(\'kk\', name())" />
										</div>
												</xsl:otherwise> 
										</xsl:choose> 
									</xsl:if>
								</xsl:for-each>
							</xsl:template>
							
							<xsl:template match="/record/description/*">
								<xsl:if test="position()>1"> ; </xsl:if>
								<xsl:value-of select="current()" />
							</xsl:template>
							
							</xsl:stylesheet>';
						if($xml->loadXML($xsl))					
						{
							$basesettings["colls"][$base_id]["xsltRollOver"] = $xsl;
						}
					}
				
					if(trim($homelink_xsl) !== '' && $xml->loadXML($homelink_xsl))
					{
						$basesettings["colls"][$base_id]["xslthomelink"] = $homelink_xsl;
					}
					elseif(trim($zxmlbase) !== '' && $xml->loadXML($zxmlbase))
					{
						$basesettings["colls"][$base_id]["xslthomelink"] = $basesettings["colls"][$base_id]["xsltRollOver"];
					}
					
				
					// prefs de collection non (encore) connues : on essaye de charger
					$basesettings["colls"][$base_id]["xsltRollOverGrp"] = null;
					
					$xmlcoll = answer::getXslRollOver2Grp($basesettings["colls"][$base_id]["prefs"], "PRODUCTION_grid", $i18n_code); 
					
					if(trim($xmlcoll) !== '' && $xml->loadXML($xmlcoll))
					{
						$basesettings["colls"][$base_id]["xsltRollOverGrp"] = $xmlcoll;
					}
					elseif(trim($xmlbase) !== '' && $xml->loadXML($xmlbase))
					{
						$basesettings["colls"][$base_id]["xsltRollOverGrp"] = $xmlbase;
					}
					else
					{
							$xslt = '<?xml version="1.0" encoding="utf-8"?>
								<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
								<xsl:output method="html"/>
								<xsl:key name="kk" match="/record/description/*[.!=\'\']" use="name()" />
								
								<xsl:template match="/record/description">
									<xsl:for-each select="*[count( . | key(\'kk\', name())[1] )=1]">
										<xsl:variable name="xx" select="count(key(\'kk\', name()))"/>
										<xsl:if test="$xx > 0">
											<xsl:choose> 
												<xsl:when test="position() mod 2!=\'0\'">
													<div class=\'descpair\'>
															<b><xsl:value-of select="name()" /> : </b>
											<xsl:apply-templates select="key(\'kk\', name())" />
											</div>
													</xsl:when> 
													<xsl:otherwise>
													<div class=\'descimpair\'>
															<b><xsl:value-of select="name()" /> : </b>
											<xsl:apply-templates select="key(\'kk\', name())" />
											</div>
													</xsl:otherwise> 
											</xsl:choose> 
										</xsl:if>
									</xsl:for-each>
								</xsl:template>
								
								<xsl:template match="/record/description/*">
									<xsl:if test="position()>1"> ; </xsl:if>
									<xsl:value-of select="current()" />
								</xsl:template>
								
								</xsl:stylesheet>';
							if($xml->loadXML($xslt))		
							
						{
							$basesettings["colls"][$base_id]["xsltRollOverGrp"] = $xslt;
						}
					}
					
					
				}
			}
			$appbox_memcache->set('bases_settings_'.$locale,$basesettings);
			self::$_basesettings = $basesettings;
		}
		
		return $basesettings;
		
	}
	
	public static function scheduler_key($renew = false)
	{
		$conn = connection::getInstance();

		$schedulerkey = false;
		
		$sql = 'SELECT schedulerkey FROM sitepreff';
		if(($rs = $conn->query($sql)))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$schedulerkey = $row['schedulerkey'];
			}
			$conn->free_result($rs);
		}
		
		if($renew === true || trim($schedulerkey) == '')
		{
			$schedulerkey = random::generatePassword(20);
			$sql = 'UPDATE sitepreff SET schedulerkey="'.$conn->escape_string($schedulerkey).'"';
			$conn->query($sql);
		}
		
		return $schedulerkey;
		
	}
		
}