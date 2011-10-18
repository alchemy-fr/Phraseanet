<?php
class task_archive extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_('task::archive:Archivage'));
	}

	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	public function graphic2xml($oldxml)
	{
//		global $parm;

		$request = httpRequest::getInstance();

		$parm2 = $request->get_parms(
							  "base_id"
							, "hotfolder"
							, "period"
							, "move_archived"
							, "move_error"
							, "copy_spe"
							, "delfolder"
							, 'cold'
						);
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		if( $dom->loadXML($oldxml) )
		{
			$xmlchanged = false;
			// foreach($parm2 as $pname=>$pvalue)
			foreach(array("str:base_id", "str:hotfolder", "str:period", "boo:move_archived", "boo:move_error", "boo:delfolder", 'boo:copy_spe', 'str:cold') as $pname)
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
					$ns = $dom->documentElement->appendChild($dom->createElement($pname));
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
	// xml2graphic : must fill the graphic form (using js) from xml
	// ====================================================================
	public function xml2graphic($xml, $form)
	{
		if( ($sxml = simplexml_load_string($xml)) )	// in fact XML IS always valid here...
		{
			// ... but we could check for safe values (ex. 0 < period < 3600)
			if( (int)($sxml->period) < 10 )
				$sxml->period = 10;
			elseif( (int)($sxml->period) > 300 )
				$sxml->period = 300;
			if( (int)($sxml->cold) < 5 )
				$sxml->cold = 5;
			elseif( (int)($sxml->cold) > 3600 )
				$sxml->cold = 3600;
?>
		<script type="text/javascript">
			var i;
			var opts = <?php echo $form?>.base_id.options;
			var basefound = 0;
			for(i=1; basefound==0 && i<opts.length; i++)
			{
				if(opts[i].value == "<?php echo p4string::MakeString($sxml->base_id, "form")?>")
					basefound = i;
			}
			opts[basefound].selected = true;
			<?php echo $form?>.hotfolder.value       = "<?php echo p4string::MakeString($sxml->hotfolder, "js", '"')?>";
			<?php echo $form?>.period.value          = "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";
			<?php echo $form?>.cold.value            = "<?php echo p4string::MakeString($sxml->cold, "js", '"')?>";
			<?php echo $form?>.move_archived.checked = <?php echo p4field::isyes($sxml->move_archived) ? "true" : "false"?>;
			<?php echo $form?>.move_error.checked    = <?php echo p4field::isyes($sxml->move_error) ? "true" : "false"?>;
			<?php echo $form?>.delfolder.checked     = <?php echo p4field::isyes($sxml->delfolder) ? "true" : "false"?>;
			<?php echo $form?>.copy_spe.checked      = <?php echo p4field::isyes($sxml->copy_spe) ? "true" : "false"?>;
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
		global $usr_id;

		$colls = databox::getColls();
?>
		<form name="graphicForm" onsubmit="return(false);" method="post">
			<?php echo _('task::archive:archivage sur base/collection/')?> :

			<select onchange="chgxmlpopup(this, 'base_id');" name="base_id">
				<option value="">...</option>
<?php
		$base_id = isset($base_id) ? $base_id : null;

		foreach($colls as $sbas_id=>$c)
		{
			foreach($c['colls'] as $bid=>$asciiname)
			{
				$selected = ($base_id!==null && $base_id==$bid) ? "selected" : "";
				print("\t\t\t\t<option value=\"".$bid."\" $selected>" . p4string::MakeString(($c["viewname"]." / ".$asciiname), "form") . "</option>\n");
			}
		}
?>
			</select>
			<br/>
			<br/>
			<?php echo _('task::_common_:hotfolder')?>
			<input type="text" name="hotfolder" style="width:400px;" onchange="chgxmltxt(this, 'hotfolder');" value=""><br/>
			<br/>
			<?php echo _('task::_common_:periodicite de la tache')?>&nbsp;:&nbsp;
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">&nbsp;<?php echo _('task::_common_:secondes (unite temporelle)')?><br/>
			<br/>
			<?php echo _('task::archive:delai de \'repos\' avant traitement')?>&nbsp;:&nbsp;
			<input type="text" name="cold" style="width:40px;" onchange="chgxmltxt(this, 'cold');" value="">&nbsp;<?php echo _('task::_common_:secondes (unite temporelle)')?><br/>
			<br/>
			<input type="checkbox" name="move_archived" onchange="chgxmlck(this, 'move_archived');">&nbsp;<?php echo _('task::archive:deplacer les fichiers archives dans _archived')?>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="move_error" onchange="chgxmlck(this, 'move_error');">&nbsp;<?php echo _('task::archive:deplacer les fichiers non-archives dans _error')?><br/>
			<br/>
			<input type="checkbox" name="copy_spe" onchange="chgxmlck(this, 'copy_spe');">&nbsp;<?php echo _('task::archive:copier les fichiers \'.phrasea.xml\' et \'.grouping.xml\' dans _archived')?><br/>
			<br/>
			<input type="checkbox" name="delfolder" onchange="chgxmlck(this, 'delfolder');">&nbsp;<?php echo _('task::archive:supprimer les repertoires apres archivage')?><br/>
		</form>
<?php
	}


	// ====================================================================
	// $argt : command line args specifics to this task (optional)
	// ====================================================================
	public $argt = array(
					//		"--truc" => array("set"=>false, "values"=>array(), "usage"=>" : usage du truc")
		 			);

	// ======================================================================================================
	// ===== help() : text displayed if --help (optional)
	// ======================================================================================================
	function help()
	{
		return(_("task::archive:Archiving files found into a 'hotfolder'"));
	}


	// ======================================================================================================
	// ===== run() : le code d'execution de la teche proprement dite
	// ======================================================================================================

	private $mimeTypes = array(	"jpg"=>"image/jpeg", "jpeg"=>"image/jpeg", "pdf"=>"application/pdf" );
	private $hotfiles = array(); // la liste de TOUS les fichiers 'chauds' (en cours d'arrivee ftp ?)
	private $sxTaskSettings = null;	// les settings de la tache en simplexml
	private $connbas = null;		// cnx e la base
	private $running = false;
	private $msg = "";

	// copy settings to task, so it's easier to get later
	private $move_archived = true;	// default value
	private $move_error    = true;	// default value


	function run()
	{
		$this->debug = false;

		if( ($this->sxTaskSettings = simplexml_load_string($this->taskSettings)) )
		{

		}
		else
		{
			// Settings illisibles
			return(false);
		}

		return($this->run2());
	}

	private function run2()
	{
		$ret = '';

		$server_coll_id = null;

		$conn = connection::getInstance();

		$base_id = (int)($this->sxTaskSettings->base_id);
		$sql = 'SELECT s.sbas_id, b.server_coll_id FROM bas b INNER JOIN sbas s ON s.sbas_id=b.sbas_id WHERE b.base_id="'.$base_id.'"' ;

		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$this->connbas = connection::getInstance($row['sbas_id']);
				$server_coll_id = $row["server_coll_id"];
				try
				{
          if(!$this->connbas)
            throw new Exception('Unable to get connection');
					$this->connbas->isok() || generalException::get('no db');

					// on liste les collections disponibles
					$this->TColls = array();
					$sql = 'SELECT coll_id FROM coll';
					$rsbas = $this->connbas->query($sql);
					while($rowbas = $this->connbas->fetch_assoc($rsbas))
						$this->TColls['c'.$rowbas['coll_id']] = $rowbas;
					$this->connbas->free_result($rsbas);

					// on lit les prefs de cette collection
					$sql = "SELECT uid AS maxcoll, value AS baseprefs, prefs AS collprefs FROM uids, pref, coll WHERE uids.name='COLL' AND pref.prop='structure' AND  coll_id='" . $server_coll_id ."'";
					$rsbas = $this->connbas->query($sql);
					$rowbas = $this->connbas->fetch_assoc($rsbas);
					$this->connbas->free_result($rsbas);

					$rowbas || generalException::get('no prefs for collection '.$server_coll_id);
				}
				catch(Exception $e)
				{
					print($e->getMessage());
					return('tostop');
				}
			}
			$conn->free_result($rs);
		}

		// ici :
		// conn est une connexion e application server du site
		// rowtask : record de la teche en cours
		// connbas est une connexion e la base distante
		// rowbas : record contenant les prefs de base et collection

		// on a les prefs de coll directement dans la session

		$this->running = true;
		$this->tmask = array();	// les masques de fichiers acceptes par cette tache
		$this->tmaskgrp = array();
		$this->period = 60;
		$this->cold = 60;


		if( ($this->sxBasePrefs = simplexml_load_string($rowbas["baseprefs"])) )
		{

			$this->sxBasePrefs["id"] = $base_id;		// info utile

			$do_it = true;

			$this->period = (int)($this->sxTaskSettings->period);
			if($this->period <= 0 || $this->period >= 60*60)
				$this->period = 60;

			$this->cold = (int)($this->sxTaskSettings->cold);
			if($this->cold <= 0 || $this->cold >= 60*60)
				$this->cold = 60;

			// si on doit copier le doc dans la base, on verifie que le dossier existe
			$copyhd = true;
			$pathhd = (string)($this->sxBasePrefs->path);

			if($copyhd && $pathhd)
			{
				if(!is_dir($pathhd))
					p4::fullmkdir($pathhd);
				if(!is_dir($pathhd))
				{
					$this->log(sprintf(_('task::archive:Can\'t create or go to folder \'%s\''), $pathhd));
					$this->running = false;
				}
			}

			// on charge les masques de fichiers
			if($this->sxTaskSettings->files && $this->sxTaskSettings->files->file)
			{
				foreach($this->sxTaskSettings->files->file as $ft)
					$this->tmask[] = array("mask"=>(string)($ft["mask"]), "caption"=>(string)($ft["caption"]), "accept"=>(string)($ft["accept"]));
			}
			if($this->sxTaskSettings->files && $this->sxTaskSettings->files->grouping)
			{
				foreach($this->sxTaskSettings->files->grouping as $ft)
					$this->tmaskgrp[] = array("mask"=>(string)($ft["mask"]), "caption"=>(string)($ft["caption"]),"representation"=>(string)($ft["representation"]), "accept"=>(string)($ft["accept"]));
			}
			if(count($this->tmask)==0)
			{
				// pas de masque defini : on accepte tout
				$this->tmask[] = array("mask"=>".*", "caption"=>"", "accept"=>"");
			}

			// ici la teche tourne tant qu'elle est active
                        $loop = 0;
			while($this->running)
			{
				if(!$conn || !$conn->ping())
				{
					$this->log(("Warning : abox connection lost, restarting in 10 min."));
					sleep(60*10);
					$this->running = false;
					return('');
				}

				if(!$this->connbas->ping())
				{
					$this->log(("dbox connection lost, restarting in 10 min."));
					sleep(60*10);
					$this->running = false;
					return('');
				}

				$path_in = (string)($this->sxTaskSettings->hotfolder);
				if(!@is_dir( $path_in ))
				{
					$this->log(sprintf(('missing hotfolder \'%s\', restarting in 10 min.'), $path_in));
					sleep(60*10);
					$this->running = false;
					return('');
				}


				// printf("====================== LOOP ====================== \n");

				$sql = "UPDATE task2 SET last_exec_time=NOW() WHERE task_id=" . $this->taskid ;
				$conn->query($sql);


				$sql = "SELECT * FROM task2 WHERE task_id=" . $this->taskid ;
				if($rs = $conn->query($sql))
				{
					$row = $conn->fetch_assoc($rs);
					$conn->free_result($rs);

					if($row)
					{
						if($row['status'] == 'tostop')
						{
							$ret = 'stopped';
							$this->running = false;
						}
						else
						{

							if( $this->sxTaskSettings = simplexml_load_string($row['settings']) )
							{
								// copy settings to task, so it's easier to get later
								$this->move_archived = p4field::isyes($this->sxTaskSettings->move_archived);
								$this->move_error    = p4field::isyes($this->sxTaskSettings->move_error);

								$period = (int)($this->sxTaskSettings->period);
								if($period <= 0 || $period >= 60*60)
									$period = 60;
								$cold = (int)($this->sxTaskSettings->cold);
								if($cold <= 0 || $cold >= 60*60)
									$cold = 60;
							}
							else
							{
								$period = 60;
								$cold = 60;
							}

							$duration = time();
							$r = $this->archiveHotFolder($server_coll_id);

                                                        if($loop > 10)
                                                          $r = 'MAXLOOP';

							switch($r)
							{
								case 'WAIT':
									$ret = 'stopped';
									$this->running = false;
									break;
								case 'BAD':
									$ret = 'stopped';
									$this->running = false;
									break;
								case 'NORECSTODO':
									$duration = time() - $duration;
									if($duration < ($period+$cold))
									{
										$conn->close();
										sleep(($period+$cold) - $duration);
										unset($conn);
										$conn = connection::getInstance();
									}
									break;
								case 'MAXRECSDONE':
								case 'MAXMEMORY':
								case 'LOOP':
								case 'MAXLOOP':
									if($row['status'] == 'started')
									{
										$ret = 'torestart';
										$this->running = false;
									}
									break;
								default:
									if($row['status'] == 'started')
									{
										$ret = 'stopped';
										$this->running = false;
									}
									break;
							}
						}
					}
					else
					{
						$this->running = false;
					}
				}
				else
				{
					$this->running = false;
				}
                                $loop++;
			}
		}

		return($ret);
	}

	function archiveHotFolder($server_coll_id)
	{
		// $this->filesToIgnore = array('.', '..', 'Thumbs.db', '');

		clearstatcache();

		$conn = connection::getInstance();

		$path_in = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
		if(!@is_file($path_in . "/.phrasea.xml"))
		{
			$this->log(sprintf(('NO .phrasea.xml AT ROOT v2 \'%s\' !'), $path_in));
			return('WAIT');
		}

		$path_archived = $path_error = null;
		if($this->move_archived)
		{
			$path_archived = $path_in . '_archived';
			@mkdir($path_archived, 0755, true);
			if(!file_exists($path_archived))
			{
				$this->log(sprintf(('Can\'t create folder \'%s\' !'), $path_archived));
				return('BAD');
			}
		}
		if($this->move_error)
		{
			$path_error = $path_in . '_error';
			@mkdir($path_error, 0755, true);
			if(!file_exists($path_error))
			{
				$this->log(sprintf(('archive:Can\'t create folder \'%s\' !'), $path_error));
				return('BAD');
			}
		}

		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$root = $dom->appendChild($dom->createElement('root'));


		$this->movedFiles = 0;
		$this->archivedFiles = 0;
		$this->path_in = $path_in;

		$nnew = $this->listFilesPhase1($dom, $root, $path_in, $server_coll_id);

		if($this->debug)
			$this->log("=========== listFilesPhase1 ========== (returned ".$nnew.")\n" . $dom->saveXML());

		$cold = (int)($this->sxTaskSettings->cold);
		if($cold <= 0 || $cold >= 60*60)
			$cold = 60;

		$sql = "SELECT status FROM task2 WHERE task_id=" . $this->taskid ;
		while($cold > 0)
		{
			if($rs = $conn->query($sql))
			{
				$row = $conn->fetch_assoc($rs);
				$conn->free_result($rs);
				if($row && $row['status'] == 'tostop')
					return('NORECSTODO');
			}
			sleep(2);
			$cold -= 2;
		}


		$this->listFilesPhase2($dom, $root, $path_in);
		if($this->debug)
			$this->log("=========== listFilesPhase2 ========== : \n" . $dom->saveXML());



		$this->makePairs($dom, $root, $path_in, $path_archived, $path_error);
		if($this->debug)
			$this->log("=========== makePairs ========== : \n" . $dom->saveXML());



		$r = $this->removeBadGroups($dom, $root, $path_in, $path_archived, $path_error);
		if($this->debug)
			$this->log("=========== removeBadGroups ========== (returned ".($r?'true':'false').") : \n" . $dom->saveXML());



		$this->archive($dom, $root, $path_in, $path_archived, $path_error);
		if($this->debug)
			$this->log("=========== archive ========== : \n" . $dom->saveXML());



		$this->bubbleResults($dom, $root, $path_in);
		if($this->debug)
			$this->log("=========== bubbleResults ========== : \n" . $dom->saveXML());



		$r = $this->moveFiles($dom, $root, $path_in, $path_archived, $path_error);
		if($this->debug)
			$this->log("=========== moveFiles ========== (returned ".($r?'true':'false').") : \n" . $dom->saveXML());

		if($this->movedFiles)
		{
			// something happened : a least one file has moved
			return('MAXRECSDONE');
		}
		elseif(memory_get_usage()>>20 > 15)
		{
			return('MAXMEMORY');
		}
		else
		{
			return('NORECSTODO');
		}


//print($dom->saveXML());

// unset($dom);

// die();

	}


	function isIgnoredFile($f)
	{
		$f =  strtolower($f);
		return(($f[0]=='.' &&  $f != '.phrasea.xml' && $f != '.grouping.xml') || $f=='thumbs.db' || $f=='par-system');
		// return(substr($f,0,1)=='.' || $f=='Thumbs.db' || $f=='par-SYSTEM');
	}

	/**
	 * check if the file matches any mask, and flag the 'caption' file if found
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $grp_rid
	 */

	function checkMatch($dom, $node)
	{
		$file = $node->getAttribute('name');

		foreach($this->tmask as $mask)
		{
			$preg_mask = '/' . $mask['mask'] . '/';
			if(preg_match($preg_mask, $file))
			{
				if($mask['caption'])
				{
					// caption in a linked file ?
					$captionFileName = @preg_replace($preg_mask, $mask['caption'], $file);
					$xpath = new DOMXPath($dom);
					$dnl = $xpath->query('./file[@name="'.$captionFileName.'"]', $node->parentNode);
					if($dnl->length == 1)
					{
						// the caption file exists
						$node->setAttribute('match', $captionFileName);
						$dnl->item(0)->setAttribute('match', '*');
					}
					else
					{
						// the caption file is missing
						$node->setAttribute('match', '?');
					}
				}
				else
				{
					// self-described
					$node->setAttribute('match', '.');
				}

				// first match is ok
				break;
			}
		}
	}


	/**
	 * Phase 1 :
	 *  list every file, all 'hot'
	 *  read .phrasea.xml files
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $server_coll_id
	 */

	function listFilesPhase1($dom, $node, $path, $server_coll_id)
	{
		// $this->traceRam();
		$nnew = 0;

		try
		{
			$listFolder = new CListFolder($path, GV_ArchiveSorted);

			if( ($sxDotPhrasea = @simplexml_load_file($path.'/.phrasea.xml')) )
			{
				// on gere le magicfile
				if( ($magicfile = trim((string)($sxDotPhrasea->magicfile))) != '')
				{
					$magicmethod = strtoupper($sxDotPhrasea->magicfile['method']);
					if($magicmethod=='LOCK' && file_exists($path.'/'.$magicfile))
						return;
					elseif($magicmethod=='UNLOCK' && !file_exists($path.'/'.$magicfile))
						return;
				}

				// on gere le changement de collection
				if( ($new_cid = $sxDotPhrasea['collection']) != '' )
				{
					//printf("new_cid=%s\n", $new_cid);
					//var_dump($this->TColls);
					if(isset($this->TColls['c'.$new_cid]))
					{
						$server_coll_id = $new_cid;
					}
					else
					{
						$this->log(sprintf(('Unknown coll_id (%1$d) in "%2$s"'), (int)$new_cid, $path.'/.phrasea.xml'));
						$server_coll_id = -1;
					}
				}
				$node->setAttribute('pxml', '1');
			}

			$iloop = 0;
			while( ($file = $listFolder->read()) !== NULL )
			{
				if($this->isIgnoredFile($file))
					continue;

				if( ($iloop++ % 100) == 0)
					usleep(1000);

				if(is_dir($path.'/'.$file))
				{
					$n = $node->appendChild($dom->createElement('file'));
					$n->setAttribute('isdir', '1');
					$n->setAttribute('name', $file);

					$nnew += $this->listFilesPhase1($dom, $n, $path.'/'.$file, $server_coll_id);
				}
				else
				{
					$n = $node->appendChild($dom->createElement('file'));
					$n->setAttribute('name', $file);
					$stat = stat($path.'/'.$file);
					foreach(array("size", "ctime", "mtime") as $k)
						$n->setAttribute($k, $stat[$k]);
					$nnew++;
				}
				$n->setAttribute('cid', $server_coll_id);

				$n->setAttribute('temperature', 'hot');
			}
		}
		catch(Exception $e)
		{

		}

		return($nnew);
	}


	/**
	 * Phase 2 :
	 *	list again and flag dead files as 'cold'
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 */
	function listFilesPhase2($dom, $node, $path, $depth=0)
	{
		static $iloop = 0;
		if($depth == 0)
			$iloop = 0;

		$nnew = 0;

		try
		{
			$listFolder = new CListFolder($path, GV_ArchiveSorted);

			$xp = new DOMXPath($dom);

			while( ($file = $listFolder->read()) !== NULL )
			{
				if($this->isIgnoredFile($file))
					continue;

				if( ($iloop++ % 100) == 0)
					usleep(500);

				$dnl = @$xp->query('./file[@name="'.$file.'"]', $node);
// if(!$dnl)
// printf("%s \n", './file[@name="'.$file.'"]');
				if($dnl && $dnl->length == 0)
				{
					if(is_dir($path.'/'.$file))
					{
						$n = $node->appendChild($dom->createElement('file'));
						$n->setAttribute('isdir', '1');
						$n->setAttribute('name', $file);

						$nnew += $this->listFilesPhase2($dom, $n, $path.'/'.$file, $depth+1);
					}
					else
					{
						$n = $node->appendChild($dom->createElement('file'));
						$n->setAttribute('name', $file);
//						$stat = stat($path.'/'.$file);
//						foreach(array("size", "ctime", "mtime") as $k)
//							$n->setAttribute($k, $stat[$k]);
						$nnew++;
					}
//					$n->setAttribute('cid', $server_coll_id);
//					$n->setAttribute('temperature', 'hot');
					$this->setBranchHot($dom, $n);
				}
				elseif($dnl && $dnl->length == 1)
				{
					$dnl->item(0)->setAttribute('temperature', 'cold');
					// $dnl->item(0)->removeAttribute('hot');
					if(is_dir($path.'/'.$file))
					{
						$this->listFilesPhase2($dom, $dnl->item(0), $path.'/'.$file, $depth+1);
					}
					else
					{
						$stat = stat($path.'/'.$file);
						foreach(array("size", "ctime", "mtime") as $k)
						{
							if($dnl->item(0)->getAttribute($k) != $stat[$k])
							{
								$this->setBranchHot($dom, $dnl->item(0));
								break;
							}
						}
					}
				}
			}
		}
		catch(Exception $e)
		{

		}
	}

	/**
	 * makePairs :
	 *  flag files to be archived (make pairs with linked caption when needed)
	 *  flag grp folders and their linked files (caption, representation)
	 *  declare uncomplete grp as error
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 */

	function makePairs($dom, $node, $path, $path_archived, $path_error, $inGrp=false, $depth=0)
	{
		static $iloop = 0;
		if($depth == 0)
			$iloop = 0;

		if($depth==0 && ($node->getAttribute('temperature')=='hot' || $node->getAttribute('cid')=='-1'))
			return;

		$xpath = new DOMXPath($dom);	// useful

		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if( ($iloop++ % 100) == 0)
				usleep(1000);

			// make xml lighter (free ram)
			foreach(array("size", "ctime", "mtime") as $k)
				$n->removeAttribute($k);

			if($n->getAttribute('temperature')=='hot' || $n->getAttribute('cid')=='-1')
				continue;

			$name = $n->getAttribute('name');
			if($n->getAttribute('isdir')=='1')
			{
				if( ($grpSettings = $this->getGrpSettings($name)) !== FALSE)	// get 'caption', 'representation'
				{
					// this is a grp folder, we check it

					$dnl = $xpath->query('./file[@name=".grouping.xml"]', $n);
					if($dnl->length == 1)
					{
						// this group is old (don't care about any linked files), just flag it
						$n->setAttribute('grp', 'tocomplete');
						$dnl->item(0)->setAttribute('match', '*');
						// recurse only if group is ok
						$this->makePairs($dom, $n, $path.'/'.$name, true, $depth+1);
					}
					else
					{
						// this group in new (to be created)
						// do we need one (or both) linked file ? (caption or representation)
						$err = false;
						$flink = array('caption'=>null, 'representation'=>null);

						foreach($flink as $linkName=>$v)
						{
							if(isset($grpSettings[$linkName]) && $grpSettings[$linkName]!='')
							{
								// we need this linked file, calc his real name
								$f = preg_replace('/'.$grpSettings['mask'].'/i', $grpSettings[$linkName], $name);

								$dnl = $xpath->query('./file[@name="'.$f.'"]', $node);
								if($dnl->length == 1)
								{
									$flink[$linkName] = $dnl->item(0);		// it's here
								}
								else
								{
									$this->log(sprintf(('missing linked file \'%1$s\' to group \'%2$s\''), $f, $name));
									$err = true;								// missing -> error
								}
							}
						}

						if(!$err)
						{
							// the group is ok, flag it ...
							$n->setAttribute('grp', 'tocreate');

							// ... as the existing linked file(s) ...
							foreach($flink as $linkName=>$v)
							{
								if($v)		// this linked file exists
								{
									// $v->setAttribute('grp', '1');
									$v->setAttribute('match', '*');
									$n->setAttribute('grp_'.$linkName, $v->getAttribute('name'));
								}
							}
							// recurse only if group is ok
							$this->makePairs($dom, $n, $path.'/'.$name
																, $path_archived.'/'.$name
																, $path_error.'/'.$name
																, true, $depth+1);
						}
						else
						{
							// something is missing, the whole group goes error, ...
							$n->setAttribute('grp', 'todelete');

							$this->setAllChildren($dom, $n, array('error'=>'1'));

							// bubble to the top
							for($nn=$n; $nn && $nn->nodeType==XML_ELEMENT_NODE; $nn=$nn->parentNode)
								$nn->setAttribute('error', '1');

							// ... as the existing linked file(s) ...
							foreach($flink as $linkName=>$v)
							{
								if($v)		// this linked file exists, it goes error also
									$v->setAttribute('error', '1');
							}
						}
					}
				}
				else
				{
					// not a grp folder, recurse
					$this->makePairs($dom, $n, $path.'/'.$name
												, $path_archived.'/'.$name
												, $path_error.'/'.$name
												, $inGrp, $depth+1);
				}
			}
			else
			{
				// this is a file
				if(!$n->getAttribute('match'))	// because match can be set before
				{
					if($name == '.phrasea.xml')
						$n->setAttribute('match', '*');		// special file(s) always ok
					else
						$this->checkMatch($dom, $n);
				}
			}
		}

		// scan again for unmatched files
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if(!$n->getAttribute('isdir')=='1' && !$n->getAttribute('match'))
			{
				// still no match, now it's an error (bubble to the top)
				for($nn=$n; $nn && $nn->nodeType==XML_ELEMENT_NODE; $nn=$nn->parentNode)
					$nn->setAttribute('error', '1');
			}
		}
	}


	/**
	 * removeBadGroups :
	 * 	move files to archived or error dir
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $path_archived
	 * @param unknown_type $path_error
	 */
	function removeBadGroups($dom, $node, $path, $path_archived, $path_error, $depth=0)
	{
		static $iloop=0;
		if($depth == 0)
			$iloop = 0;

		$ret = false;

		if($depth==0 && $node->getAttribute('temperature')=='hot')	// if root of hotfolder if hot, die...
			return($ret);

//printf("%s : \n", __LINE__);
		$nodesToDel = array();
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if( ($iloop++ % 20) == 0)
				usleep(1000);

			if($n->getAttribute('temperature')=='hot')	// do not move hotfiles
				continue;

//printf("%s : \n", __LINE__);
			$name = $n->getAttribute('name');
//printf("----------------\n file %s : \n ", $path.'/'.$name);
//$fp = fopen('php://stdin', 'r');
//fgets($fp);
//fclose($fp);

			if($n->getAttribute('isdir'))
			{
				// a dir
//				if($n->getAttribute('error') && $this->move_error)
//				{
//					@mkdir($path_error . '/' . $name);
//				}

				$ret |= $this->removeBadGroups($dom, $n, $path.'/'.$name
													, $path_archived.'/'.$name
													, $path_error.'/'.$name
													, $depth+1);
				if($n->getAttribute('grp')=='todelete')
				{
					$nodesToDel[] = $n;
					@unlink($path.'/'.$name);
				}
			}
			else
			{
				// a file
				if($n->getAttribute('error'))
				{
// printf("%s : \n", __LINE__);
					if($this->move_error)
					{
						$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
						$subpath  = substr($path, strlen($rootpath));
						$this->log(sprintf(('copy \'%s\' to \'error\''), $subpath.'/'.$name));

						@mkdir($path_error, 0755, true);
						@copy($path.'/'.$name, $path_error.'/'.$name);
					}

					$nodesToDel[] = $n;
					@unlink($path.'/'.$name);

					$this->movedFiles++;
				}
			}
		}

		foreach($nodesToDel as $n)
			$n->parentNode->removeChild($n);
	}






	/**
	 * archive :
	 *   archive files
	 *   do special work on grp folders
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 */

	function archive($dom, $node, $path, $path_archived, $path_error, $depth=0)
	{
		static $iloop = 0;
		if($depth == 0)
			$iloop = 0;

		if($node->getAttribute('temperature')=='hot')
			return;

		$nodesToDel = array();
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if( ($iloop++ % 20) == 0)
				usleep(1000);

			if($n->getAttribute('temperature')=='hot')
				continue;

			if($n->getAttribute('cid')=='-1')
			{
				$n->setAttribute('error', '1');
				continue;
			}

			if($n->getAttribute('isdir')=='1')
			{
				if($n->getAttribute('grp'))
				{
					// a grp folder : special work
					$this->ArchiveGrp($dom, $n, $path, $path_archived, $path_error, $nodesToDel);
				}
				else
				{
					// ...normal subfolder : recurse
					$name = $n->getAttribute('name');
					$this->archive($dom, $n, $path.'/'.$name
											, $path_archived.'/'.$name
											, $path_error.'/'.$name
											, $depth+1);
				}
			}
			else
			{
				// a file
				$this->archiveFile($dom, $n, $path, $path_archived, $path_error, $nodesToDel, 0);	// 0 = no grp
			}
		}
