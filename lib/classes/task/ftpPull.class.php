<?php

class task_ftpPull extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	
	private $debug = false;
	
	public function getName()
	{
		return(_("task::ftp:FTP Pull"));
	}
	

	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	public function graphic2xml($oldxml)
	{
//		global $parm;
		$request = httpRequest::getInstance();
		
		$parm2 = $request->get_parms(
							"proxy"
							,"proxyport"
							,"host"
							,"port"
							,"user"
							,"password"
							,"ssl"
							,"ftppath"
							,"localpath"
							,"passive"
							, "period"
						);
		if( $dom = @DOMDocument::loadXML($oldxml) )
		{
			$xmlchanged = false;
			foreach(array("str:proxy","str:proxyport", "str:period", "boo:passive", "boo:ssl", "str:password", "str:user", "str:ftppath", "str:localpath", "str:port", "str:host") as $pname)
			{
				$ptype = substr($pname, 0, 3);
				$pname = substr($pname, 4);
				$pvalue = $parm2[$pname];
				if( $ns = $dom->getElementsByTagName($pname)->item(0) )
				{
					// le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
					while( ($n = $ns->firstChild) )
						$ns->removeChild($n);
				}
				else
				{
					// le champ n'existait pas dans le xml, on le cree
					$dom->documentElement->appendChild($dom->createTextNode("\t"));
					$ns = $dom->documentElement->appendChild($dom->createElement($pname));
					$dom->documentElement->appendChild($dom->createTextNode("\n"));
				}
				// on fixe sa valeur
				switch($ptype)
				{
					case "str":
						$ns->appendChild($dom->createTextNode($pvalue));
						break;
					case "boo":
						$ns->appendChild($dom->createTextNode($pvalue ? '1':'0'));
						break;
				}
				$xmlchanged = true;
			}
		}
		return($dom->saveXML());
	}
	
	// ====================================================================
	// xml2graphic : must fill the grapic form (using js) from xml
	// ====================================================================
	public function xml2graphic($xml, $form)
	{
		if( ($sxml = simplexml_load_string($xml)) )	// in fact XML IS always valid here...
		{
			// ... but we could check for safe values (ex. 0 < period < 3600)
?>
		<script type="text/javascript">
		<?php echo $form?>.proxy.value		= "<?php echo p4string::MakeString($sxml->proxy, "js", '"')?>";
		<?php echo $form?>.proxyport.value	= "<?php echo p4string::MakeString($sxml->proxyport, "js", '"')?>";
		<?php echo $form?>.period.value		= "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";

		<?php echo $form?>.localpath.value	= "<?php echo p4string::MakeString($sxml->localpath, "js", '"')?>";
		<?php echo $form?>.ftppath.value	= "<?php echo p4string::MakeString($sxml->ftppath, "js", '"')?>";
		<?php echo $form?>.host.value		= "<?php echo p4string::MakeString($sxml->host, "js", '"')?>";
		<?php echo $form?>.port.value		= "<?php echo p4string::MakeString($sxml->port, "js", '"')?>";
		<?php echo $form?>.user.value		= "<?php echo p4string::MakeString($sxml->user, "js", '"')?>";
		<?php echo $form?>.password.value	= "<?php echo p4string::MakeString($sxml->password, "js", '"')?>";
		<?php echo $form?>.ssl.checked		= <?php echo p4field::isyes($sxml->ssl) ? "true" : 'false'?>;
		<?php echo $form?>.passive.checked	= <?php echo p4field::isyes($sxml->passive) ? "true" : 'false'?>;
		</script>
<?php
			return("");
		}
		else	// ... so we NEVER come here
		{
			// bad xml
			return("BAD XML");
		}
	}
	
	// ====================================================================
	// printInterfaceJS() : generer le code js de l'interface 'graphic view'
	// ====================================================================
	public function printInterfaceJS()
	{
		global $parm;
?>
		<script type="text/javascript">
		function chgxmltxt(textinput, fieldname)
		{
			setDirty();
		}
		function chgxmlck(checkinput, fieldname)
		{
			setDirty();
		}
		function chgxmlpopup(popupinput, fieldname)
		{
			setDirty();
		}
		</script>
<?php
	}
	// ====================================================================
	// callback : must return the name graphic form to submit
	// if not implemented, assume 'graphicForm'
	// ====================================================================
	function getGraphicForm()
	{
		return('graphicForm');
	}
	
	// ====================================================================
	// printInterfaceHTML(..) : generer l'interface 'graphic view' !! EN UTF-8 !!
	// ====================================================================
	public function printInterfaceHTML()
	{
		global $parm;
?>
		<form name="graphicForm" onsubmit="return(false);">
			<br/>
			<?php echo('task::ftp:proxy')?> 
			<input type="text" name="proxy" style="width:400px;" onchange="chgxmltxt(this, 'proxy');"><br/>
			<br/>
			<?php echo('task::ftp:proxy port')?> 
			<input type="text" name="proxyport" style="width:400px;" onchange="chgxmltxt(this, 'proxyport');"><br/>
			<br/>
			
			<?php echo('task::ftp:host')?> 
			<input type="text" name="host" style="width:400px;" onchange="chgxmltxt(this, 'host');"><br/>
			<br/>
			<?php echo('task::ftp:port')?> 
			<input type="text" name="port" style="width:400px;" onchange="chgxmltxt(this, 'port');"><br/>
			<br/>
			<?php echo('task::ftp:user')?> 
			<input type="text" name="user" style="width:400px;" onchange="chgxmltxt(this, 'user');"><br/>
			<br/>
			<?php echo('task::ftp:password')?> 
			<input type="password" name="password" style="width:400px;" onchange="chgxmltxt(this, 'password');"><br/>
			<br/>
			<?php echo('task::ftp:chemin distant')?> 
			<input type="text" name="ftppath" style="width:400px;" onchange="chgxmltxt(this, 'ftppath');"><br/>
			<br/>
			<?php echo('task::ftp:localpath')?> 
			<input type="text" name="localpath" style="width:400px;" onchange="chgxmltxt(this, 'localpath');"><br/>
			<br/>
			
			<input type="checkbox" name="passive" onchange="chgxmlck(this)">
			<?php echo _('task::ftp:mode passif')?>
			<br/>
			<input type="checkbox" name="ssl" onchange="chgxmlck(this)">
			<?php echo _('task::ftp:utiliser SSL')?>
			<br/>
			<?php echo('task::_common_:periodicite de la tache')?> 
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');">
			&nbsp;<?php echo('task::_common_:minutes (unite temporelle)')?><br/>
		</form>
<?php
	}

	// saveChanges() : enregistrer les changements de teches [ !!!! appele par 'chgtask.x.php' !!!! ]
	// doit retourner true si saved, false si error
	public function saveChanges(&$conn, $taskid, &$taskrow)
	{
		// global $parm;
		// "tid"
		// "xml"
		// "name"
		// "active"
		// "sbasid"		// non pris en compte si xml est fixe
		// "hot"		// non pris en compte si xml est fixe
		// "debug"

		$request = httpRequest::getInstance();
		
		$parm = $request->get_parms(
							  "xml"		// xml 'raw' est prioritaire sur les champs 'graphic view'
							, "name"
							, "active"
							// non pris en compte si xml est fixe : ...
							, "proxy"
							, "proxyport"
							, "period"
							
							, "localpath"
							, "ftppath"
							, "port"
							, "host"
							, "user"
							, "password"
							, "passive"
							, "ssl"
							// ...
							, "debug"
						);

		if($parm["xml"]===null)
		{
			// pas de xml 'raw' : on accepte les champs 'graphic view'
			if( $domTaskSettings = DOMDocument::loadXML($taskrow["settings"]) )
			{
				$xmlchanged = false;
				foreach(array("proxy", "proxyport", "period", "host", "port", "user", "password", "ssl", "passive", "localpath", "ftppath") as $f)
				{
					if($parm[$f] !== NULL)
					{
						if( $ns = $domTaskSettings->getElementsByTagName($f)->item(0) )
						{
							// le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
							while( ($n = $ns->firstChild) )
								$ns->removeChild($n);
						}
						else
						{
							// le champ n'existait pas dans le xml, on le cree
							$domTaskSettings->documentElement->appendChild($domTaskSettings->createTextNode("\t"));
							$ns = $domTaskSettings->documentElement->appendChild($domTaskSettings->createElement($f));
							$domTaskSettings->documentElement->appendChild($domTaskSettings->createTextNode("\n"));
						}
						// on fixe sa valeur
						$ns->appendChild($domTaskSettings->createTextNode($parm[$f]));
						$xmlchanged = true;
					}
				}
				if($xmlchanged)
					$parm["xml"] = $domTaskSettings->saveXML();
			}
		}
			
		// si on doit changer le xml, on verifie qu'il est valide
		if($parm["xml"] && !DOMDocument::loadXML($parm["xml"]))
			return(false);
				
		$sql = "";
		if($parm["xml"] !== NULL)
			$sql .= ($sql?" ,":"") . "settings='" . $conn->escape_string($parm["xml"]) . "'";
		if($parm["name"] !== NULL)
			$sql .= ($sql?" ,":"") . "name='" . $conn->escape_string($parm["name"]) . "'";
		if($parm["active"] !== NULL)
			$sql .= ($sql?" ,":"") . "active='" . $conn->escape_string($parm["active"]) . "'";
		
		if($sql)
		{
			$sql = "UPDATE task2 SET $sql WHERE task_id='" . $conn->escape_string($taskid)."'";
			if($parm["debug"])
			{
				printf("sql=%s\n", htmlentities($sql));
			}
			else
			{
				if($rs = $conn->query($sql))
					return(true);
				else
					return(false);
			}				
		}
		else
		{
			return(true);
		}
	}
	


	
	
	// ======================================================================================================
	// ===== le code d'execution de la teche proprement dite
	// ======================================================================================================
	
	public $argt = array();
	
	function run()
	{
		$conn = connection::getInstance();
		
		$taskid = $this->taskid;
		
		$this->log(sprintf(_("task::ftp:ftptask (taskid=%s) started"), $taskid));
		
		// on update la tache en 'active' pour pouvoir la lancer e la mano (hors scheduler)
		$sql = "UPDATE task2 SET active=2 WHERE task_id='" . $this->taskid."'" ;
		$conn->query($sql);
		
		$ret = $this->run2();
		
		$this->log(sprintf(_("task::ftp:ftptask (taskid=%s) ended"), $taskid));
		return($ret);
	}
	
	
	function run2()
	{
		$running = true;
		
		$loops = 0; 
		
		$conn = connection::getInstance();
		
		$ret = 'stopped';
		
		while($conn && $running == true)
		{
			$proxyport = $proxy = $time2sleep = null;
			
			$sql = "UPDATE task2 SET last_exec_time=NOW() WHERE task_id='" . $this->taskid."'" ;
			$conn->query($sql);
			
			$running = false;
			$sql = "SELECT settings FROM task2 WHERE active=2 AND (status='started' or status='manual') AND task_id='" . $this->taskid."'" ;	
		
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$running = true;
		
					if($taskprefs =  simplexml_load_string($row["settings"]))
					{
						$period 		= (string)($taskprefs->period);
						$proxy 			= (string)($taskprefs->proxy);
						$proxyport		= (string)($taskprefs->proxyport);		
						
						$host			= (string)($taskprefs->host);		
						$port			= (string)($taskprefs->port);		
						$user			= (string)($taskprefs->user);		
						$password		= (string)($taskprefs->password);		
						$ssl			= (string)($taskprefs->ssl) ? true : false;		
						$passive		= (string)($taskprefs->passive) ? true : false;		
						$ftppath		= (string)($taskprefs->ftppath);		
						$localpath		= (string)($taskprefs->localpath);		
					}
					$time2sleep = (int)($period);
				}
				$conn->free_result($rs);
			}
			
			$todo = 0;
			$done = 0;
			
			$this->setProgress($done, $todo);
				
			try
			{
				if(!is_dir($localpath) || !p4::fullmkdir($localpath))
					throw new Exception("$localpath is not writeable\n");
					
				if(!is_writeable($localpath))
					throw new Exception("$localpath is not writeable\n");
					
				$ftp = new ftpclient($host, $port, 90, $ssl, $proxy, $proxyport);
				$ftp->login($user, $password);
				$ftp->chdir($ftppath);
				$list_1 = $ftp->list_directory(true);
				
				$todo = count($list_1);
				$this->setProgress($done, $todo);
				
				if($this->debug)
					echo "attente de 25sec pour avoir les fichiers froids...\n";
				sleep(25);
				
				$list_2 = $ftp->list_directory(true);
				
				foreach($list_1 as $filepath=>$timestamp)
				{
					$done++;
					$this->setProgress($done, $todo);
					
					if(!isset($list_2[$filepath]))
					{
						if($this->debug)
							echo "le fichier $filepath a disparu...\n";
						continue;
					}
					if($list_2[$filepath] !== $timestamp)
					{
						if($this->debug)
							echo "le fichier $filepath a ete modifie depuis le dernier passage...\n";
						continue;
					}
					
					$finalpath = p4string::addEndSlash($localpath) . ($filepath[0] == '/' ? mb_substr($filepath, 1) : $filepath);
					echo "Ok pour rappatriement de $filepath vers $finalpath\n";

					try
					{
						if(file_exists($finalpath))
							throw new Exception("Un fichier du meme nom ($finalpath) existe deja...");

						p4::fullmkdir(dirname($finalpath));
												
						$ftp->get($finalpath, $filepath);
						$ftp->delete($filepath);
					}
					catch(Exception $e)
					{
						if($this->debug)
							echo "Erreur lors du rappatriement de $filepath : ".$e->getMessage()."\n";	
					}
				}
				
				$ftp->close();
				
				$this->setProgress(0,0);
			}
			catch(Exception $e)
			{
				if(isset($ftp) && $ftp instanceof ftpclient)
					$ftp->close();
				echo $e->getMessage()."\n";
			}
			$this->setProgress(0,0);
			
			$loops++;
			
			if($loops > 3 || memory_get_usage()>>20 >= 15)
			{
				$ret = 'torestart';
				$this->running = false;
			}		
			if($running)
			{
				$_time = 600;
				
				if($time2sleep!=null && $time2sleep>0)
					$_time = $time2sleep * 60;

				$conn->close();
				unset($conn);
				sleep($_time);
				$conn = connection::getInstance();
			}
		}
		return $ret;
	}
	
}
