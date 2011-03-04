<?php


class task_ftp extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_("task::ftp:FTP Push"));
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
							, "period"
						);
		if( $dom = @DOMDocument::loadXML($oldxml) )
		{
			$xmlchanged = false;
			foreach(array("str:proxy","str:proxyport", "str:period") as $pname)
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
		<?php echo $form?>.proxy.value            = "<?php echo p4string::MakeString($sxml->proxy, "js", '"')?>";
		<?php echo $form?>.proxyport.value            = "<?php echo p4string::MakeString($sxml->proxyport, "js", '"')?>";
		<?php echo $form?>.period.value           = "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";
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
		<form name="graphicForm" onsubmit="return(false);" method="post">
			<br/>
			<?php echo('task::ftp:proxy')?>
			<input type="text" name="proxy" style="width:400px;" onchange="chgxmltxt(this, 'proxy');"><br/>
			<br/>
			<?php echo('task::ftp:proxy port')?>
			<input type="text" name="proxyport" style="width:400px;" onchange="chgxmltxt(this, 'proxyport');"><br/>
			<br/>

			<?php echo('task::_common_:periodicite de la tache')?>&nbsp;:&nbsp;
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');">
			&nbsp;<?php echo('task::_common_:secondes (unite temporelle)')?><br/>
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
							// ...
							, "debug"
						);

		if($parm["xml"]===null)
		{
			// pas de xml 'raw' : on accepte les champs 'graphic view'
			if( $domTaskSettings = @DOMDocument::loadXML($taskrow["settings"]) )
			{
				$xmlchanged = false;
				foreach(array("proxy", "proxyport", "period") as $f)
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
		if($parm["xml"] && !@DOMDocument::loadXML($parm["xml"]))
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
		$debug = false;

		phrasea::start();

		$running = true;

		$conn = connection::getInstance();

		$ret = 'stopped';
                $loop = 0;

		while($conn && $running == true)
		{
			$proxyport = $proxy = $time2sleep = null;
			$ftp_exports = array();

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
					}
					$time2sleep = (int)($period);
				}
				$conn->free_result($rs);
			}

			$sql = "SELECT id FROM ftp_export WHERE crash>=nbretry AND date<'".phraseadate::format_mysql(new DateTime('-30 days'))."' " ;
			if($rs = $conn->query($sql))
			{
				while($rowtask = $conn->fetch_assoc($rs))
				{
					$conn->query("DELETE FROM ftp_export WHERE id='".$conn->escape_string($rowtask["id"])."'");
					$conn->query("DELETE FROM ftp_export_elements WHERE ftp_export_id='".$conn->escape_string($rowtask["id"])."'");
				}
				$conn->free_result($rs);
			}

			$sql = "SELECT * FROM ftp_export WHERE crash<nbretry ORDER BY id" ;
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
					$ftp_exports[$row["id"]] = array_merge(array('files'=>array()),$row);
				$conn->free_result($rs);
			}

			$sql = "SELECT e.* from ftp_export f
					INNER JOIN ftp_export_elements e on (f.id=e.ftp_export_id AND f.crash<f.nbretry AND (e.done = 0 or error=1))
					ORDER BY f.id" ;

			$todo = 0;
			$done = 0;

			if($rs = $conn->query($sql))
			{
				$todo = $conn->num_rows($rs);
				while($rowtask = $conn->fetch_assoc($rs))
				{
					if(isset($ftp_exports[$rowtask["ftp_export_id"]]))
						$ftp_exports[$rowtask["ftp_export_id"]]["files"][] = $rowtask;
				}
				$conn->free_result($rs);
			}

			$this->setProgress($done, $todo);

			foreach($ftp_exports as $id=>$ftp_export)
			{
				$ftp_exports[$id]["crash"] 		= (int)$ftp_export["crash"];
				$ftp_exports[$id]["nbretry"]	= (int)$ftp_export["nbretry"] < 1 ? 3 : (int)$ftp_export["nbretry"];

				$state 			= "";
				$ftp_server 	= $ftp_export["addr"] ;
				$ftp_user_name 	= $ftp_export["login"];
				$ftp_user_pass 	= $ftp_export["pwd"];
				$usr_id = (int)$ftp_export["usr_id"];

				$ftpLog = $ftp_user_name."@".p4string::addEndSlash($ftp_server).$ftp_export["destfolder"];

				if($ftp_export["crash"]==0)
				{
					$state .= $line = sprintf(_('task::ftp:Etat d\'envoi FTP vers le serveur "%1$s" avec le compte "%2$s" et pour destination le dossier : "%3$s"').PHP_EOL, $ftp_server, $ftp_user_name, $ftp_exports[$id]["destfolder"]);

					if($debug)
						echo $line;
				}

				$state .= $line = sprintf(_("task::ftp:TENTATIVE no %s"), $ftp_export["crash"]+1) . "  (".date('r').")" . PHP_EOL;

				if($debug)
					echo $line;

				if(($ses_id = phrasea_create_session($usr_id)) == null)
				{
					echo "Unable to create session\n";
					continue;
				}

				if(!($ph_session  =  phrasea_open_session($ses_id,$usr_id)))
				{
					echo "Unable to open session\n";
					phrasea_close_session($ses_id);
					continue;
				}

				try
				{
					$ssl = $ftp_export['ssl'] == '1' ? true : false;
					$ftp_client	= new ftpclient($ftp_server, 21, 300, $ssl, $proxy, $proxyport);
					$ftp_client->login($ftp_user_name, $ftp_user_pass);

					if($ftp_export["passif"]=="1")
					{
						try
						{
							$ftp_client->passive(true);
						}
						catch(Exception $e)
						{
							echo $e->getMessage();
						}
					}

					if(trim($ftp_export["destfolder"]) != '')
					{
						try
						{
							$ftp_client->chdir($ftp_export["destfolder"]);
							$ftp_export["destfolder"] = '/'.$ftp_export["destfolder"];
						}
						catch(Exception $e)
						{
							echo $e->getMessage();
						}
					}
					else
					{
						$ftp_export["destfolder"] = '/';
					}

					if(trim($ftp_export["foldertocreate"]) != '')
					{
						try
						{
							$ftp_client->mkdir($ftp_export["foldertocreate"]);
						}
						catch(Exception $e)
						{
							echo $e->getMessage();
						}
						try
						{
							$ftp_client->chdir($ftp_client->add_end_slash($ftp_export["destfolder"]).$ftp_export["foldertocreate"]);
						}
						catch(Exception $e)
						{
							echo $e->getMessage();
						}
					}

					$obj = array();

					$basefolder = (!in_array(trim($ftp_export["destfolder"]), array('.','./','')) ? p4string::addEndSlash($ftp_export["destfolder"]) : '') . $ftp_export["foldertocreate"];
					$basefolder = !in_array(trim($basefolder), array('.','./','')) ? $basefolder : '/';

					foreach ($ftp_export['files'] as $fileid=>$file)
					{
						$base_id 	= $file["base_id"];
						$record_id 	= $file["record_id"];
						$subdef 	= $file['subdef'];
						$sdcaption 	= phrasea_xmlcaption($ses_id, $base_id,$record_id);

						try
						{
							$remotefile = $file["filename"];

							if($subdef == 'caption')
							{
								$desc = export::get_caption($base_id, $record_id,$ses_id, false);

								$localfile = GV_RootPath.'tmp/'.md5($desc.time().mt_rand());
								if(file_put_contents($localfile,$desc) === false)
								{
									throw new Exception('Impossible de creer un fichier temporaire');
								}
							}
							else
							{
								$sd = phrasea_subdefs($ses_id, $base_id,$record_id, $subdef) ;

								if(!$sd || !isset($sd[$subdef]))
								{
									continue;
								}

								$localfile = p4string::addEndSlash($sd[$subdef]["path"]).$sd[$subdef]["file"];
								if(!file_exists($localfile))
								{
									throw new Exception('Le fichier local n\'existe pas');
								}
							}

							$current_folder = p4string::delEndSlash(str_replace('//','/',$basefolder.$file['folder']));

							if($ftp_client->pwd() != $current_folder)
							{
								try
								{
									$ftp_client->chdir($current_folder);
								}
								catch(Exception $e)
								{
									echo $e->getMessage();
								}
							}

							$ftp_client->put($remotefile, $localfile);


							$obj[] = array("name"=>$subdef, "size"=>filesize($localfile), "shortXml"=>($sdcaption?$sdcaption:''));

							if($subdef == 'caption')
							{
								unlink($localfile);
							}

							$sql = "UPDATE ftp_export_elements SET done='1', error='0' WHERE id='".$conn->escape_string($file["id"])."'";
							$conn->query($sql);
							$this->logexport($base_id, $record_id, $obj, $ftpLog, $ses_id);
						}
						catch(Exception $e)
						{
							$state .= $line = sprintf(_('task::ftp:File "%1$s" (record %2$s) de la base "%3$s" (Export du Document) : Transfert cancelled (le document n\'existe plus)'), basename($localfile), $record_id, phrasea::sbas_names(phrasea::sbasFromBas($base_id)))."\n<br/>";

							if($debug)
								echo $line;

							$done = $file['error'];

							$sql = "UPDATE ftp_export_elements SET done='".$conn->escape_string($done)."', error='1' WHERE id='".$conn->escape_string($file["id"])."'";
				 			$conn->query($sql);
						}
						$done++;
						$this->setProgress($done, $todo);
					}

					$ftp_client->close();
					unset($ftp_client);

				}
				catch(Exception $e)
				{
					$state .= $line = $e."\n";

					if($debug)
						echo $line;

					$conn->query("UPDATE ftp_export SET crash=crash+1,date=now() WHERE id='".$conn->escape_string($ftp_export["id"])."'");

					unset($ftp_client);
				}
				$this->finalize($id);
				phrasea_close_session($ses_id);
			}
			$this->setProgress(0,0);

			if($loop>2 || memory_get_usage()>>20 >= 15)
			{
				$conn->close();
				unset($conn);
                                sleep(20);
				$ret = 'torestart';
				$running = false;
			}
			if($running)
			{
				$_time = 60;

				if($time2sleep!=null && $time2sleep>0)
					$_time = $time2sleep;

				$conn->close();
				unset($conn);
				sleep($_time);
				$conn = connection::getInstance();
			}
                        $loop++;
		}
		return $ret;
	}

	function finalize($id)
	{
		$conn = connection::getInstance();
		$sql = 'SELECT crash, nbretry FROM ftp_export WHERE id="'.$conn->escape_string($id).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				if($row['crash'] >= $row['nbretry'])
				{
					$this->send_mails($id);
					$conn->free_result($rs);
					return $this;
				}
			}
			$conn->free_result($rs);
		}

		$sql = 'SELECT count(id) as total, sum(error) as errors, sum(done) as done
				FROM ftp_export_elements WHERE ftp_export_id="'.$conn->escape_string($id).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				var_dump((int)$row['done'],(int)$row['total']);

				if((int)$row['done'] == (int)$row['total'])
				{
					$this->send_mails($id);

					if((int)$row['errors'] == 0)
					{
						$sql = 'DELETE FROM ftp_export WHERE id = "'.$conn->escape_string($id).'"';
						$conn->query($sql);
						$sql = 'DELETE FROM ftp_export_elements WHERE ftp_export_id = "'.$conn->escape_string($id).'"';
						$conn->query($sql);
					}
					else
					{
						$sql = 'UPDATE ftp_export SET crash = nbretry';
						$conn->query($sql);
						$sql = 'DELET FROM ftp_export_elements WHERE ftp_export_id = "'.$conn->escape_string($id).'" AND error="0"';
						$conn->query($sql);
					}
					$conn->free_result($rs);
					return $this;
				}
			}
			$conn->free_result($rs);
		}
	}

	function send_mails($id)
	{
		$conn = connection::getInstance();

		$sql = 'SELECT filename, base_id, record_id, subdef, error, done
				FROM ftp_export_elements WHERE ftp_export_id="'.$conn->escape_string($id).'"';

		$conn = connection::getInstance();

		$transferts = array();

		$transfert_status = _('task::ftp:Tous les documents ont ete transferes avec succes');

		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				if($row['error'] == '0' && $row['done'] == '1')
				{
					$transferts[] = '<li>'.sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s'), $row["record_id"], $row["filename"]
										, phrasea::sbas_names(phrasea::sbasFromBas($row["base_id"])), phrasea::bas_names($row['base_id']),$row['subdef']).' : '._('Transfert OK').'</li>';
				}
				else
				{
					$transferts[] = '<li>'.sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s'), $row["record_id"], $row["filename"]
										, phrasea::sbas_names(phrasea::sbasFromBas($row["base_id"])), phrasea::bas_names($row['base_id']),$row['subdef']).' : '._('Transfert Annule').'</li>';
					$transfert_status = _('task::ftp:Certains documents n\'ont pas pu etre tranferes');
				}
			}
			$conn->free_result($rs);
		}

		$sql = 'SELECT addr, crash, nbretry, sendermail, mail, text_mail_sender, text_mail_receiver
				FROM ftp_export WHERE id="'.$conn->escape_string($id).'"';

		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				if($row['crash'] >= $row['nbretry'])
					$connection_status = _('Des difficultes ont ete rencontres a la connection au serveur distant');
				else
					$connection_status = _('La connection vers le serveur distant est OK');

				$text_mail_sender = $row['text_mail_sender'];
				$text_mail_receiver = $row['text_mail_receiver'];
				$mail = $row['mail'];
				$sendermail = $row['sendermail'];
				$ftp_server = $row['addr'];
			}
			$conn->free_result($rs);
		}

		$message = "\n<br/>----------------------------------------<br/>\n";
		$message = "<div>".$connection_status."</div>\n";
		$message .= "<div>".$transfert_status."</div>\n";
		$message .= "<div>"._("task::ftp:Details des fichiers"). "</div>\n";

		$message .= "<ul>";
		$message .= implode("\n",$transferts);
		$message .= "</ul>";

		$sender_message = $text_mail_sender.$message;
		$receiver_message = $text_mail_receiver.$message;

		$subject = sprintf(_('task::ftp:Status about your FTP transfert from %1$s to %2$s'),GV_homeTitle,$ftp_server);
		mail::ftp_sent($sendermail, $subject, $sender_message);

		mail::ftp_receive($mail, $receiver_message);
	}

	function logexport($base_id, $record_id, $obj, $ftpLog, $ses_id)
	{
		$collloc2dist = array();

		$lb = phrasea::bases() ;
		foreach($lb["bases"] as $onebase)
		{
			foreach($onebase["collections"] as $oneColl)
			{
				$collloc2dist[$oneColl["base_id"]] = $oneColl["coll_id"];
			}
		}

		$dst_logid = null;

		$conn = connection::getInstance();

		// recuperation de mes logid distant
		$sql2 = "SELECT dist_logid FROM cache WHERE session_id='".$conn->escape_string($ses_id)."'";
		if($rs2 = $conn->query($sql2))
		{
			if( $row2 = $conn->fetch_assoc($rs2) )
				$dst_logid = unserialize($row2["dist_logid"]);
			$conn->free_result($rs2);
		}

		$sbas_id = phrasea::sbasFromBas($base_id) ;

		$conn2 = connection::getInstance($sbas_id);

		if($conn2->isok())
		{

			foreach($obj as $oneObj)
			{

				answer::logEvent($sbas_id,$record_id,'ftp',$ftpLog,'');

				$newid = $conn2->getId("EXPORTS");
				$sql3  = "INSERT INTO exports (id, logid, date, rid, collid, weight, type, shortXml) VALUES " ;
				$sql3 .= "('".$conn2->escape_string($newid)."','".$conn2->escape_string($dst_logid[$sbas_id])."',now() ,'".$conn2->escape_string($record_id)."', '".$conn2->escape_string($collloc2dist[$base_id])."', '".$conn2->escape_string($oneObj["size"])."' , '".$conn2->escape_string($oneObj["name"])."', '".$conn2->escape_string( $oneObj["shortXml"])."')" ;
				$rs2 = $conn2->query($sql3);

			}
		}
	}

}
