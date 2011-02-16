<?php
	$session = session::getInstance();
	$mdesc = '<' . '?xml version="1.0" encoding="UTF-8" standalone="yes" ?' . '><mdesc>' . $parm['mds'] . '</mdesc>';
	$mdesc = DOMDocument::loadXML($mdesc);
	if($mdesc)
	{
		$connbas = connection::getInstance($parm['sbid']);
		
		if($connbas)		
		{
			$sx_struct = null; // la structure de la base en simplexml
			$tstruct = array();
			
			$edidate = date('Y/m/d H:i:s', time());
			$trecchanges0 = array();			// auto changed fields, for every record
			
			if( ($sx_struct = databox::get_sxml_structure($parm['sbid'])) !== false)
			{
				// load struct in php
				foreach($sx_struct->description->children() as $fname=>$field)
				{
					$src       = (string)($field['src']);
					$type      = (string)($field['type']);
				
					$format = $explain = "";
					if($type != "")
					{
						switch($type)
						{
							case 'datetime':
								$format  = _('phraseanet::technique::datetime-edit-format');
								$explain = _('phraseanet::technique::datetime-edit-explain');
								break;
							case 'date':
								$format  = _('phraseanet::technique::date-edit-format');
								$explain = _('phraseanet::technique::date-edit-explain');
								break;
							case 'time':
								$format  = _('phraseanet::technique::time-edit-format');
								$explain = _('phraseanet::technique::time-edit-explain');
								break;
						}
					}
					
					$separator = mb_strtolower((string)($field['separator']));
					if(strpos($separator, ';')===false)	// le ';' est le separator par defaut, et un separator obligatoire
							$separator .= ';';
					
					if($src == 'tf-editdate')
						$trecchanges0[$fname] = array('values'=>array($edidate));
					$tstruct[$fname] = array();
					$tstruct[$fname]['src']       = $src;
					$tstruct[$fname]['type']      = $type;
					$tstruct[$fname]['format']    = $format;
					$tstruct[$fname]['multi']     = p4field::isyes((string)($field['multi'])) || $src=='ip-keyword' || $src=='ip-suppcat' || $src=='Keywords';
					$tstruct[$fname]['separator'] = $separator;	// l'attribut "separator" du champ
					
				}
			}

			$ridschanged = array();	// la liste des records a reindexer
			
			$xp_mdesc = new DOMXPath($mdesc);
			
			// on scanne toutes les fiches contenues dans ce mdesc
			$recmdesc = $xp_mdesc->query('/mdesc/record');
			// on compte le nombre de recs a traiter
			$ndesc = 0;
			for($ndesc = 0; $desc = $recmdesc->item($ndesc); $ndesc++)
				;
			// on boucle sur les recs
			for($idesc = 0; $desc = $recmdesc->item($idesc); $idesc++)
			{
				// on recupere les attributs bid (base_id) et rid (record_id) de cette fiche
				$bid = $desc->getAttribute('bid');
				$rid = $desc->getAttribute('rid');
				$statbits = $desc->getAttribute('sb');
				$editDirty = $desc->getAttribute('edit');
					
				if($editDirty == '0')
					$editDirty = false;
				else
					$editDirty = true;
					
				// load the xml record into dom
				$xmlrec = phrasea_xmlcaption($session->ses_id, $bid, $rid);
				if(!$xmlrec)
				{
					if(GV_debug)
						printf("le record bid=%d, rid=%s est inaccessible : anormal\n", $bid, $rid);
					continue;
				}
				$rec = new DOMDocument();
				$rec->preserveWhiteSpace = false;
				$rec->formatOutput = true;
				if(!$rec->loadXML($xmlrec))
				{
					if(GV_debug)
						printf("le record bid=%d, rid=%s est bad xml : anormal\n", $bid, $rid);
					continue;
				}
				$xp_rec = new DOMXPath($rec);
				if(!($recdesc = $xp_rec->query('/record/description')->item(0)))
				{
					if(GV_debug)
						printf("le record bid=%d, rid=%s n'a pas de xml('description') : anormal\n", $bid, $rid);
					continue;
				}
				
				// prepare a php array with field=>values of the record before change
				// and delete all old fields of the dom 
				$trec = array();
				while($node = $recdesc->firstChild)
				{
					if($node->nodeType == XML_ELEMENT_NODE)
					{
						if(($node2=$node->firstChild) && $node2->nodeType==XML_TEXT_NODE)
						{
							$fname = $node->nodeName;
							$fval  = $node2->nodeValue;
							if(!isset($trec[$fname]))
								$trec[$fname] = array('values'=>array());
							$trec[$fname]['values'][] = $fval;	
						}
						if(GV_debug)
							printf("suppr du node <%s> du record.\n", $node->nodeName);
					}
					$recdesc->removeChild($node);
				}

				// prepare a php array with fields=>values to change for this record
				$trecchanges = $trecchanges0;
				
				$regfields = false;
				$all_regfields = basket::load_regfields();
				if(isset($all_regfields[$parm['sbid']]))
				{
					$regfields = $all_regfields[$parm['sbid']];
				}
				
				for($fdesc = $desc->firstChild; $fdesc; $fdesc = $fdesc->nextSibling)
				{
					if($fdesc->nodeType != XML_ELEMENT_NODE)
						continue;
					$fname = $fdesc->nodeName;
					if(!isset($tstruct[$fname]))
					{
						if(GV_debug)
							printf("mdesc : le champ '%s' n'existe pas dans la structure ?\n", $fname);
						continue;
					}
					if(!isset($trecchanges[$fname]))
						$trecchanges[$fname] = array('values'=>array());
						
					$tval = array();
					$val = str_replace(array('&lt;', '&gt;', '&amp;'), array('<', '>', '&'), $fdesc->nodeValue);
					if($tstruct[$fname]['multi'])
					{
						$separator = $tstruct[$fname]['separator'];
						if(strlen($separator)==1)
						{
							$tval = explode($separator, $val);
						}
						else
						{
							// s'il y'a plusieurs delimiters, on transforme en regexp pour utiliser split
							$separator = preg_split('//', $separator, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
							$separator = '/\\'.implode('|\\', $separator) . '/';
							$tval = preg_split($separator, $val);
						}
					}
					else
					{
						$tval = array($val);
					}
					// add val(s) to changes, keeping multi-values distinct
					foreach($tval as $val)
					{
						$val = trim($val);
						if(!$tstruct[$fname]['multi'] || !array_search($val, $trecchanges[$fname]['values']))
							$trecchanges[$fname]['values'][] = $val;
					}
				}
				
				$conn = connection::getInstance();
				
				foreach($trecchanges as $fname=>$fchange)
				{
					if(GV_debug)
						printf("\nmodification du champ '%s'\n", $fname);
				
					$bool = false;
					if($regfields && $parm['act'] == 'SAVEGRP' && $fname == $regfields['regname'])
					{
                                          try
                                          {
                                            $basket = basket::getInstance($parm['ssel']);
                                            $basket->name = $fchange['values'];
                                            $basket->save();
						$bool = true;
					}
                                          catch(Exception $e)
                                          {
                                            echo $e->getMessage();
                                          }
					}
					if($regfields && $parm['act'] == 'SAVEGRP' && $fname == $regfields['regdesc'])
					{
                                          try
                                          {
                                            $basket = basket::getInstance($parm['ssel']);
                                            $basket->desc = $fchange['values'];
                                            $basket->save();
						$bool = true;
					}
                                          catch(Exception $e)
                                          {
                                            echo $e->getMessage();
                                          }
					}
					if($bool)
					{
						$cache_basket = cache_basket::getInstance();
						$cache_basket->delete($session->usr_id, $parm['ssel']);
					}
					
					$src    = $tstruct[$fname]['src'];
					$type   = $tstruct[$fname]['type'];
					$format = $tstruct[$fname]['format'];
	
					// delete old instances of field in trec
					if(count($fchange['values'])==1 && $fchange['values'][0]=='')
					{
						if(GV_debug)
							printf("suppr du champ '%s' du record.\n", $fname);
						unset($trec[$fname]);
					}
					else
					{
						if(GV_debug)
							printf("remplacement du champ '%s' du record.\n", $fname);
						$trec[$fname] = $fchange;
					}
				}
				
				foreach($tstruct as $fname=>$fstruct)
				{
					if(isset($trec[$fname]))
					{
						foreach($trec[$fname]['values'] as $fval)
						{
							$node = $recdesc->appendChild($rec->createElement($fname));
							$node->appendChild($rec->createTextNode($fval));
						}
					}
				}
				
	
				$rec->normalize();
	
				$t = $rec->saveXML();
				
				// on calcule les mask de statbits
				$newstat = 'status';
				$statbits = ltrim($statbits, 'x');
				if($statbits != '')
				{
					if( ($mask_and = ltrim(str_replace(array('x','0','1','z'), array('1','z','0','1'), $statbits), '0')) != '')
						$newstat = '('.$newstat.' & ~0b'.$mask_and.')';
					if( ($mask_or  = ltrim(str_replace('x', '0', $statbits), '0')) != '')
						$newstat = '('.$newstat.' | 0b'.$mask_or.')';
				}
				

				// SAVE & INDEX ///////////////////////////////////////////////////////////////////////////////////////////////

				// $setxml = phrasea_setxmlcaption($ses, $bid, $rid, $t, true, false);	// suppr les index, ne pas baisser les status
				// on n'utilise PAS phrasea_setxmlcaption qui baisse les statbits 0 et 1 immediatement...
				// ... on ecrit en sql
				// ... flag the record as 'to reindex' (bits 0 and 1) AND LOCK IT (bit 2) to prevent immediate reindex
				// ... ask for meta rewriting
				$sql = 'UPDATE record SET status='.$newstat.' & ~7, jeton='.(JETON_WRITE_META_DOC|JETON_WRITE_META_SUBDEF).', xml=\'' . $connbas->escape_string($t) . '\' WHERE record_id=' . $rid;
				$setxml = $connbas->query($sql) ? true : false;
				
				if($statbits != '')
				{
					answer::logEvent($parm['sbid'],$rid,'status','','');
				}
				if($editDirty)
				{
					answer::logEvent($parm['sbid'],$rid,'edit','','');
				}
				

				if($setxml)
				{
					$ridschanged[] = $rid;
					
					// since caption has changed, the stamp is invalid, so delete
					$doc = phrasea_subdefs($session->ses_id, $bid, $rid, 'document');
					@unlink($doc['document']['path'].'stamp_'.$doc['document']['file']);
				}
			}

			if($ridschanged)
			{
				$cache = cache_record::getInstance();
				$sql = 'UPDATE record SET status=status | 4 WHERE record_id IN(' . implode(',',$ridschanged) . ')';
				if($connbas->query($sql))
				{
					foreach($ridschanged as $record_id)
					{
						$cache->delete($parm['sbid'],$record_id);
					}
				}
			}
		}
	}
		