// printf("========== %s === %s ====== \n", __LINE__, var_export($nodesToDel, true));
		foreach($nodesToDel as $n)
			$n->parentNode->removeChild($n);

		// at the end of recursion, restore the magic file (create or delete it)
		if( ($magicfile = $node->getAttribute('magicfile')) != '')
		{
			$magicmethod = $node->getAttribute('magicmethod');
			if($magicmethod=='LOCK')
				file_put_contents($path.'/'.$magicfile, '');
			elseif($magicmethod=='UNLOCK')
				unlink($path.'/'.$magicfile);
		}
	}



	/**
	 * bubbleResults :
	 *   bubble result attributes ('keep', 'archived', 'error') up to top,
	 *   to help creation of result folders and cleaning of the hotfolder
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @return unknown
	 */
	function bubbleResults($dom, $node, $path, $depth=0)
	{
		static $iloop=0;
		if($depth == 0)
			$iloop = 0;

		if($node->getAttribute('temperature')=='hot')
			return;

		$ret = 0;
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if( ($iloop++ % 20) == 0)
				usleep(1000);

			if($n->getAttribute('name') == '.phrasea.xml' || $n->getAttribute('name') == '.grouping.xml')
			{
				// special files stay in place AND are copied into 'archived'
				$n->setAttribute('keep', '1');
				if(p4field::isyes($this->sxTaskSettings->copy_spe))
					$n->setAttribute('archived', '1');
			}
//			else
//			{
//				if(!$n->getAttribute('isdir') && ($n->getAttribute('temperature') == 'cold' && !$n->getAttribute('archived')))
//				{
//					$n->setAttribute('error', '1');
//				}
//			}
			if($n->getAttribute('keep') == '1')
				$ret |= 1;
			if($n->getAttribute('archived') == '1')
				$ret |= 2;
			if($n->getAttribute('error') == '1')
				$ret |= 4;
			if($n->getAttribute('isdir') == '1')
				$ret |= $this->bubbleResults($dom, $n, $path.'/'.$n->getAttribute('name'), $depth+1);
		}
		if($ret & 1)
			$node->setAttribute('keep', '1');
		if($ret & 2)
			$node->setAttribute('archived', '1');
		if($ret & 4)
			$node->setAttribute('error', '1');
		return($ret);
	}



	/**
	 * Phase 5 :
	 * 	move files to archived or error dir
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $path_archived
	 * @param unknown_type $path_error
	 */
	function moveFiles($dom, $node, $path, $path_archived, $path_error, $depth=0)
	{
		static $iloop=0;
		if($depth == 0)
			$iloop = 0;

		$ret = false;

		if($depth==0 && $node->getAttribute('temperature')=='hot')	// if root of hotfolder if hot, die...
			return($ret);

//printf("%s : \n", __LINE__);
		$nodesToDel = array();
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if( ($iloop++ % 20) == 0)
				usleep(1000);

			if($n->getAttribute('temperature')=='hot')	// do not move hotfiles
				continue;

//printf("%s : \n", __LINE__);
			$name = $n->getAttribute('name');
//printf("----------------\n file %s : \n ", $path.'/'.$name);
//$fp = fopen('php://stdin', 'r');
//fgets($fp);
//fclose($fp);

			if($n->getAttribute('isdir'))
			{
// printf("%s : ('%s', '%s')\n", __LINE__, $path_archived, $path_error);
//				$new_path_archived = $new_path_error = null;
//				if($n->getAttribute('archived') && $this->move_archived)
//				{
//					@mkdir($new_path_archived = ($path_archived . '/' . $name));
//					if(!is_dir($new_path_archived))
//					{
//						$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
//						$subpath  = substr($new_path_archived, strlen($rootpath));
//
//						$this->log("error subfolder '".$subpath."' does not exits");
//					}
//				}
//
//				if($n->getAttribute('error') && $this->move_error)
//				{
//					@mkdir($new_path_error = ($path_error . '/' . $name));
//					if(!is_dir($new_path_error))
//					{
//						$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
//						$subpath  = substr($new_path_error, strlen($rootpath));
//
//						$this->log("error subfolder '".$subpath."' does not exists");
//					}
//				}

				$ret |= $this->moveFiles($dom, $n, $path.'/'.$name
													, $path_archived.'/'.$name
													, $path_error.'/'.$name
													, $depth+1);

				if(!$n->firstChild)
					$nodesToDel[] = $n;
// ----- JY 20100318 : DO NOT DELETE EMPTY FOLDERS ANYMORE, AS THEY MAY DISAPEAR TOO SOON -----
//				if(!$n->getAttribute('keep'))
//					@rmdir($path.'/'.$name);
// --------------------------------------------------------------------------------------------
			}
			else
			{
//printf("%s : \n", __LINE__);
				$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
				$subpath  = substr($path, strlen($rootpath));

				if($n->getAttribute('archived') && $this->move_archived)
				{
					$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$name));

					@mkdir($path_archived, 0755, true);
					@copy($path.'/'.$name, $path_archived.'/'.$name);
					if(!$n->getAttribute('keep'))	// do not count copy of special files as a real event
					{
						$nodesToDel[] = $n;
						$ret = true;
					}
				}

				if($n->getAttribute('error') && $this->move_error)
				{
// printf("%s : \n", __LINE__);
					$this->log(sprintf(('copy \'%s\' to \'error\''), $subpath.'/'.$name));

					@mkdir($path_error, 0755, true);
					@copy($path.'/'.$name, $path_error.'/'.$name);
					if(!$n->getAttribute('keep'))	// do not count copy of special files as a real event
					{
						$nodesToDel[] = $n;
						$ret = true;
					}
//printf("-> copy to %s \n", $path_error.'/'.$name);
				}

				if(!$n->getAttribute('keep'))
				{
//printf("-> unlink %s \n", $path.'/'.$name);
					$this->log(sprintf(('delete \'%s\''), $subpath.'/'.$name));
					if(@unlink($path.'/'.$name))
					{
			//			$n->parentNode->removeChild($n);
						$this->movedFiles++;
					}
					else
					{
						$this->log(sprintf(("Warning : can't delete '%s' from hotfolder, task is stopped"),  $subpath.'/'.$name));
					}
				}
			}
		}

		foreach($nodesToDel as $n)
			$n->parentNode->removeChild($n);

		return($ret);
	}





	function setBranchHot($dom, $node)
	{
		for($n=$node; $n; $n=$n->parentNode)
		{
			if($n->nodeType == XML_ELEMENT_NODE)
			{
				$n->setAttribute('temperature', 'hot');
				if($n->hasAttribute('pxml'))
					break;
			}
		}
	}

	/**
	 * special work for a grp folder :
	 *   create the grp if needed
	 *   archive files
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $parentPath
	 */

	function archiveGrp($dom, $node, $path, $path_archived, $path_error, &$nodesToDel)
	{
		$xpath = new DOMXPath($dom);	// useful

		$node->setAttribute('keep', '1');		// grp folders stay in place
		$grpFolder = $node->getAttribute('name');

		$groupingFile = $path.'/'.$grpFolder.'/.grouping.xml';

		if($node->getAttribute('grp') == 'tocreate')
		{
			$representationFileName = null;
			$representationFileNode = null;
			$captionFileName = null;
			$captionFileNode = null;
			$cid = $node->getAttribute('cid');
			$genericdoc = null;

			$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
			$subpath  = substr($path, strlen($rootpath));

			$this->log(sprintf(('created story \'%s\''), $subpath.'/'.$grpFolder));

			// if the .grp does not have a representative doc, let's use a generic file
			if(!($rep = $node->getAttribute('grp_representation')))
			{
				copy(p4string::addEndSlash(GV_RootPath).'www/skins/icons/substitution/regroup_doc.png', $genericdoc=($path.'/group.jpg'));
				$representationFileName = 'group.jpg';
				$this->log((' (no representation file)'));
			}
			else
			{
				$dnl = $xpath->query('./file[@name="'.$rep.'"]', $node->parentNode);
				$representationFileNode = $dnl->item(0);
				$representationFileName = $rep;
				$node->removeAttribute('grp_representation');
				$this->log(sprintf(('representation from \'%s\''), $representationFileName));
			}

			if( ($cap = $node->getAttribute('grp_caption')) )
			{
				$dnl = $xpath->query('./file[@name="'.$cap.'"]', $node->parentNode);
				$captionFileNode = $dnl->item(0);
				$captionFileName = $cap;
				$node->removeAttribute('grp_caption');
				$this->log(sprintf(('caption from \'%s\''), $captionFileName));
			}

			$rid = $this->connbas->getId("RECORD", 1);
			$mimeExt = giveMimeExt($path.'/'.$representationFileName);
			$propfile = array(
								'recordid' => $rid,
								'extension' => $mimeExt['ext'],
								'mime' => $mimeExt['mime'],
								'size' => sprintf('%u', filesize($path.'/'.$representationFileName)),
								'subpath' => $subpath,
								'parentdirectory' => '',
								'hotfolderfile' => $path.'/'.$representationFileName,
								'hotfoldercaptionfile' => $path.'/'.$captionFileName,
								'pathhd' => '',
								'file' => '',
								'originalname' => $representationFileName,
								'inbase' => 0,
								'type' => giveMeDocType($mimeExt['mime'])
							);
			$pi = pathinfo($subpath);
			$propfile['parentdirectory'] = $pi["basename"];

			$meta = get_xml($this->sxBasePrefs, $propfile);

			$stat0 = $stat1= "0";
			if($this->sxBasePrefs->status)
				$stat0 = (string)($this->sxBasePrefs->status);
			if($this->sxTaskSettings->status)
				$stat1 = (string)($this->sxTaskSettings->status);

			if(!$stat0)
				$stat0 = '0';
			if(!$stat1)
				$stat1 = '0';

			// ... flag the record as 'to reindex' (status-bits 0 and 1 to 0) AND UNLOCK IT (status-bit 2 to 1)
			// ... and flag it for subdef creation (jeton bit 0 to 1)
			$fl = 'coll_id, record_id, parent_record_id, status, jeton, moddate, credate, xml, type, sha256';
			$vl = $cid . ', ' .$rid . ', ' .$rid . ', (((' . $stat0 . ' | ' . $stat1 . ') & ~0x0F) | 0x0C), '.JETON_MAKE_SUBDEF.', NOW(), NOW(), \'' . $this->connbas->escape_string($meta['xml']->saveXML()) . '\', \'' . $this->connbas->escape_string($propfile['type']) . '\', \''.hash_file('sha256',$propfile["hotfolderfile"]).'\'';
			$sql = 'INSERT INTO record ('.$fl.') VALUES ('.$vl.')';

			$this->connbas->query($sql);

			$copyhd = true;

			$documentisok = false;

			$this->log(sprintf((' (recordId %s)'), $rid));

			if($copyhd)
			{
				// archiving with copy into base
				$pathhd = trim((string)($this->sxBasePrefs->path));
				$pathhd = p4string::addEndSlash(p4::dispatch($pathhd));
				if(!is_dir($pathhd))
					p4::fullmkdir($pathhd);

				$newname = $rid . "_document.".$propfile["extension"];
				if(copy($propfile["hotfolderfile"], $pathhd . $newname))
				{
					$propfile['pathhd'] = $pathhd;
					$propfile['file'] = $newname;
					$propfile['inbase'] = 1;

					$fl  = 'record_id';		$vl  = $rid;
					$fl .= ', name';		$vl .= ", 'document'";
					$fl .= ', path';		$vl .= ", '" . $this->connbas->escape_string($propfile['pathhd']) . "'";
					$fl .= ', file';		$vl .= ", '" . $this->connbas->escape_string($propfile['file']) . "'";
					$fl .= ', baseurl';		$vl .= ", ''";
					$fl .= ', inbase';		$vl .= ", '" . $propfile['inbase'] . "'";
					if(isset($propfile['width']))
					{
						$fl .= ', width';		$vl .= ", '" . $this->connbas->escape_string($propfile['width']) . "'";
					}
					if(isset($propfile['height']))
					{
						$fl .= ', height';		$vl .= ", '" . $this->connbas->escape_string($propfile['height']) . "'";
					}
					$fl .= ', mime';		$vl .= ", '" . $this->connbas->escape_string($propfile['mime']) . "'";
					$fl .= ', size';		$vl .= ", '" . $this->connbas->escape_string($propfile['size']) . "'";
					$fl .= ', dispatched';	$vl .= ", '1'";

					$sql = "INSERT INTO subdef (".$fl.") VALUES (".$vl.")";

					$this->connbas->query($sql);
				}

				$this->archivedFiles++;
			}
			else
			{
				// archiving 'in place'
			}

			if($genericdoc)
				unlink($genericdoc);


			// ok here
			//	, create a .grouping.xml in the hotfolder
			file_put_contents($groupingFile, '<?xml version="1.0" encoding="ISO-8859-1" ?><record grouping="'.$rid.'" />');
			$n = $node->appendChild($dom->createElement('file'));
			$n->setAttribute('name', '.grouping.xml');
			$n->setAttribute('temperature', 'cold');
			$n->setAttribute('grp', '1');
//			$n->setAttribute('archived', '1');
			$n->setAttribute('match', '*');
			if($this->move_archived)
			{
				$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$grpFolder.'/.grouping.xml'));
				@mkdir($path_archived.'/'.$grpFolder, 0755, true);
				@copy($path.'/'.$grpFolder.'/.grouping.xml', $path_archived.'/'.$grpFolder.'/.grouping.xml');
			}
			//

			if($captionFileNode)
			{
				$captionFileNode->setAttribute('archived', '1');
				if($this->move_archived)
				{
					$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$captionFileName));

					if(!is_dir($path_archived))
						@mkdir($path_archived, 0755, true);
					@copy($path.'/'.$captionFileName, $path_archived.'/'.$captionFileName);
				}
				@unlink($path.'/'.$captionFileName);
				$nodesToDel[] = $captionFileNode;

				$this->movedFiles++;
			}
			if($representationFileNode)
			{
				$representationFileNode->setAttribute('archived', '1');
				if($this->move_archived)
				{
					$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$representationFileName));

					if(!is_dir($path_archived))
						@mkdir($path_archived, 0755, true);
					@copy($path.'/'.$representationFileName, $path_archived.'/'.$representationFileName);
				}
				@unlink($path.'/'.$representationFileName);
				$nodesToDel[] = $representationFileNode;

				$this->movedFiles++;
			}
			//
			$node->setAttribute('grp', 'tocomplete');
		}

		// here the .grouping.xml should exists
		if(file_exists($groupingFile))
		{
			// a .grouping.xml must stay in place
			// -- don't do, done in phase4
			//$xpath = new DOMXPath($dom);
			//$dnl = $xpath->query('./file[@name=".grouping.xml"]', $node);
			//if($dnl->length == 1)
			//	$dnl->item(0)->setAttribute('keep', '1');

			$sxGrouping = simplexml_load_file($groupingFile);
			$grp_rid = $sxGrouping['grouping'];

			$this->archiveFilesToGrp($dom, $node, $path.'/'.$grpFolder
													, $path_archived.'/'.$grpFolder
													, $path_error.'/'.$grpFolder
													, $grp_rid);
		}
	}


	/**
	 * archive every files inside a grp
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $grp_rid
	 */
	function archiveFilesToGrp($dom, $node, $path, $path_archived, $path_error, $grp_rid)
	{
		//usleep(1000);
		$nodesToDel = array();
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if($n->getAttribute('isdir')=='1')
			{
				// in a grp, all levels goes in the same grp
				$node->setAttribute('archived', '1');		// the main grp folder is 'keep'ed, but not subfolders
				$this->archiveFilesToGrp($dom, $n, $path.'/'.$n->getAttribute('name')
												, $path_archived.'/'.$n->getAttribute('name')
												, $path_error.'/'.$n->getAttribute('name')
												, $grp_rid);
			}
			else
			{
				// a file
				$this->archiveFile($dom, $n, $path, $path_archived, $path_error, $nodesToDel, $grp_rid);
			}
		}
