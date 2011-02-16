<?php
require_once(GV_RootPath."lib/index_utils2.php");


class task_readmeta extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_('task::readmeta:lecture des metadatas'));
	}

	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	public function graphic2xml($oldxml)
	{
//		global $parm;
		$request = httpRequest::getInstance();

		$parm2 = $request->get_parms(
							  "period"
							, 'flush'
							, 'maxrecs'
							, 'maxmegs'
						);
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		if( $dom->loadXML($oldxml) )
		{
			$xmlchanged = false;
			// foreach($parm2 as $pname=>$pvalue)
			foreach(array("str:period", "str:flush", "str:maxrecs", "str:maxmegs") as $pname)
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

			if( (string)($sxml->flush) == '' )
				$sxml->flush = 10;
			elseif( (int)($sxml->flush) < 1 )
				$sxml->flush = 1;
			elseif( (int)($sxml->flush) > 100 )
				$sxml->flush = 100;

			if( (string)($sxml->maxrecs) == '')
				$sxml->maxrecs = 200;
			if( (int)($sxml->maxrecs) < 10 )
				$sxml->maxrecs = 10;
			elseif( (int)($sxml->maxrecs) > 500 )
				$sxml->maxrecs = 500;

			if( (string)($sxml->maxmegs) == '' )
				$sxml->maxmegs = 6;
			if( (int)($sxml->maxmegs) < 3 )
				$sxml->maxmegs = 3;
			elseif( (int)($sxml->maxmegs) > 32 )
				$sxml->maxmegs = 32;
?>
		<script type="text/javascript">
			<?php echo $form?>.period.value         = "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";
			<?php echo $form?>.flush.value          = "<?php echo p4string::MakeString($sxml->flush, "js", '"')?>";
			<?php echo $form?>.maxrecs.value        = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"')?>";
			<?php echo $form?>.maxmegs.value        = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"')?>";
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
	// printInterfaceJS() : g�n�rer le code js de l'interface 'graphic view'
	// ====================================================================
	public function printInterfaceJS()
	{
		global $parm;
?>
		<script type="text/javascript">
		function chgxmltxt(textinput, fieldname)
		{
			var limits = { 'period':{min:1, 'max':300} , 'flush':{min:1, 'max':100} , 'maxrecs':{min:10, 'max':1000} , 'maxmegs':{min:2, 'max':100} } ;
			if(typeof(limits[fieldname])!='undefined')
			{
				var v = 0|textinput.value;
				if(v < limits[fieldname].min)
					v = limits[fieldname].min;
				else if(v > limits[fieldname].max)
					v = limits[fieldname].max;
				textinput.value = v;
			}
			setDirty();
		}
		function chgxmlck_die(ck)
		{
			if(ck.checked)
			{
				if(document.forms['graphicForm'].maxrecs.value == "")
					document.forms['graphicForm'].maxrecs.value = 500;
				if(document.forms['graphicForm'].maxmegs.value == "")
					document.forms['graphicForm'].maxmegs.value = 4;
				document.forms['graphicForm'].maxrecs.disabled = document.forms['graphicForm'].maxmegs.disabled = false;
			}
			else
			{
				document.forms['graphicForm'].maxrecs.disabled = document.forms['graphicForm'].maxmegs.disabled = true;
			}
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
?>
		<form name="graphicForm" onsubmit="return(false);">
			<br/>
			<?php echo _('task::_common_:periodicite de la tache')?>&nbsp;:&nbsp;
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
			<?php echo _('task::_common_:secondes (unite temporelle)')?><br/>
			<br/>
			<?php echo sprintf(_("task::_common_:passer tous les %s records a l'etape suivante"),'<input type="text" name="flush" style="width:40px;" onchange="chgxmltxt(this, \'flush\');" value="">'); ?>
			<br/>
			<br/>
			<?php echo _('task::_common_:relancer la tache tous les')?>&nbsp;
			<input type="text" name="maxrecs" style="width:40px;" onchange="chgxmltxt(this, 'maxrecs');" value="">
			<?php echo _('task::_common_:records, ou si la memoire depasse')?>&nbsp;
			<input type="text" name="maxmegs" style="width:40px;" onchange="chgxmltxt(this, 'maxmegs');" value="">
			Mo
			<br/>
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
		return(_("task::readmeta:lecture des metadatas des documents"));
	}


	// ======================================================================================================
	// ===== run() : le code d'�x�cution de la t�che proprement dite
	// ======================================================================================================

	private $sxTaskSettings = null;	// les settings de la tache en simplexml
	private $connbas = null;		// cnx � la base
	private $running = false;
	private $msg = "";

	function run()
	{
		if( ($this->sxTaskSettings = simplexml_load_string($this->taskSettings)) )
		{
			return($this->run2());
		}
		else
		{
			// Settings illisibles
			return(false);
		}
	}

	private function run2()
	{
		// ex : 3 dbox, maxrecs=500, maxmegs=4
		// scenario 1 : 100;0;80
		//  100;0;80 ; 0;0;0;sleep ; 0;0;0;sleep...
		// scenario 2 : 630;0;750
		//  500;0;500;restart ; 130;0;250 ; 0;0;0;sleep ; 0;0;0;sleep...
		// scenario 3 : 820;0;750 (420+250>500 --> restart)
		//  500;0;500;restart ; 420;0;250;restart ; 0;0;0;sleep ; 0;0;0;sleep...
		// scenario 4 : 100;0;80 (mem overflow each 45 records)
		//  45;restart ; 45;restart ; 10;0;45;restart; 0;0;35; 0;0;0;sleep ; 0;0;0;sleep...

		$ret = '';

		$this->period = (int)($this->sxTaskSettings->period);
		if($this->period <= 0 || $this->period >= 60*60)
			$this->period = 60;

		// ici la t�che tourne tant qu'elle est active
		$this->running = true;
		$taskStatus = '';

		$conn = connection::getInstance();
		$loop = 0;
		while($conn && $this->running)
		{
			$sql = "UPDATE task2 SET last_exec_time=NOW() WHERE task_id=" . $this->taskid ;
			$conn->query($sql);

			$sql = "SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id=" . $this->taskid ;
			if($rs = $conn->query($sql))
			{
				$allRecsDone = 0;	// sum of records done on each dbox

				$duration = time();
				while($this->running && ($row = $conn->fetch_assoc($rs)) )
				{
					$taskStatus = $row['status'];
					$this->sbas_id = (int)$row['sbas_id'];

					if($taskStatus == 'tostop')
					{
						$ret = 'stopped';
						$this->running = false;
					}
					else
					{
						if( !($this->connbas = connection::getInstance($this->sbas_id)) )
						{
							continue;
						}
						if(!$this->connbas->isok())
						{
							$this->connbas->close();
							continue;
						}

						if( $this->sxTaskSettings = simplexml_load_string($row['settings']) )
						{
							$period = (int)($this->sxTaskSettings->period);
							if($period <= 0 || $period >= 60*60)
								$period = 60;

							if((int)($this->sxTaskSettings->maxrecs)<10 || (int)($this->sxTaskSettings->maxrecs)>1000)
								$this->sxTaskSettings->maxrecs = 100;
							if((int)($this->sxTaskSettings->maxmegs)<2 || (int)($this->sxTaskSettings->maxmegs)>100)
								$this->sxTaskSettings->maxmegs = 20;
						}
						else
						{
							$period = 60;
						}

						// on lit les prefs de cette base
						$this->sxBasePrefs = databox::get_sxml_structure($this->sbas_id);

						$retStatus = null;
						$allRecsDone += $this->doRecords($retStatus);	// $retStatus:byref

						$this->connbas->close();

						if($retStatus == 'TOSTOP')
						{
							$ret = 'stopped';
							$this->running = false;		// quit NOW !
						}
						elseif($retStatus == 'MAXMEGSREACHED')
						{
							if($taskStatus != 'manual')
							{
								$ret = 'torestart';
								$this->running = false;		// restart NOW !
							}
						}
						elseif($retStatus == 'MAXRECSDONE')
						{
							if($taskStatus != 'manual')
							{
								$ret = 'torestart';			// restart after last dbox
							}
						}
					}
				}
				$conn->free_result($rs);

				if($loop > 5 || $allRecsDone >= (int)($this->sxTaskSettings->maxrecs))
				{
					if($ret == 'started')
					{
						$ret = 'torestart';
					}
				}

				if($this->running && $ret=='' && $allRecsDone == 0)
				{
					// nothing to do on every dbox, so pause
					$duration = time() - $duration;
					if($duration < $period)
					{
						$conn->close();
						sleep($period - $duration);
						unset($conn);
						$conn = connection::getInstance();
					}
				}

				if($ret != '')
					$this->running = false;
			}
			else
			{
				$ret = 'stopped';
				$this->running = false;
			}
                        $loop++;
		}

		return($ret);
	}

	function doRecords(&$ret)
	{
		$ndone = 0;
		$recsToNext = array('n'=>0, 'X'=>array(), 'XS'=>array());
		$ret = '';

		$this->sxBasePrefs = databox::get_sxml_structure($this->sbas_id);

		$conn = connection::getInstance();

		if($this->sxBasePrefs)
		{
			$sql = 'SELECT record.record_id, xml, jeton, path, file, mime FROM record INNER JOIN subdef ON (record.jeton & '.(JETON_READ_META_DOC | JETON_READ_META_DOC_MAKE_SUBDEF).' > 0) AND subdef.record_id=record.record_id AND subdef.name=\'document\' ORDER BY record_id' ;
			if( ($rsbas = $this->connbas->query($sql)) )
			{
				$rowstodo = $this->connbas->num_rows($rsbas);
				$rowsdone = 0;
				while( ($rowbas = $this->connbas->fetch_assoc($rsbas)) )
				{
					$this->doRecord($rowbas['record_id'], $rowbas['xml'], p4string::addEndSlash($rowbas['path']).$rowbas['file'], $rowbas['mime'], $rowbas['jeton'], $recsToNext);
					$ndone++;

					$this->setProgress($rowsdone, $rowstodo);

					$rowsdone++;

					$sql = "SELECT status FROM task2 WHERE task_id=" . $this->taskid ;
					if($rs = $conn->query($sql))
					{
						$row = $conn->fetch_assoc($rs);
						$conn->free_result($rs);
						if(!$row || $row['status'] == 'tostop')
						{
							$ret = 'TOSTOP';
							break;
						}
					}
					else
					{
						// appbox crashed ? stop task
						$ret = 'TOSTOP';
						break;
					}

					if($ndone >= (int)($this->sxTaskSettings->maxrecs))
					{
						$ret = 'MAXRECSDONE';
						break;
					}
					if(memory_get_usage()>>20 >= (int)($this->sxTaskSettings->maxmegs))
					{
						$ret = 'MAXMEGSREACHED';
						break;
					}

					if($recsToNext['n']  >= (int)($this->sxTaskSettings->flush))
					{
						$this->flushRecsToIndex($recsToNext);
						$recsToNext = array('n'=>0, 'X'=>array(), 'XS'=>array());
					}
				}

				$this->flushRecsToIndex($recsToNext);
				$recsToNext = array('n'=>0, 'X'=>array(), 'XS'=>array());

				if($ndone >= (int)($this->sxTaskSettings->maxrecs))
				{
					$this->log(sprintf(_('task::_common_:%d records done, restarting'), $ndone));
				}
				if(memory_get_usage()>>20 >= (int)($this->sxTaskSettings->maxmegs))
				{
					$this->log(sprintf(_('task::_common_:memory reached %d Ko, restarting'), memory_get_usage()>>10));
				}

				$this->connbas->free_result($rsbas);

				if($rowstodo > 0)
					$this->setProgress(0, 0);
			}
			unset($recsToNext);
		}

		if($ret == '' && $ndone==0)
			$ret = 'NORECSTODO';

		return($ndone);
	}

	function doRecord($rid, $xml, $file, $mime, $jeton, &$recsToNext)
	{
		$this->log(sprintf(_('task::readmeta:reading meta for sbas_id=%1$d - record_id=%2$d').' : ', (int)$this->sbas_id, (int)$rid) );

		$domxml = new DOMDocument('1.0', 'UTF-8');
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;

//printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($xml, true));
		if($domxml->loadXML($xml))
		{
//printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($domxml->saveXML(), true));
			$propfile = array(
								'recordid' => $rid,
								'mime' => $mime,
								'hotfolderfile' => $file,
								'size' => filesize($file)
							);

			$xml = read_meta($this->sxBasePrefs, $propfile, $domxml);
//printf("%s (%d):\n%s\n", __FILE__, __LINE__, var_export($xml, true));
			if($xml && isset($xml["xml"]) && $xml["xml"]!="")
			{
				// ask for reindex, but lock record to prevent start
				$sql = 'UPDATE record SET jeton=(jeton & ~'.(JETON_READ_META_DOC | JETON_READ_META_DOC_MAKE_SUBDEF).'), xml=\''.$this->connbas->escape_string($xml["xml"]).'\' WHERE record_id=' . $rid;
				$ret = _('task::_common_:ok');
			}
			else
			{
				$sql = 'UPDATE record SET jeton=(jeton & ~'.(JETON_READ_META_DOC | JETON_READ_META_DOC_MAKE_SUBDEF).') WHERE record_id=' . $rid;
				$ret = _('task::_common_:bad');
			}
			$this->connbas->query($sql);

			if($jeton & JETON_READ_META_DOC_MAKE_SUBDEF)
				$recsToNext['XS'][] = $rid;		// index + subdef
			else
				$recsToNext['X'][] = $rid;			// index only
			$recsToNext['n']++;

			$sql = '';
			if(isset($propfile['width']))
				$sql .= ($sql?', ':'') . 'width=\'' . $this->connbas->escape_string($propfile['width']) . '\'';
			if(isset($propfile['height']))
				$sql .= ($sql?', ':'') . 'height=\'' . $this->connbas->escape_string($propfile['height']) . '\'';
			if(isset($propfile['size']))
				$sql .= ($sql?', ':'') . 'size=\'' . $this->connbas->escape_string($propfile['size']) . '\'';
			if($sql)
			{
				$sql = 'UPDATE subdef SET ' . $sql . ' WHERE record_id=' . $rid . ' AND name=\'document\'' ;
				$this->connbas->query($sql);
			}
		}
		else
		{
			$sql = 'UPDATE record SET jeton=(jeton & ~'.(JETON_READ_META_DOC | JETON_READ_META_DOC_MAKE_SUBDEF).') WHERE record_id=' . $rid;
			$ret = _('task::_common_:bad');
			$this->connbas->query($sql);
		}
		$this->log(' ' . $ret);
	}


	function flushRecsToIndex(&$recsToNext)
	{
		$sql = '';
		foreach($recsToNext['X'] as $rid)
			$sql .= ($sql?',':'') . $rid;
		// ask for index only
		if($sql != '')
		{
			$this->log(sprintf(_('task::readmeta:setting %d record(s) to index'), count($recsToNext['X'])));
			$sql = 'UPDATE record SET status=(status & ~0x03) WHERE record_id IN ('.$sql.')';
			$this->connbas->query($sql);
		}

		$sql = '';
		foreach($recsToNext['XS'] as $rid)
			$sql .= ($sql?',':'') . $rid;
		// ask for index and subdef
		if($sql != '')
		{
			$this->log(sprintf(_('task::readmeta:setting %d record(s) to index and subdef creation'), count($recsToNext['XS'])));
			$sql = 'UPDATE record SET status=(status & ~0x03), jeton=(jeton | '.JETON_MAKE_SUBDEF.') WHERE record_id IN ('.$sql.')';
			$this->connbas->query($sql);
		}
	}
}

?>