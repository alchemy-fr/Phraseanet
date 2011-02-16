<?php

class task_cindexer extends phraseatask
{
	// ====================================================================
	// getName : must return the name for this kind of task
	// MANDATORY
	// ====================================================================
	public function getName()
	{
		return(_("task::cindexer:Indexation"));
	}
	
	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	public function graphic2xml($oldxml)
	{
//		global $parm;
		$request = httpRequest::getInstance();
		
		$parm2 = $request->get_parms(
							  'binpath', 'host', 'port', 'base', 'user', 'password', 'socket', 'use_sbas', 'nolog', 'clng', 'winsvc_run', 'charset'
				);
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		if( $dom->loadXML($oldxml) )
		{
			$xmlchanged = false;
			foreach(array("str:binpath", "str:host", "str:port", "str:base", "str:user", "str:password", "str:socket", "boo:use_sbas", "boo:nolog", "str:clng", "boo:winsvc_run", "str:charset") as $pname)
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
	// xml2graphic : must fill the grapic form (using js) from xml
	// ====================================================================
	public function xml2graphic($xml, $form)
	{
		if( ($sxml = simplexml_load_string($xml)) )	// in fact XML IS always valid here...
		{
?>
		<script type="text/javascript">
			<?php echo $form?>.binpath.value      = "<?php echo p4string::MakeString($sxml->binpath, "js", '"')?>";
			<?php echo $form?>.host.value         = "<?php echo p4string::MakeString($sxml->host, "js", '"')?>";
			<?php echo $form?>.port.value         = "<?php echo p4string::MakeString($sxml->port, "js", '"')?>";
			<?php echo $form?>.base.value         = "<?php echo p4string::MakeString($sxml->base, "js", '"')?>";
			<?php echo $form?>.user.value         = "<?php echo p4string::MakeString($sxml->user, "js", '"')?>";
			<?php echo $form?>.socket.value       = "<?php echo p4string::MakeString($sxml->socket, "js", '"')?>";
			<?php echo $form?>.password.value     = "<?php echo p4string::MakeString($sxml->password, "js", '"')?>";
			<?php echo $form?>.clng.value         = "<?php echo p4string::MakeString($sxml->clng, "js", '"')?>";
			<?php echo $form?>.use_sbas.checked   = <?php echo trim((string)$sxml->use_sbas) != '' ? (p4field::isyes($sxml->use_sbas) ? 'true':'false') : 'true'?>;
			<?php echo $form?>.nolog.checked      = <?php echo p4field::isyes($sxml->nolog) ? 'true':'false'?>;
			<?php echo $form?>.winsvc_run.checked = <?php echo p4field::isyes($sxml->winsvc_run) ? 'true':'false'?>;
			<?php echo $form?>.charset.value      = "<?php echo trim((string)$sxml->charset) != '' ? p4string::MakeString($sxml->charset, "js", '"') : 'utf8'?>";
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
	
	public function checkXML($xml)
	{
		return("");
	}
	
	// ====================================================================
	// printInterfaceJS() : g�n�rer le code js de l'interface 'graphic view'
	// ====================================================================
	public function printInterfaceJS()
	{
		global $parm;
		$appname = 'phraseanet_indexer';
		if($this->system == 'WINDOWS')
			$appname .= '.exe';
?>
		<script type="text/javascript">
		function calccmd()
		{
			var cmd = '';
			with(document.forms['graphicForm'])
			{
				cmd += binpath.value + "/<?php echo $appname?>";
				if(host.value)
					cmd += " -h=" + host.value;
				if(port.value)
					cmd += " -P=" + port.value;
				if(base.value)
					cmd += " -b=" + base.value;
				if(user.value)
					cmd += " -u=" + user.value;
				if(password.value)
					cmd += " -p=" + password.value;
				if(socket.value)
					cmd += " --socket=" + socket.value;
				if(charset.value)
					cmd += " --default-character-set=" + charset.value;
				if(use_sbas.checked)
					cmd += " -o";
				if(nolog.checked)
					cmd += " -n";
				if(clng.value)
					cmd += " -c=" + clng.value;
				if(winsvc_run.checked)
					cmd += " --run";
			}
			document.getElementById('cmd').innerHTML = cmd;
		}
		function chgxmltxt(textinput, fieldname)
		{
			calccmd();
			setDirty();
		}
		function chgxmlck(checkinput, fieldname)
		{
			calccmd();
			setDirty();
		}
		function chgxmlpopup(popupinput, fieldname)
		{
			calccmd();
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
		global $parm;
		$appname = 'phraseanet_indexer';
		if($this->system == 'WINDOWS')
			$appname .= '.exe';
?>
		<form name="graphicForm" onsubmit="return(false);">
			<br/>
			<?php echo _('task::cindexer:executable')?>&nbsp;:&nbsp;
			<input type="text" name="binpath" style="width:300px;" onchange="chgxmltxt(this, 'binpath');" value="">&nbsp;/&nbsp;<?php echo $appname?>
			<br/>
			<?php echo _('task::cindexer:host')?>&nbsp;:&nbsp;<input type="text" name="host" style="width:100px;" onchange="chgxmltxt(this, 'host');" value="">
			<br/>
			<?php echo _('task::cindexer:port')?>&nbsp;:&nbsp;<input type="text" name="port" style="width:100px;" onchange="chgxmltxt(this, 'port');" value="">
			<br/>
			<?php echo _('task::cindexer:base')?>&nbsp;:&nbsp;<input type="text" name="base" style="width:200px;" onchange="chgxmltxt(this, 'base');" value="">
			<br/>
			<?php echo _('task::cindexer:user')?>&nbsp;:&nbsp;<input type="text" name="user" style="width:200px;" onchange="chgxmltxt(this, 'user');" value="">
			<br/>
			<?php echo _('task::cindexer:password')?>&nbsp;:&nbsp;<input type="text" name="password" style="width:200px;" onchange="chgxmltxt(this, 'password');" value="">
			<br/>
			<br/>
			
			<?php echo _('task::cindexer:control socket')?>&nbsp;:&nbsp;<input type="text" name="socket" style="width:200px;" onchange="chgxmltxt(this, 'socket');" value="">
			<br/>
			<br/>
			
			<div style="display:none;">
				<input type="checkbox" name="use_sbas" onclick="chgxmlck(this, 'old');">&nbsp;<?php echo _('task::cindexer:use table \'sbas\' (unchecked: use \'xbas\')')?>
				<br/>
			</div>
			
			<?php echo _('task::cindexer:MySQL charset')?>&nbsp;:&nbsp;<input type="text" name="charset" style="width:200px;" onchange="chgxmltxt(this, 'charset');" value="">
			<br/>
			<br/>
			
			<input type="checkbox" name="nolog" onclick="chgxmlck(this, 'nolog');">&nbsp;<?php echo _('task::cindexer:do not (sys)log, but out to console)')?>
			<br/>
			
			<?php echo _('task::cindexer:default language for new candidates')?>&nbsp;:&nbsp;<input type="text" name="clng" style="width:50px;" onchange="chgxmltxt(this, 'clng');" value="">
			<br/>
			<br/>
			
			<hr/>
			
			<br/>
			<?php echo _('task::cindexer:windows specific')?>&nbsp;:<br/>
			<input type="checkbox" name="winsvc_run" onclick="chgxmlck(this, 'run');">&nbsp;<?php echo _('task::cindexer:run as application, not as service')?>
			<br/>
			
		</form>
		<br>
		<center>
			<div style="margin:10px; padding:5px; border:1px #000000 solid; font-family:monospace; font-size:16px; text-align:left; color:#00e000; background-color:#404040" id="cmd">cmd</div>
		</center>
<?php
	}
	
	
	// ====================================================================
	// $argt : command line args specifics to this task (optional)
	// ====================================================================
	public $argt = array(
		 			);

	// ======================================================================================================
	// ===== help() : text displayed if --help (optional)
	// ======================================================================================================
	function help()
	{
		return(_("task::cindexer:indexing records"));
	}
		 			
		 			
	// ======================================================================================================
	// ===== run() : le code d'�x�cution de la t�che proprement dite
	// ======================================================================================================

	function run()
	{
		
		// task can't be stopped here
		
		$conn = connection::getInstance();
		
		$sxsettings = 
		$sql = "SELECT settings FROM task2 WHERE task_id=" . $this->taskid ;
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$sxsettings = simplexml_load_string($row['settings']);
			$conn->free_result($rs);
		}
		
		if(!$sxsettings)
			return;
		
		// $cmd = p4string::addEndSlash($sxsettings->binpath) . 'phraseanet_indexer';
		$cmd = 'phraseanet_indexer';
		if($this->system == 'WINDOWS')
		{
			$cmd .= '.exe';
			$nulfile = 'nul';
		}
		else
		{
			$nulfile = '/dev/null';
		}
			
		if(!file_exists(p4string::addEndSlash($sxsettings->binpath) . $cmd))
		{
			$this->log(sprintf(_('task::cindexer:file \'%s\' does not exists'), p4string::addEndSlash($sxsettings->binpath).$cmd));
			return;
		}

		if((string)($sxsettings->host) != '')
			$cmd .= " -h=" . $sxsettings->host;
		if((string)($sxsettings->port) != '')
			$cmd .= " -P=" . $sxsettings->port;
		if((string)($sxsettings->base) != '')
			$cmd .= " -b=" . $sxsettings->base;
		if((string)($sxsettings->user) != '')
			$cmd .= " -u=" . $sxsettings->user;
		if((string)($sxsettings->password) != '')
			$cmd .= " -p=" . $sxsettings->password;
		if((string)($sxsettings->socket) != '')
			$cmd .= " --socket=" . $sxsettings->socket;
		if(p4field::isyes((string)($sxsettings->use_sbas)))
			$cmd .= " -o";
		if((string)($sxsettings->charset) != '')
			$cmd .= " --default-character-set=" . $sxsettings->charset;
		if(p4field::isyes((string)($sxsettings->nolog)))
			$cmd .= " -n";
		if(p4field::isyes((string)($sxsettings->winsvc_run)))
			$cmd .= " --run";

		
		$logdir = p4string::addEndSlash(GV_RootPath.'logs');
		
		$syslog = false;

		if(!is_dir($logdir))
		{
			$logdir  = null;
		}

		$descriptors = array();
		if($logdir)
		{
			$descriptors[1] = array("file", $logdir . "/phraseanet_indexer_".$this->taskid.".log", "a+");
			$descriptors[2] = array("file", $logdir . "/phraseanet_indexer_".$this->taskid.".error.log", "a+");
		}
		else
		{
			$descriptors[1] = array("file", $nulfile, "a+");
			$descriptors[2] = array("file", $nulfile, "a+");
		}

		$pipes = array();
		
		if($this->system == 'WINDOWS')
			$cmd = '' . p4string::addEndSlash($sxsettings->binpath) . $cmd;
		else
			$cmd = '' . p4string::addEndSlash($sxsettings->binpath) . $cmd;
			
		$this->log(sprintf('cmd=\'%s\'', $cmd));
		
		$process = proc_open($cmd, $descriptors, $pipes, $sxsettings->binpath, null, array('bypass_shell'=>true) );
							
		$pid = NULL;
		if(is_resource($process))
		{
			$proc_status = proc_get_status($process);
			if($proc_status['running'])
				$pid = $proc_status['pid'];
		}
		
		
		$running = true;
		$qsent = '';
		$timetokill = NULL;
		$sock = NULL;
		while($running)
		{
			$sql = "SELECT status FROM task2 WHERE status='tostop' AND task_id=" . $this->taskid ;
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					// must quit task, so send 'Q' to cindexer
					$socket = 0 + ((string)($sxsettings->socket));
					if($socket > 0)
					{
						// must quit task, so send 'Q' to port 127.0.0.1:XXXX to cindexer
						if(!$qsent && (($sock = @socket_create(AF_INET, SOCK_STREAM, 0)) !== false))
						{
							if(@socket_connect($sock, '127.0.0.1', $socket) === true)
							{
								socket_write($sock, 'Q', 1);
								socket_write($sock, "\r\n", strlen("\r\n"));
								sleep(5);
								$qsent = 'Q';
								$timetokill = time()+10;
							}
							else
							{
								@socket_close($sock);
								$sock = NULL;								
							}
						}
					}
				}
				$conn->free_result($rs);
			}

			$proc_status = proc_get_status($process);
			if(!$proc_status['running'])
			{
				// the cindexer died
				if($qsent == 'Q')
					$this->log(_('task::cindexer:the cindexer clean-quit'));
				elseif($qsent == 'K')
					$this->log(_('task::cindexer:the cindexer has been killed'));
				else
					$this->log(_('task::cindexer:the cindexer crashed'));
				$running = false;
			}
			else
			{
				if($qsent == 'Q')
				{
					if(time() > $timetokill)
					{
						// must kill cindexer
						$this->log(_('task::cindexer:killing the cindexer'));
						$qsent = 'K';
						proc_terminate($process);	// sigint
					}
				}
			}
			sleep(5);
		}
		
		if($sock)
		{
			@socket_close($sock);
			$sock = NULL;
		}

		@fclose($pipes[1]);
		@fclose($pipes[2]);
		proc_close($process);
		
		return('stopped');
	}
}

?>