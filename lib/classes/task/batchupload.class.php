<?php


class task_batchupload extends phraseatask
{
	// ======================================================================================================
	// ===== les interfaces de settings (task2.php) pour ce type de tache
	// ======================================================================================================

	// ====================================================================
	// getName() : must return the name of this kind of task (utf8), MANDATORY
	// ====================================================================
	public function getName()
	{
		return(utf8_encode("Batch upload process (XML Service)"));
	}

	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	/*
	public function graphic2xml($oldxml)
	{
		return($oldxml);
	}
	*/

	// ====================================================================
	// xml2graphic : must fill the graphic form (using js) from xml
	// ====================================================================
	/*
	public function xml2graphic($xml, $form)
	{
		return(true);
	}
	*/

	// ====================================================================
	// printInterfaceJS() : print the js part of the graphic view
	// ====================================================================
	/*
	public function printInterfaceJS()
	{
	}
	*/

	// ====================================================================
	// printInterfaceHTML(..) : print the html part of the graphic view
	// ====================================================================
	/*
	public function printInterfaceHTML()
	{
?>
		<form name="graphicForm" onsubmit="return(false);">
		</form>
<?php
	}
	*/

	// ====================================================================
	// getGraphicForm() : must return the name graphic form to submit
	// if not implemented, assume 'graphicForm'
	// ====================================================================
	/*
	public function getGraphicForm()
	{
		return('graphicForm');
	}
	*/



	// ====================================================================
	// $argt : command line args specifics to this task
	// ====================================================================
	/*
	public $argt = array(
		 			);
	*/

	// ======================================================================================================
	// help() : text displayed if --help
	// ======================================================================================================
	public function help()
	{
		return(utf8_encode("Hello I'm the batch upload process."));
	}





	// ======================================================================================================
	// run() : the real code executed by each task, MANDATORY
	// ======================================================================================================

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

	function run2()
	{
		$conn = connection::getInstance();
		$ret = '';
		$this->running = true;
		$taskStatus = '';
                $loop = 0;
		while($conn && $this->running)
		{
			$sql = "UPDATE task2 SET last_exec_time=NOW() WHERE task_id='" . $conn->escape_string($this->taskid)."'" ;
			$conn->query($sql);

			$sql = "SELECT status FROM task2 WHERE task_id='" . $conn->escape_string($this->taskid)."'" ;
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$taskStatus = $row['status'];

					if($taskStatus == 'tostop')
					{
						$ret = 'stopped';
						$this->running = false;
					}
					else
					{
						// flag bad batches (bad coll_number)
						$sql = 'UPDATE uplfile AS f INNER JOIN uplbatch AS u USING(uplbatch_id) SET f.error="1", u.error="1"'
							. ' WHERE u.error=0 AND u.base_id NOT IN(SELECT base_id FROM bas)';
						$conn->query($sql);

						// work on good batches
						$sql = 'SELECT uplbatch_id, sbas_id, server_coll_id, usr_id FROM (uplbatch u INNER JOIN bas b USING(base_id)) WHERE complete="1" AND error="0" ORDER BY uplbatch_id';

						if($rs2 = $conn->query($sql))
						{
							while($row = $conn->fetch_assoc($rs2))
							{
								$this->log(sprintf(('processing batch %s'), $row['uplbatch_id']));

								if(($action = @unserialize($row['action'])) === false)
									$action = '';

								$batch = $this->processBatch($row['usr_id'], $row['uplbatch_id'], $row['sbas_id'], $row['server_coll_id'], $action);

								$sql = 'UPDATE uplbatch SET complete="2", error="'.$batch['error'].'" WHERE uplbatch_id="'.$conn->escape_string($row['uplbatch_id']).'"';
								$conn->query($sql);

								$this->log(sprintf(('finishing batch %s'), $row['uplbatch_id']));
							}
							$conn->free_result($rs2);
						}
					}
				}
				$conn->free_result($rs);
			}
			$conn->close();
			unset($conn);
			sleep(10);
			$conn = connection::getInstance();

