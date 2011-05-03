<?php
require_once(GV_RootPath."lib/index_utils2.php");


class task_workflow01 extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_('task::workflow01'));
	}
	
	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	public function graphic2xml($oldxml)
	{
//		global $parm;
		$request = httpRequest::getInstance();
		
		$parm2 = $request->get_parms(
							  "sbas_id"
							, "period"
							, 'status0'
							, 'coll0'
							, 'status1'
							, 'coll1'
						);
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		if( $dom->loadXML($oldxml) )
		{
			$xmlchanged = false;
			// foreach($parm2 as $pname=>$pvalue)
			foreach(array(
							"str:sbas_id",
							"str:period",
							'str:status0',
							'str:coll0',
							'str:status1',
							'str:coll1',
							) as $pname)
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
	// xml2graphic : must fill the graphic form (using js) from xml
	// ====================================================================
	public function xml2graphic($xml, $form)
	{
		if( ($sxml = simplexml_load_string($xml)) )	// in fact XML IS always valid here...
		{
			// ... but we could check for safe values 
			if( (int)($sxml->period) < 10 )
				$sxml->period = 10;
			elseif( (int)($sxml->period) > 1440 )	// 1 jour
				$sxml->period = 1440;

			if( (string)($sxml->delay) == '')
				$sxml->delay = 0;
?>
		<script type="text/javascript">
			var i;
			var opts;
			var pops = [
				{'name':"sbas_id", 'val':"<?php echo p4string::MakeString($sxml->sbas_id, "js")?>"},
				
				{'name':"status0",  'val':"<?php echo p4string::MakeString($sxml->status0, "js")?>"},
				{'name':"coll0",    'val':"<?php echo p4string::MakeString($sxml->coll0, "js")?>"},
				
				{'name':"status1",  'val':"<?php echo p4string::MakeString($sxml->status1, "js")?>"},
				{'name':"coll1",    'val':"<?php echo p4string::MakeString($sxml->coll1, "js")?>"}
			 ];
			for(j in pops)
			{
				for(opts=<?php echo $form?>[pops[j].name].options, i=0; i<opts.length; i++)
				{
					if(opts[i].value == pops[j].val)
					{
						opts[i].selected = true;
						break;
					}
				}
				if(j==0)
					parent.chgsbas(<?php echo $form?>[pops[j].name]);
			}
			<?php echo $form?>.period.value   = "<?php echo p4string::MakeString($sxml->period, "js", '"')?>";
			parent.calccmd();
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
	// printInterfaceHEAD() : 
	// ====================================================================
	public function printInterfaceHEAD()
	{
?>
		<style>
			OPTION.jsFilled
			{
				padding-left:10px;
				padding-right:20px;
			}
			#OUTOFDATETAB TD
			{
				text-align:center;
			}
		</style>
<?php
	}
	// ====================================================================
	// printInterfaceJS() : generer le code js de l'interface 'graphic view'
	// ====================================================================
	public function printInterfaceJS()
	{
		global $parm;
?>
		<script type="text/javascript">
		function calccmd()
		{
			var cmd = '';
			with(document.forms['graphicForm'])
			{
				cmd += "";
				if((coll0.value||status0.value) && (coll1.value||status1.value))
				{
					cmd += "UPDATE record SET ";
					u = "";
					if(coll1.value)
						u += (u?", ":"") + "coll_id=" + coll1.value;
					if(status1.value)
					{
						x = status1.value.split("_");
						if(x[1]=="0")
							u += (u?", ":"") + "status=status&~(1<<" + x[0] + ")";
						else
							u += (u?", ":"") + "status=status|(1<<" + x[0] + ")";
					}
					cmd += u;
					w = "";
					if(coll0.value)
						w += (w?" AND ":"") + "coll_id=" + coll0.value;
					if(status0.value)
					{
						x = status0.value.split("_");
						if(x[1]=="0")
							w += (w?" AND ":"") + "(status>>" + x[0] + ")&1=0";
						else
							w += (w?" AND ":"") + "(status>>" + x[0] + ")&1=1";
					}
					cmd += " WHERE " + w;
				}
			}
			document.getElementById('cmd').innerHTML = cmd;
		}
		
		function chgxmltxt(textinput, fieldname)
		{
			var limits = { 'period':{min:1, 'max':1440} , 'delay':{min:0} } ;
			if(typeof(limits[fieldname])!='undefined')
			{
				var v = 0|textinput.value;
				if(limits[fieldname].min && v < limits[fieldname].min)
					v = limits[fieldname].min;
				else if(limits[fieldname].max && v > limits[fieldname].max)
					v = limits[fieldname].max;
				textinput.value = v;
			}
			setDirty();
			calccmd();
		}
		function chgxmlck(checkinput, fieldname)
		{
			setDirty();
			calccmd();
		}
		function chgxmlpopup(popupinput, fieldname)
		{
 			setDirty();
 			calccmd();
		}
		function chgsbas(sbaspopup)
		{
			var xmlhttp = new XMLHttpRequest_with_xpath();
			xmlhttp.open("POST", "/admin/taskfacility.php", false);
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			xmlhttp.send("cls=outofdate&bid="+sbaspopup.value);

			for(fld=0; fld<=1; fld++)
			{
				var p = document.getElementById("status"+fld);
				while( (f=p.firstChild) )
					p.removeChild(f);
				var t = xmlhttp.xpathSearch('/result/status_bits/bit');
				if(t.length > 0)
				{
					var o = p.appendChild(document.createElement('option'));
					o.setAttribute('value', '');
					o.appendChild(document.createTextNode("..."));
					for(i in t)
					{
						var o = p.appendChild(document.createElement('option'));
						o.setAttribute('value', t[i].getAttribute("n")+"_"+t[i].getAttribute("value"));
						o.appendChild(document.createTextNode(t[i].firstChild.nodeValue));
						o.setAttribute('class', "jsFilled");
					}
				}
			}

			for(fld=0; fld<=1; fld++)
			{
				var p = document.getElementById("coll"+fld);
				while( (f=p.firstChild) )
					p.removeChild(f);
				var t = xmlhttp.xpathSearch('/result/collections/collection');
				if(t.length > 0)
				{
					var o = p.appendChild(document.createElement('option'));
					o.setAttribute('value', '');
					o.appendChild(document.createTextNode("..."));
					for(i in t)
					{
						var o = p.appendChild(document.createElement('option'));
						o.setAttribute('value', t[i].getAttribute("id"));
						o.appendChild(document.createTextNode(t[i].firstChild.nodeValue));
						o.setAttribute('class', "jsFilled");
					}
				}
			}
			delete xmlhttp;
			calccmd();
 			// setDirty();
		}
		
		function XMLHttpRequest_with_xpath()
		{
			var x = new XMLHttpRequest();
			x.xpathSearch = function(xpath) {
				var t = new Array();
				if(x.responseXML.evaluate)
				{
					var tmp = x.responseXML.evaluate(xpath, x.responseXML, null, 4, null);
					var i;
					while(i = tmp.iterateNext())
						t.push(i);
				}
				else if(typeof(x.responseXML.selectNodes))
				{
					var tmp = x.responseXML.selectNodes(xpath);
					for(var i=0; i<tmp.length; i++)
						t.push(tmp.item(i));
				}
				return(t);				
			};
			return(x);
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

		$conn = connection::getInstance();
		
		$sql = 'SELECT sbas.sbas_id, dbname, viewname FROM sbas INNER JOIN sbasusr ON sbasusr.sbas_id=sbas.sbas_id AND usr_id=\''.$usr_id.'\' AND bas_manage>0 ORDER BY ord';
		if($rs = $conn->query($sql))
		{
?>
		<form name="graphicForm" onsubmit="return(false);" method="post">
			<?php echo _('task::outofdate:Base')?>&nbsp;:&nbsp;
			
			<select onchange="chgsbas(this);setDirty();" name="sbas_id">
				<option value="">...</option>
<?php
			while($row = $conn->fetch_assoc($rs))
			{
				if($row['viewname'])
					$row['dbname'] .= (' ('.$row['viewname'].')');
				//$selected = ($sbas_id!==null && $sbas_id==$row["sbas_id"]) ? "selected" : "";
				$selected = '';
				print("\t\t\t\t<option value=\"".$row["sbas_id"]."\" $selected>" . p4string::MakeString($row["dbname"], "form") . "</option>\n");
			}
			$conn->free_result($rs);
?>
			</select>
			
			&nbsp;
			
			<br/>
			<br/>
			
			<?php echo _('task::_common_:periodicite de la tache')?>&nbsp;:&nbsp;
			<input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
			<?php echo _('task::_common_:minutes (unite temporelle)')?><br/>
			<br/>

			<table id="OUTOFDATETAB" style="margin-right:10px; ">
				<tr>
					<td style="white-space:nowrap;">
						Collection&nbsp;:
					</td>
					<td>
						<select name="coll0" id="coll0" onchange="chgxmlpopup(this, 'coll0');"></select>
					</td>
					<td rowspan="2">
						&nbsp;&nbsp;====&gt;&nbsp;&nbsp;
					</td>
					<td>
						<select name="coll1" id="coll1" onchange="chgxmlpopup(this, 'coll1');"></select>
					</td>
				</tr>
				<tr>
					<td style="white-space:nowrap;">
						Status&nbsp;:
					</td>
					<td>
						<select name="status0" id="status0" onchange="chgxmlpopup(this, 'status0');"></select>
					</td>
					<td>
						<select name="status1" id="status1" onchange="chgxmlpopup(this, 'status1');"></select>
					</td>
				</tr>
			</table>
		</form>
		<br>
		<center>
			<div style="margin:10px; padding:5px; border:1px #000000 solid; font-family:monospace; font-size:16px; text-align:left; color:#00e000; background-color:#404040" id="cmd">cmd</div>
		</center>
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
		return(_("task::outofdate:deplacement de docs suivant valeurs de champs 'date'"));
	}
		 			
		 			
	// ======================================================================================================
	// ===== run() : le code d'execution de la tache proprement dite
	// ======================================================================================================
	
	private $sxTaskSettings = null;	// les settings de la tache en simplexml
	private $connbas = null;		// cnx a la base
	private $running = false;
	private $msg = "";
	
	function run()
	{
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
		
		$this->sxBasePrefs = null;

		$sbas_id = (int)($this->sxTaskSettings->sbas_id);

		$conn = connection::getInstance();
		
		$this->connbas = connection::getInstance($sbas_id);

		$this->running = true;
		$this->tmask = array();	
		$this->tmaskgrp = array();	 
		$this->period = 60;
		
	
		// ici la t�che tourne tant qu'elle est active
		$last_exec = 0;
                $loop = 0;
		while($conn && $this->running)
		{
			$sql = "UPDATE task2 SET last_exec_time=NOW() WHERE task_id=" . $this->taskid ;
			$conn->query($sql);
	
//			// on lit les prefs de cette base
			$this->sxBasePrefs = databox::get_sxml_structure($sbas_id);//$rowbas["struct"]);
			
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
							$period = (int)($this->sxTaskSettings->period);
							if($period <= 0 || $period >= 24*60)
								$period = 60;
						}
						else
						{
							$period = 60;
						}
						$this->connbas = connection::getInstance($sbas_id);
						
						$now = time();
						if($now-$last_exec >= $period*60)	// period est en minutes
						{

							$r = $this->doRecords();
							// printf("line %s r=%s\n", __LINE__, $r);
					
							if($r == 'NORECSTODO')
							{
								$last_exec = $now;
							}		
							else
							{
								// on a trait� des records, on restart (si on a �t� lanc� par le scheduler)
								if($row['status'] == 'started')
								{
									// ask for wakeup by scheduler
									$ret = 'torestart';
									$this->running = false;
								}
							}
							
							if($loop > 5 || memory_get_usage()>>20 >= 15)
							{
								$ret = 'torestart';
								$this->running = false;
							}	
						}
						else
						{
							$conn->close();
							$this->connbas->close();
							unset($conn);
							sleep(5);
							$conn = connection::getInstance();
							$this->connbas = connection::getInstance($sbas_id);
						}
					}
				}
				else
				{
					$ret = 'stopped';
					$this->running = false;
				}
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
	
	function doRecords()
	{
		$ndone = 0;
		$ret = 'NORECSTODO';
		$nchanged = 0;
		
		$coll = array();
		$stat = array();
		for($i=0; $i<=1; $i++)
		{
			$coll[$i] = trim((string)($this->sxTaskSettings->{'coll'.$i}));
			$stat[$i] = trim((string)($this->sxTaskSettings->{'status'.$i}));
		}
		
		if(($coll[0] || $stat[0]) && ($coll[1] || $stat[1]))
		{
			$u = '';
			if($coll[1])
				$u .= ($u?', ':'') . 'coll_id=\'' . $this->connbas->escape_string($coll[1]) . '\'';
			if($stat[1])
			{
				$x = explode('_', $stat[1]);
				if($x[1]=='0')
					$u .= ($u?', ':'') . 'status=status&~(1<<' . $x[0] . ')';
				else
					$u .= ($u?', ':'') . 'status=status|(1<<' . $x[0] . ')';
			}
			$w = '';
			if($coll[0])
				$w .= ($w?' AND ':'') . 'coll_id=\'' . $this->connbas->escape_string($coll[0]) . '\'';
			if($stat[0])
			{
				$x = explode('_', $stat[0]);
				if($x[1]=='0')
					$w .= ($w?' AND ':'') . '(status>>' . $x[0] . ')&1=0';
				else
					$w .= ($w?' AND ':'') . '(status>>' . $x[0] . ')&1=1';
			}
			$sql = 'UPDATE record SET ' . $u . ' WHERE ' . $w;
		var_dump($sql);
			
			$this->connbas->query($sql);
			$nchanged += ($n=$this->connbas->affected_rows());
			if($n > 0)
				$this->log(sprintf(("SQL=%s\n - %s changes"), $sql, $n));
		}
		
		$ret = ($nchanged > 0 ? $nchanged : 'NORECSTODO');
			
		return($ret);
	}
	
	public function facility()
	{
		//global $parm;
		$request = httpRequest::getInstance();
		
		$parm2 = $request->get_parms(
							  "bid"
						);
						
		header("Content-Type: text/xml; charset=UTF-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");                          // HTTP/1.0
		
		$ret = new DOMDocument("1.0", "UTF-8");
		$ret->standalone = true;
		$ret->preserveWhiteSpace = false;
		
		$element = $ret->createElement('result');
		
		$root = $ret->appendChild($element);
		$root->appendChild($ret->createCDATASection( var_export($parm2, true) ));
		$dfields =  $root->appendChild($ret->createElement("date_fields"));
		$statbits =  $root->appendChild($ret->createElement("status_bits"));
		$coll =  $root->appendChild($ret->createElement("collections"));
					
		$xml = NULL;				
		if($parm2["bid"] !== null)
		{			
			$conn = connection::getInstance();
			$connbas = connection::getInstance($parm2['bid']);
			if($connbas->isok())
			{
				$sxBasePrefs = databox::get_sxml_structure($parm2['bid']);
				if($sxBasePrefs)
				{							
					foreach($sxBasePrefs->description->children() as $fname=>$field)
					{
						if(mb_strtolower($field['type']) == 'date')
							$dfields->appendChild($ret->createElement("field"))->appendChild($ret->createTextNode($fname));
					}
					foreach($sxBasePrefs->statbits->bit as $sbit)
					{
						if( ($n=(int)($sbit['n'])) >= 4)
						{
							$labelOn = (string)$sbit;
							if($sbit['labelOn'] != '')
								$labelOn = $sbit['labelOn'];
							$labelOff = 'non-' . $labelOn;
							if($sbit['labelOff'] != '')
								$labelOff = $sbit['labelOff'];
							$node = $statbits->appendChild($ret->createElement("bit"));
							$node->setAttribute('n', $n);
							$node->setAttribute('value', '0');
							$node->setAttribute('label', $labelOff);
							$node->appendChild($ret->createTextNode($labelOff));
							$node = $statbits->appendChild($ret->createElement("bit"));
							$node->setAttribute('n', $n);
							$node->setAttribute('value', '1');
							$node->setAttribute('label', $labelOn);
							$node->appendChild($ret->createTextNode($labelOn));
						}
					}
				}
				
				$sql = "SELECT coll_id, asciiname FROM coll";
				if($rsbas = $connbas->query($sql))
				{
					while($rowbas = $conn->fetch_assoc($rsbas))
					{
						$node = $coll->appendChild($ret->createElement("collection"));
						$node->setAttribute('id', $rowbas['coll_id']);
						
						$name = trim($rowbas['asciiname']) != '' ? $rowbas['asciiname'] : 'Untitled';
						
						$node->appendChild($ret->createTextNode($name));
					}
					$connbas->free_result($rsbas);
				}
			}
		}
		
		print($ret->saveXML());
	}	
}

?>