// printf("======== %s %s \n", __LINE__, var_export($nodesToDel, true));
		foreach($nodesToDel as $n)
			$n->parentNode->removeChild($n);
	}


	/**
	 * ARCHIVE FILE !
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $path
	 * @param unknown_type $grp_rid
	 */

	function archiveFile($dom, $node, $path, $path_archived, $path_error, &$nodesToDel, $grp_rid=0)
	{
		$match = $node->getAttribute('match');
		if($match == '*')
			return;

		$file = $node->getAttribute('name');
		$cid  = $node->getAttribute('cid');
		$captionFileNode = null;

		$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
		$subpath  = substr($path, strlen($rootpath));


		if(!$match)
		{
			// the file does not match on any mask
			$this->log(sprintf(("File '%s' does not match any mask"), $subpath.'/'.$file));
			$node->setAttribute('error', '1');
			return;
		}
		elseif($match == '?')
		{
			// the caption file is missing
			$this->log(sprintf(("Caption of file '%s' is missing"), $subpath.'/'.$file));
			$node->setAttribute('error', '1');
			return;
		}
		elseif( ($match != '.') )		// match='.' : the file does not have a separate caption
		{
			$xpath = new DOMXPath($dom);
			$dnl = $xpath->query('./file[@name="'.$match.'"]', $node->parentNode);
			// in fact, xquery has been done in checkMatch, setting match='?' if caption does not exists...
			if($dnl->length == 1)
			{
				// ...so we ALWAYS come here
				$captionFileNode = $dnl->item(0);
			}
			else
			{
				// ...so we should NEVER come here
				$node->setAttribute('error', '1');
				return;
			}
		}

		$this->archiveFileAndCaption($dom, $node, $captionFileNode, $path, $path_archived, $path_error, $grp_rid, $nodesToDel);
 	}


	function archiveFileAndCaption($dom, $node, $captionFileNode, $path, $path_archived, $path_error, $grp_rid, &$nodesToDel)
	{
		$ret = false;

 		$file = $node->getAttribute('name');
		$captionFileName = $captionFileNode ? $captionFileNode->getAttribute('name') : NULL;

		$rootpath = p4string::delEndSlash(trim((string)($this->sxTaskSettings->hotfolder)));
		$subpath  = substr($path, strlen($rootpath));

		$this->log(sprintf(("Archiving file '%s'"), $subpath.'/'.$file));
		if($captionFileName !== NULL)
			$this->log(sprintf(' '.(" (caption in '%s')"), $captionFileName));
		if($grp_rid !== 0)
			$this->log(sprintf(' '.(" into GRP rid=%s"), $grp_rid));

		$stat0 = $stat1= "0";
		if($this->sxBasePrefs->status)
			$stat0 = (string)($this->sxBasePrefs->status);
		if($this->sxTaskSettings->status)
			$stat1 = (string)($this->sxTaskSettings->status);
		if(!$stat0)
			$stat0 = '0';
		if(!$stat1)
			$stat1 = '0';

		$rid = $this->connbas->getId("RECORD", 1);
		$mimeExt = giveMimeExt($path.'/'.$file);
		$propfile = array(
							'recordid' => $rid,
							'extension' => $mimeExt['ext'],
							'mime' => $mimeExt['mime'],
							'size' => sprintf('%u', filesize($path.'/'.$file)),
							'subpath' => $subpath,
							'parentdirectory' => '',
							'hotfolderfile' => $path.'/'.$file,
							'hotfoldercaptionfile' => NULL,
							'pathhd' => '',
							'file' => '',
							'originalname' => $file,
							'inbase' => 0,
							'type' => giveMeDocType($mimeExt['mime'])
						);

		if($captionFileName != $file)
		{
			$propfile['hotfoldercaptionfile'] = $path.'/'.$captionFileName;
		}

		$pi = pathinfo($subpath);
		$propfile['parentdirectory'] = $pi["basename"];
		// var_dump($propfile);

		$meta = get_xml($this->sxBasePrefs, $propfile);

		$hexstat = '';
		if($meta['status'] !== NULL)
		{
			$s  = strrev($meta['status']) . str_repeat('0', 64);
			for($a=0; $a<4; $a++)
				$hexstat = substr('0000'.base_convert(strrev(substr($s,  $a<<4, 16)), 2, 16), -4) . $hexstat;
		}
		else
		{
			$hexstat = '0';
		}

		$lazaret = false;
		$uuid = $sha256 = false;
		if($grp_rid == 0 && $captionFileName == NULL)
		{
			$this->log(sprintf(("Checkin for lazaret")));
			try
			{


				$base_id = (int)($this->sxTaskSettings->base_id);
				$sbas_id = phrasea::sbasFromBas($base_id);

        $sha256 = hash_file('sha256',$path.'/'.$file);

        $uuid = false;
        $file_uuid = new uuid($path.'/'.$file);
        if(!$file_uuid->has_uuid())
        {
          $connbas = connection::getInstance($sbas_id);
          $sql = 'SELECT uuid FROM record WHERE sha256 = "'.$connbas->escape_string($sha256).'"';
          if($rs = $connbas->query($sql))
          {
            if($row = $connbas->fetch_assoc($rs))
            {
              if(uuid::uuid_is_valid($row['uuid']))
                $uuid = $row['uuid'];
            }
            $connbas->free_result($rs);
          }
        }

        $uuid = $file_uuid->write_uuid($uuid);

				$error_file = p4file::check_file_error($path.'/'.$file, $sbas_id, $file);
				$status = status::or_operation($stat0, $stat1);

				if($meta['status'])
					$status = status::or_operation($status, $meta['status']);

				if(($uuid !== false && !$file_uuid->is_new_in_base(phrasea::sbasFromBas($base_id))) || count($error_file) > 0)
				{
					$this->log(sprintf(("Trying to move to lazaret")));
					if(lazaretFile::move_uploaded_to_lazaret($path.'/'.$file, $base_id, $file, $uuid, $sha256, implode("\n",$error_file), $status))
					{
						$this->log("File %s moved to lazaret");
						$lazaret = true;
						$node->setAttribute('archived', '1');
						$this->archivedFiles++;
					}
				}
				else
				{
						$this->log("No need to lazaret");
				}

			}
			catch (Exception $e)
			{
				$this->log(sprintf(("Error while checking for lazaret : %s"), $e->getMessage()));
			}
		}

		if(!$lazaret)
		{

			$cid = $node->getAttribute('cid');
			// ... flag the record as 'to reindex' (status-bits 0 and 1 to 0) AND UNLOCK IT (status-bit 2 to 1)
			// ... and flag it for subdef creation (jeton bit 0 to 1)
			$fl = 'coll_id, record_id, parent_record_id, status, jeton, moddate, credate, xml, type, sha256, uuid';
			$vl = $cid . ', ' .$rid . ', ' . '0' . ', (((' . $stat0 . ' | ' . $stat1 . ' | 0x' . $hexstat . ') & ~0x0F) | 0x0C),
          '.JETON_READ_META_DOC_MAKE_SUBDEF.', NOW(), NOW(), \'' . $this->connbas->escape_string($meta['xml']->saveXML()) . '\',
            \'' . $this->connbas->escape_string($propfile['type']) . '\', \''.$sha256.'\',
              "'.$this->connbas->escape_string($uuid).'"' ;
			$sql = 'INSERT INTO record ('.$fl.') VALUES ('.$vl.')';

			//printf("sql:%s\n", $sql);
			if($this->connbas->query($sql))
			{
				if($grp_rid !== NULL)
				{
					$this->connbas->query("INSERT INTO regroup (id, rid_parent, rid_child, dateadd, ord) VALUES (NULL, $grp_rid, $rid, NOW(), 0)");
				}

				$copyhd = true;

				$documentisok = false;
				if($copyhd)
				{
					// archiving with copy into base
					$pathhd = trim((string)($this->sxBasePrefs->path));
					$pathhd = p4::dispatch($pathhd);

					$newname = $rid . "_document.".$propfile["extension"];
					if(copy($propfile["hotfolderfile"], $pathhd . $newname))
					{
						$propfile['pathhd'] = $pathhd;
						$propfile['file'] = $newname;
						$propfile['inbase'] = 1;

						$fl  = 'record_id';		$vl  = $rid;
						$fl .= ', name';		$vl .= ", 'document'";
						$fl .= ', path';		$vl .= ", '" . $this->connbas->escape_string($propfile['pathhd']) . "'";
						$fl .= ', file';		$vl .= ", '" . $this->connbas->escape_string($propfile['file']) . "'";
						$fl .= ', inbase';		$vl .= ", '" . $propfile['inbase'] . "'";
						if(isset($propfile['width']))
						{
							$fl .= ', width';		$vl .= ", '" . $this->connbas->escape_string($propfile['width']) . "'";
						}
						if(isset($propfile['height']))
						{
							$fl .= ', height';		$vl .= ", '" . $this->connbas->escape_string($propfile['height']) . "'";
						}
						$fl .= ', mime';		$vl .= ", '" . $this->connbas->escape_string($propfile['mime']) . "'";
						$fl .= ', size';		$vl .= ", '" . $this->connbas->escape_string($propfile['size']) . "'";
						$fl .= ', dispatched';		$vl .= ", '1'";
						$sql = "INSERT INTO subdef (".$fl.") VALUES (".$vl.")";

						if($this->connbas->query($sql))
						{
							$node->setAttribute('archived', '1');
							if($captionFileNode)
								$captionFileNode->setAttribute('archived', '1');
						}
						else
						{
							$this->log(("Error : can't insert into subdef"));
							$node->setAttribute('error', '1');
							if($captionFileNode)
								$captionFileNode->setAttribute('error', '1');
						}

					}
					else
					{
						$this->log(sprintf(('Error : can\'t copy from \'%1$s\' to \'%$2s\''), $propfile["hotfolderfile"], $pathhd.$newname));
						$node->setAttribute('error', '1');
						if($captionFileNode)
							$captionFileNode->setAttribute('error', '1');
					}
				}
				else
				{
					// archiving 'in place'
					$node->setAttribute('archived', '1');
					if($captionFileNode)
						$captionFileNode->setAttribute('archived', '1');
				}

				$this->archivedFiles++;
			}
			else
			{
				$this->log(("Error : can't insert into record"));
				$node->setAttribute('error', '1');
				if($captionFileNode)
					$captionFileNode->setAttribute('error', '1');
			}
		}

		if($node->getAttribute('archived') && $this->move_archived)
		{
			$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$file));

			@mkdir($path_archived, 0755, true);
			@copy($path.'/'.$file, $path_archived.'/'.$file);
			if($captionFileName != $file)
			{
				$this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath.'/'.$captionFileName));
				@copy($path.'/'.$captionFileName, $path_archived.'/'.$captionFileName);
			}
			if(!$node->getAttribute('keep'))	// do not count copy of special files as a real event
				$ret = true;
		}

		if($node->getAttribute('error') && $this->move_error)
		{
			$this->log(sprintf(('copy \'%s\' to \'error\''), $subpath.'/'.$file));

			@mkdir($path_error, 0755, true);
			@copy($path.'/'.$file, $path_error.'/'.$file);
			if($captionFileName != $file)
			{
				$this->log(sprintf(('copy \'%s\' to \'error\''), $subpath.'/'.$captionFileName));
				@copy($path.'/'.$captionFileName, $path_error.'/'.$captionFileName);
			}
			if(!$node->getAttribute('keep'))	// do not count copy of special files as a real event
				$ret = true;
		}

		if(!$node->getAttribute('keep'))
		{
			$file = $node->getAttribute('name');
			@unlink($path.'/'.$file);
			$nodesToDel[] = $node;

			$this->movedFiles++;
		}

		if($captionFileNode && !$captionFileNode->getAttribute('keep'))
		{
			$file = $captionFileNode->getAttribute('name');
			@unlink($path.'/'.$file);
			$nodesToDel[] = $captionFileNode;

			$this->movedFiles++;
		}