			if($loop > 5 || memory_get_usage()>>20 >= 15)
			{
				$ret = 'torestart';
				$this->running = false;
			}
                        $loop++;
		}
		return($ret);
	}

	function processBatch($usr_id, $batch_id, $sbas_id, $cid, $action)
	{
		$batch = array('error'=>'0', 'records'=>array());

		$conn = connection::getInstance();
		$session = session::getInstance();

		$connbas = NULL;
		$sxBasePrefs = NULL;
		$path = NULL;

		try
		{
			// check tmp dir
			$path = GV_RootPath.'tmp/batches/'.$batch_id.'/';
			if(!is_dir($path))
				throw new Exception(sprintf(('Batch directory \'%s\' does not exist'), $path));

			// check sbas
			$connbas = connection::getInstance($sbas_id);
			if(!$connbas || !$connbas->isok())
				throw new Exception(sprintf(('sbas(id=%s) is unavailable'), $sbas_id));

			// get struct
			$sxBasePrefs = databox::get_sxml_structure($sbas_id);
			if(!$sxBasePrefs)
				throw new Exception(sprintf(('Can\'t get structure of sbas(id=%s)'), $sbas_id));
		}
		catch(Exception $e)		// abort if something is wrong
		{
			$this->log($e->getMessage());

			$sql = 'UPDATE uplfile AS f INNER JOIN uplbatch AS u USING(uplbatch_id) SET f.error="1", u.error="1"'
				. ' WHERE u.uplbatch_id=\''.$conn->escape_string($batch_id).'\'';
			$conn->query($sql);
			$batch['error'] = '1';
			return $batch;
		}

		//on cree une session artificielle quon va detruire

		p4::authenticate($usr_id);



		// do the job
		$sql = 'SELECT * FROM uplfile WHERE uplbatch_id=\''.$conn->escape_string($batch_id).'\' ORDER BY idx';
		if($rs = $conn->query($sql))
		{
			if( ($nfiles = $conn->num_rows($rs)) > 0)
			{
				$rid = $connbas->getId("RECORD", $nfiles);

				while($row = $conn->fetch_assoc($rs))
				{
					$this->log(sprintf(('archiving file \'%s\''), $row['filename']));

					$file = $row['idx'];
					$mimeExt = giveMimeExt($path.'/'.$file);
					$propfile = array(
										'recordid' => $rid,
										'extension' => $mimeExt['ext'],
										'mime' => $mimeExt['mime'],
										'size' => sprintf('%u', filesize($path.'/'.$file)),
										'subpath' => '',
										'parentdirectory' => '',
										'hotfolderfile' => $path.'/'.$file,
										'hotfoldercaptionfile' => NULL,
										'pathhd' => '',
										'file' => '',
										'originalname' => $row['filename'],
										'inbase' => 0,
										'type' => giveMeDocType($mimeExt['mime'])
									);

					$meta = get_xml($sxBasePrefs, $propfile);

					$stat0 = $stat1 = '0';

					$file_uuid = new uuid($path.'/'.$file);
					$uuid = $file_uuid->check_uuid();

					// ... flag the record as 'to reindex' (status-bits 0 and 1 to 0) AND UNLOCK IT (status-bit 2 to 1)
					// ... and flag it for subdef creation (jeton bit 0 to 1)
					$fl = 'coll_id, record_id, parent_record_id, status, jeton, moddate, credate, xml, type, sha256, uuid';
					$vl = $cid . ', ' .$rid . ', ' . '0' . ', (((' . $stat0 . ' | ' . $stat1 . ') & ~0x0F) | 0x0C), '.JETON_READ_META_DOC_MAKE_SUBDEF.', NOW(), NOW(), \'' . $connbas->escape_string($meta['xml']->saveXML()) . '\', \'' . $connbas->escape_string($propfile['type']) . '\', \''.hash_file('sha256',$propfile["hotfolderfile"]).'\', "'.$connbas->escape_string($uuid).'"';
					$sql = 'INSERT INTO record ('.$fl.') VALUES ('.$vl.')';

					if($connbas->query($sql))
					{
						$copyhd = (bool)($sxBasePrefs->copy_hd);

						$documentisok = false;
						if($copyhd)
						{
							// archiving with copy into base
							$pathhd = trim((string)($sxBasePrefs->path));
							$pathhd = p4::dispatch($pathhd);

							$newname = $rid . "_document.".$propfile["extension"];
							if(copy($propfile["hotfolderfile"], $pathhd . $newname))
							{
								$propfile['pathhd'] = $pathhd;
								$propfile['file'] = $newname;
								$propfile['inbase'] = 1;

								$fl  = 'record_id';		$vl  = $rid;
								$fl .= ', name';		$vl .= ", 'document'";
								$fl .= ', path';		$vl .= ", '" . $connbas->escape_string($propfile['pathhd']) . "'";
								$fl .= ', file';		$vl .= ", '" . $connbas->escape_string($propfile['file']) . "'";
								$fl .= ', inbase';		$vl .= ", '" . $propfile['inbase'] . "'";
								if(isset($propfile['width']))
								{
									$fl .= ', width';		$vl .= ", '" . $connbas->escape_string($propfile['width']) . "'";
								}
								if(isset($propfile['height']))
								{
									$fl .= ', height';		$vl .= ", '" . $connbas->escape_string($propfile['height']) . "'";
								}
								$fl .= ', mime';		$vl .= ", '" . $connbas->escape_string($propfile['mime']) . "'";
								$fl .= ', size';		$vl .= ", '" . $connbas->escape_string($propfile['size']) . "'";
								$fl .= ', dispatched';		$vl .= ", '1'";
								$sql = "INSERT INTO subdef (".$fl.") VALUES (".$vl.")";

								if($connbas->query($sql))
								{

									if(isset($session->logs[$sbas_id]))
									{
										$sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment) VALUES (null, "'.$connbas->escape_string($session->logs[$sbas_id]).'", now(), "'.$connbas->escape_string($rid).'", "add", "'.$connbas->escape_string($cid).'","")';
										$connbas->query($sql);
									}

								}
							}



							@unlink($propfile["hotfolderfile"]);
						}
						else
						{
							// archiving 'in place'
						}

						$batch['records'][] = $rid;
					}

					$rid++;
				}

			}
			$conn->free_result($rs);
		}
		@p4::logout();

		rmdir($path);
		return $batch;
	}


}

?>