<?php
require_once(GV_RootPath."lib/index_utils2.php");


class task_writemeta extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_('task::writemeta:ecriture des metadatas'));
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
							, 'cleardoc'
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
			foreach(array("str:period", 'str:maxrecs', 'str:maxmegs', 'boo:cleardoc') as $pname)
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
					// le champ n'existait pas dans le xml, on le cr�e
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

				if( (string)($sxml->maxrecs) == '')
					$sxml->maxrecs = 100;
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
			<?php echo $form?>.period.value        = "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";
			<?php echo $form?>.cleardoc.checked    = <?php echo p4field::isyes($sxml->cleardoc) ? "true" : 'false'?>;
			<?php echo $form?>.maxrecs.value       = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"')?>";
			<?php echo $form?>.maxmegs.value       = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"')?>";
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
			var limits = { 'period':{min:1, 'max':300} , 'maxrecs':{min:10, 'max':1000} , 'maxmegs':{min:2, 'max':100} } ;
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
	// printInterfaceHTML(..) : g�n�rer l'interface 'graphic view' !! EN UTF-8 !!
	// ====================================================================
	public function printInterfaceHTML()
	{
		global $usr_id;
		$conn = connection::getInstance();
		$sql = 'SELECT sbas.sbas_id, dbname, viewname FROM sbas INNER JOIN sbasusr ON sbasusr.sbas_id=sbas.sbas_id AND usr_id=\''.$usr_id.'\' AND bas_manage>0 ORDER BY ord';
		if($rs = $conn->query($sql))
		{
?>
		<form name="graphicForm" onsubmit="return(false);" method="post">
			<br/>
			<?php echo _('task::_common_:periodicite de la tache')?>&nbsp;:&nbsp;
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
			<?php echo _('task::_common_:secondes (unite temporelle)')?><br/>
			<br/>
			<input type="checkbox" name="cleardoc" onchange="chgxmlck(this)">
			<?php echo _('task::writemeta:effacer les metadatas non presentes dans la structure')?>
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
		return(_("task::writemeta:(re)ecriture des metadatas dans les documents (et subdefs concernees)"));
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

		$conn = connection::getInstance();
		
		$this->period = (int)($this->sxTaskSettings->period);
		if($this->period <= 0 || $this->period >= 60*60)
			$this->period = 60;

		// ici la t�che tourne tant qu'elle est active
		$this->running = true;
		$taskStatus = '';
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
		$ret = '';
		
		$conn = connection::getInstance();
		
		$this->sxBasePrefs = databox::get_sxml_structure($this->sbas_id);

		if($this->sxBasePrefs)
		{				

			$subdefgroups = databox::get_subdefs($this->sbas_id);
			$metasubdefs = array();

			foreach($subdefgroups as $type=>$subdefs)
			{
				foreach($subdefs as $sub)
				{
					$name = $sub->attributes()->name;
					if(p4field::isyes($sub->meta))
						$metasubdefs[$name.'_'.$type] = true;
				}
			}
		
			$sql = 'SELECT record.record_id, record.type, record.xml, record.jeton, name, path, file
			 FROM record INNER JOIN subdef ON (record.jeton & '.JETON_WRITE_META.' > 0) AND subdef.record_id=record.record_id
			 GROUP BY record_id, name WITH ROLLUP' ;
			
			if( ($rsbas = $this->connbas->query($sql)) )
			{
				$tsub = array();
				$rowstodo = $this->connbas->num_rows($rsbas);
				$rowsdone = 0;
				while( ($rowbas = $this->connbas->fetch_assoc($rsbas)) )
				{
					if($rowbas['record_id'] === NULL)
						continue;

					$name  = $rowbas['name'];
					if($name !== NULL)
					{
						$jeton = $rowbas['jeton'];
						$type  = $rowbas['type'];
						if( (($jeton & JETON_WRITE_META_DOC) && $name=='document') || (($jeton & JETON_WRITE_META_SUBDEF) && isset($metasubdefs[$name.'_'.$type])) )
						{
							$tsub[$name] = p4string::addEndSlash($rowbas['path']).$rowbas['file'];
						}
					}
					else
					{
						$this->doRecord($rowbas['record_id'], $rowbas['xml'], $tsub);
						$tsub = array();
					
						$rowsdone++;
						unset($rowbas);
						
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
					}
				}

				if($ndone >= (int)($this->sxTaskSettings->maxrecs))
				{
					$this->log(sprintf(('%d records done, restarting'), $ndone));
				}
				if(memory_get_usage()>>20 >= (int)($this->sxTaskSettings->maxmegs))
				{
					$this->log(sprintf(('memory reached %d Ko, restarting'), memory_get_usage()>>10));
				}

				$this->connbas->free_result($rsbas);
				unset($rsbas);
	
				if($rowstodo > 0)
					$this->setProgress(0, 0);
				
				unset($tsub);
			}
		}

		if($ret == '' && $ndone==0)
			$ret = 'NORECSTODO';
		
		return($ndone);
	}
	
	function doRecord($rid, $xml, &$tsub)
	{
		if( ($sxxml = simplexml_load_string($xml)) )
		{
			$uuid = false;
			$sql = 'SELECT uuid FROM record WHERE record_id="'.$rid.'"';
			if($rs = $this->connbas->query($sql))
			{
				if($row = $this->connbas->fetch_assoc($rs))
				{
					$uuid = $row['uuid'];
				}
				$this->connbas->free_result($rs);
			}
			
			
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->formatOutput = true;
			$dom->preserveWhiteSpace = true;
			
			$noderoot = $dom->appendChild($dom->createElement('rdf:RDF'));
			$noderoot->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
	
			$nodedesc = $noderoot->appendChild($dom->createElement('rdf:Description'));
		
			if($uuid)
			{
				$node = $nodedesc->appendChild($dom->createElement('XMP-exif:ImageUniqueID'));
				$node->appendChild($dom->createTextNode($uuid));
				$node = $nodedesc->appendChild($dom->createElement('IPTC:UniqueDocumentID'));
				$node->appendChild($dom->createTextNode($uuid));
			}
			
			$nodedesc->setAttribute('xmlns:et', 'http://ns.exiftool.ca/1.0/');
			$nodedesc->setAttribute('et:toolkit', 'Image::ExifTool 7.62');
			
			$nodedesc->setAttribute('xmlns:ExifTool', 'http://ns.exiftool.ca/ExifTool/1.0/');
			$nodedesc->setAttribute('xmlns:File', 'http://ns.exiftool.ca/File/1.0/');
			$nodedesc->setAttribute('xmlns:JFIF', 'http://ns.exiftool.ca/JFIF/JFIF/1.0/');
			$nodedesc->setAttribute('xmlns:Photoshop', 'http://ns.exiftool.ca/Photoshop/Photoshop/1.0/');
			$nodedesc->setAttribute('xmlns:IPTC', 'http://ns.exiftool.ca/IPTC/IPTC/1.0/');
			$nodedesc->setAttribute('xmlns:Adobe', 'http://ns.exiftool.ca/APP14/Adobe/1.0/');
			$nodedesc->setAttribute('xmlns:FotoStation', 'http://ns.exiftool.ca/FotoStation/FotoStation/1.0/');
			$nodedesc->setAttribute('xmlns:File2', 'http://ns.exiftool.ca/File/1.0/');
			$nodedesc->setAttribute('xmlns:IPTC2', 'http://ns.exiftool.ca/IPTC/IPTC2/1.0/');
			$nodedesc->setAttribute('xmlns:Composite', 'http://ns.exiftool.ca/Composite/1.0/');
			
			$nodedesc->setAttribute('xmlns:IFD0', 'http://ns.exiftool.ca/EXIF/IFD0/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-x', 'http://ns.exiftool.ca/XMP/XMP-x/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-exif', 'http://ns.exiftool.ca/XMP/XMP-exif/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-photoshop', 'http://ns.exiftool.ca/XMP/XMP-photoshop/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-tiff', 'http://ns.exiftool.ca/XMP/XMP-tiff/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-xmp', 'http://ns.exiftool.ca/XMP/XMP-xmp/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-xmpMM', 'http://ns.exiftool.ca/XMP/XMP-xmpMM/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-xmpRights', 'http://ns.exiftool.ca/XMP/XMP-xmpRights/1.0/');
			$nodedesc->setAttribute('xmlns:XMP-dc', 'http://ns.exiftool.ca/XMP/XMP-dc/1.0/');
			$nodedesc->setAttribute('xmlns:ExifIFD', 'http://ns.exiftool.ca/EXIF/ExifIFD/1.0/');
			$nodedesc->setAttribute('xmlns:GPS', 'http://ns.exiftool.ca/EXIF/GPS/1.0/');
			$nodedesc->setAttribute('xmlns:PDF', 'http://ns.exiftool.ca/PDF/PDF/1.0/');
			
			foreach($this->sxBasePrefs->description->children() as $fname=>$field)
			{
				if( (($src = (string)($field['src'])) != '') ) // && ($f = $sxxml->{$fname}) )
				{
					$tsrc = explode('/', $src);
					if(count($tsrc)==4 && $tsrc[0]=='' && $tsrc[1]=='rdf:RDF' && $tsrc[2]=='rdf:Description')
					{
						$tag = $tsrc[3];
						$multi = p4field::isyes($field['multi']);
						$type = (string)($field['type']);
						$glue = '';
						$node = $nodedesc->appendChild($dom->createElement($tag));
						if($multi)
						{
							$node = $node->appendChild($dom->createElement('rdf:Bag'));
						}
						foreach($sxxml->description->{$fname} as $f)
						{
							if($type=='date')
							{
								$f = str_replace(array("-", ":", "/", "."), array(" ", " ", " ", " "), $f);
								$ip_date_yyyy = 0;
								$ip_date_mm   = 0;
								$ip_date_dd   = 0;
								$ip_date_hh   = 0;
								$ip_date_nn   = 0;
								$ip_date_ss   = 0;
								switch(sscanf($f, "%d %d %d %d %d %d", $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss))
								{
									case 1:
										$val = sprintf("%04d:00:00", $ip_date_yyyy);
										break;
									case 2:
										$val = sprintf("%04d:%02d:00", $ip_date_yyyy, $ip_date_mm);
										break;
									case 3:
									case 4:
									case 5:
									case 6:
										$val = sprintf("%04d:%02d:%02d", $ip_date_yyyy, $ip_date_mm, $ip_date_dd);
										break;
									default:
										$val = '0000:00:00';
								}
							}
							if($multi)
							{
								$node->appendChild($dom->createElement('rdf:li'))->appendChild($dom->createTextNode((string)$f));
							}
							else
							{
								$glue .= ($glue?' ; ':'') . (string)$f;
							}
						}
						if(!$multi)
						{
							$node->appendChild($dom->createTextNode($glue));
						}
					}
				}
			}
			$temp = tempnam(GV_RootPath.'tmp/', 'meta');
			rename($temp, $tempxml = $temp.'.xml');
			$dom->save($tempxml);
			
			foreach($tsub as $name=>$file)
			{
				$cmd = '';
				if($this->system == 'WINDOWS')
					$cmd    = 'start /B /LOW ';
				$cmd .= ( GV_exiftool.' -m -overwrite_original ');
				if($name != 'document' || p4field::isyes($this->sxTaskSettings->cleardoc))
					$cmd .= '-all:all= ';
				$cmd .= (' -codedcharacterset=utf8 -TagsFromFile ' . escapeshellarg($tempxml) . ' ' . escapeshellarg($file) );
 				
				$this->log(sprintf(('writing meta for sbas_id=%1$d - record_id=%2$d (%3$s)'), $this->sbas_id, $rid, $name) );
				
				$s = trim(shell_exec($cmd));
				
				$this->log("\t" . $s );
			}

			unlink($tempxml);
		}
		
		$sql = 'UPDATE record SET jeton=jeton & ~'.JETON_WRITE_META.' WHERE record_id=' . $rid;
		$this->connbas->query($sql);
	}
}

?>