// printf("======== %s %s \n", __LINE__, var_export($nodesToDel, true));
	}





	/**
	 * xml facility :
	 *   set attributes to a node and all children
	 *
	 * @param unknown_type $dom
	 * @param unknown_type $node
	 * @param unknown_type $attributes
	 */

	function setAllChildren($dom, $node, $attributes, $depth=0)
	{
		static $iloop=0;
		if($depth==0)
			$iloop = 0;

		foreach($attributes as $a=>$v)
			$node->setAttribute($a, $v);

		if( ($iloop++ % 100) == 0)
			usleep(1000);

		for($n=$node->firstChild; $n; $n=$n->nextSibling)
			$this->setAllChildren($dom, $n, $attributes, $depth+1);
	}


	function getGrpSettings($file)
	{
		$matched = FALSE;
		foreach($this->tmaskgrp as $maskgrp)
		{
			//$attachments = null;
			//$attachments["representation"] = null;
			//$attachments["caption"] = null;

			$preg_maskgrp = "/" . $maskgrp["mask"] . "/";
			if(preg_match($preg_maskgrp, $file))
			{
				$matched = $maskgrp;
			}
			if($matched)
				break;
		}
		return($matched);
	}

}

class CListFolder
{
	private $list;
	function __construct($path, $sorted = false)
	{
		$this->list = array();
		if($hdir = opendir($path))
		{
			while(false !== ($file = readdir($hdir)))
				$this->list[] = $file;
			closedir($hdir);
			if($sorted)
				natcasesort($this->list);
		}
	}
	function __destruct()
	{
		unset($this->list);
	}
	function read()
	{
		return(array_shift($this->list));
	}
}

