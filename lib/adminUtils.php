<?php

function getTree($usr_id,$ses_id,$position=false)
{
	$out = '';
	
	$conn = connection::getInstance();
	if(!$conn)
		return $out;
	
	$superU = $seeUsr = $seeTaskManager = false;
	
	$allcoll = $offcoll = $usrRight = array();
	
	$user = user::getInstance($usr_id);
	
	$superU = $user->is_admin;
	$seeUsr = $user->_global_rights['manageusers'];
	$seeTaskManager = $user->_global_rights['taskmanager'];
	
	$feature = 'connected';
	$featured = false;
	$position = explode(':',$position);
	if(count($position) > 0)
	{
		if(in_array($position[0],array('connected','registrations','taskmanager','base','bases','collection','user','users')))
		{
			$feature = $position[0];
			if(isset($position[1]))
				$featured = $position[1];
		}
	}

	unset($position);
	
	$sql = "SELECT sb.sbas_id, bu.base_id, bu.canaddrecord, bu.canmodifrecord,
		bu.canadmin, bu.manage,bu.modify_struct,
		sb.bas_manage,  sb.bas_modify_struct
		FROM (usr u, sbasusr sb) 
			LEFT JOIN (bas b, basusr bu) ON (b.sbas_id = sb.sbas_id AND bu.base_id = b.base_id AND bu.usr_id='".$conn->escape_string($usr_id)."')
		 WHERE sb.usr_id = u.usr_id AND u.usr_id='".$conn->escape_string($usr_id)."'";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["sbas_id"]][$row["base_id"]]['canmodifrecord'] = $row['canmodifrecord'];
			$usrRight[$row["sbas_id"]][$row["base_id"]]['canadmin'] = $row['canadmin'];
			
			$usrRight[$row["sbas_id"]][$row["base_id"]]['manage'] = $row['manage'];
			$usrRight[$row["sbas_id"]]["bas_manage"] = $row["bas_manage"];
			$usrRight[$row["sbas_id"]][$row["base_id"]] = $row['modify_struct'];
			$usrRight[$row["sbas_id"]]['bas_modify_struct'] = $row['bas_modify_struct'];
		}
		$conn->free_result($rs);
	}

	$sql = 'SELECT b.server_coll_id, b.ord, b.base_id, b.active, sb.host, sb.sbas_id, sb.port, sb.dbname, sb.viewname, sb.user 
			FROM (sbasusr sbu, sbas sb, usr u) 
				LEFT JOIN (bas b, basusr bu) ON (b.active="1" AND bu.base_id = b.base_id AND bu.usr_id = "'.$conn->escape_string($usr_id).'" AND b.sbas_id=sb.sbas_id AND (bu.canadmin="1" OR bu.manage="1" OR bu.modify_struct="1") )  
			WHERE u.usr_id="'.$conn->escape_string($usr_id).'" AND sbu.usr_id=u.usr_id AND sb.sbas_id = sbu.sbas_id AND (sbu.bas_manage="1" OR sbu.bas_modify_struct="1") 
			ORDER BY sb.ord,sb.sbas_id,b.ord,b.base_id';
	
	if($rs = $conn->query($sql))
	{
		while(($row = $conn->fetch_assoc($rs)) )
		{
			$unik = "_" . $row["sbas_id"];
			if(!isset($allcoll[$unik]))
			{
				$allcoll[$unik]["host"] = $row['host'] ;
				$allcoll[$unik]["sbas_id"] = $row["sbas_id"];
				$allcoll[$unik]["dbname"] = $row["dbname"];
				$allcoll[$unik]["viewname"] = trim($row["viewname"]) != '' ? $row['viewname'] : $row['dbname'];
				$allcoll[$unik]["server_coll_list"] = "";
				$allcoll[$unik]["collections"] = array();
			}
			if(!is_null($row["server_coll_id"]))
			{
				$allcoll[$unik]["collections"][$row["server_coll_id"]]["base_id"]= $row["base_id"];
				$allcoll[$unik]["collections"][$row["server_coll_id"]]["coll_id"]= $row["server_coll_id"];
				if( $allcoll[$unik]["server_coll_list"]!="")
					$allcoll[$unik]["server_coll_list"].= ",";
				$allcoll[$unik]["server_coll_list"].= $row["server_coll_id"];
			}
		}
		$conn->free_result($rs);
	}
	
	foreach($allcoll as $indice =>$myunik)
	{
		$conn2 = connection::getInstance($myunik['sbas_id']);
		if($conn2)
		{
			$sql2 = "SELECT coll_id,htmlname from coll WHERE coll_id in (".$myunik["server_coll_list"].")";
			if($rs2 = $conn2->query($sql2))
			{
				while(($row2 = $conn2->fetch_assoc($rs2)) )
				{
					if( isset($allcoll[$indice]["collections"][$row2["coll_id"]]) )
					{
						$allcoll[$indice]["collections"][$row2["coll_id"]]["name"] = $row2["htmlname"];
					}
				}
				$conn2->free_result($rs2);
			}
		}
		else
		{
			$offcoll[] = array('dbname'=>$allcoll[$indice]['dbname'],'host'=>$allcoll[$indice]['host']);
	
			unset($allcoll[$indice]["coll"]);
			unset($allcoll[$indice]);
		}
		unset($allcoll[$indice]["server_coll_list"]);
	}

		
			
			
	$out .= '<ul id="tree" class="filetree">';
	if($superU === true)
		$out .= '<li><a target="right" href="sitestruct.php">'._('Tableau de bord').'</a></li>';
	else
		$out .= '<li>'.GV_ServerName.'</li>';
	if($superU === true)
		$out .=	'<li><a target="right" href="/admin/global_values.php">Setup</a></li>';
	
	$out .= '<li class="'.($feature == 'connected' ? 'selected' : '').'"><a target="right" href="sessionwhois.php"> <img src="/skins/icons/usersConnected.gif"> '._('admin::utilisateurs: utilisateurs connectes').'</a></li>';
		
	if($seeUsr) {
	
	$out .= '<li class="'.($feature == 'users' ? 'selected' : '').'"><a target="right" href="users.php?act=LISTUSERS&p0=&p1="> <img src="/skins/icons/users20.gif"> '._('admin::utilisateurs: utilisateurs').'</a></li>
	<li class="'.($feature == 'registrations' ? 'selected' : '').'"><a target="right" href="demand.php?act=LISTUSERS"> <img src="/skins/icons/demand.gif"> '._('admin::utilisateurs: demandes en cours').'</a></li>';
	
	} if($seeTaskManager) {
	
	$out .= '<li class="'.($feature == 'taskmanager' ? 'selected' : '').'"><a target="right" href="taskmanager.php"> <img src="/skins/icons/scheduler.gif"> '._('admin::utilisateurs: gestionnaire de taches').'</a></li>';
	
	}
		
	$out .= '<li class="open">
				<div class="'.($feature == 'bases' ? 'selected' : '').'" style="padding:0 0 2px 0;">
					<a id="TREE_DATABASES" target="right" href="databases.php"> <img src="/skins/icons/foldph20netwk_0.gif"/> '._('admin::utilisateurs: bases de donnees').'</a>
				</div>
		<ul>';
				
	foreach($allcoll as $phbase)
	{

		$out .= '<li '.(in_array($feature,array('base','collection','user')) && $featured==$phbase['sbas_id'] ? 'class="open"' : '').'>
					<div style="padding:0 0 2px 0;">
						<a target="right" href="database.php?p0='.$phbase["sbas_id"].'"> <img src="/skins/icons/foldph20close_0.gif"/> '.$phbase["viewname"].'</a>
					</div>
			<ul>';
			
				if($user->_rights_sbas[$phbase['sbas_id']]['bas_modify_struct'])
				{
				
					$out .= ''.
					'<li> <a target="right" href="structure.php?act=STRUCTURE&p0='.$phbase["sbas_id"].'"> <img src="/skins/icons/miniadjust01.gif"/> '._('admin::structure: reglage de la structure').'</a></li>
					<li> <a target="right" href="statbits.php?act=STATBITS&p0='.$phbase["sbas_id"].'"> <img src="/skins/icons/miniadjust02.gif"/> '._('admin::status: reglage des status').'</a></li>
					<li> <a target="right" href="cgus.php?p0='.$phbase["sbas_id"].'"> '._('admin:: CGUs').'</a></li>
					<li> <a target="right" href="collorder.php?p0='.$phbase["sbas_id"].'"> <img src="/skins/icons/miniadjust03.gif"/> '._('admin::collection: ordre des collections').'</a></li>';
				
				}
				
				$seeUsrGene = FALSE ;
		
				foreach($phbase["collections"] as $coll)
				{
				 	if($usrRight[$phbase["sbas_id"]][$coll["base_id"]]['canadmin']=="1")
				 	{
				 		$seeUsrGene = TRUE ;
				 		break;
				 	}
				}

				if($seeUsrGene)
				{
				
					$out .= '<li><a target="right" href="users.php?act=LISTUSERS&p0='.$phbase["sbas_id"].'&p1="> <img src="/skins/icons/users20.gif"/> '._('admin::utilisateurs: utilisateurs').'</a></li>';
				
				}


				foreach($phbase["collections"] as $coll)
				{
					$collname = trim($coll["name"]) != '' ? $coll["name"] : '<i>Untitled</i>';
					$out .= '<li>
								<div style="padding:0 0 2px 0;">
									<a target="right" href="collection.php?act=COLLECTION&p0='.$phbase["sbas_id"].'&p1='.$coll['base_id'].'">'.$collname.'</a>
								</div>
						<ul>';
						
						if($usrRight[$phbase["sbas_id"]][$coll["base_id"]]['modify_struct']=="1" )
						{
						
							$out .= '<li><a target="right" href="sugval.php?p0='.$phbase["sbas_id"].'&p1='.$coll["base_id"].'"> <img src="/skins/icons/foldph20open_0.gif"/> '._('admin::base: preferences de collection').'</a></li>';
						
			 			}
 
			 			if($usrRight[$phbase["sbas_id"]][$coll["base_id"]]['canadmin']=="1")
			 			{
			 				if($usrRight[$phbase["sbas_id"]][$coll["base_id"]]['canmodifrecord']=="1" && $usrRight[$phbase["sbas_id"]][$coll["base_id"]]['manage']=="1" && $usrRight[$phbase["sbas_id"]]["bas_manage"]=="1" && $usrRight[$phbase["sbas_id"]]["bas_manage"]=="1")
			 				{
						
							$out .= '<li><a target="right" href="users.php?act=LISTUSERS&p0='.$phbase["sbas_id"].'&p1='.$coll["base_id"].'"> <img src="/skins/icons/users20.gif"/> '._('admin::utilisateurs: utilisateurs').'</a></li>';					
						
			 				}
						}
						
						$out .= '</ul>
					</li>';
				
				}
				
			$out .= '</ul>
		</li>';
	}
					
		$out .= '</ul>
	</li>';
	
	foreach($offcoll as $coll)
	{
		$out .= '<li><span> <img src="/skins/icons/db-remove.png"/> '.$coll["dbname"].'('.$coll["host"].')</span></li>';
	}
		
	$out .= '</ul>';

	return $out;
}


function createBase($type,$ghost,$gport,$guser,$gpasswd,$gdbname,$writeConn=false)
{
	
	$connexion = dirname(__FILE__)."/../config/connexion.inc";	
	
	$go = true;
	
	$ret = array('error'=>true,'message'=>'');
	
	if(trim($gdbname)=='' || trim($ghost)=='')
	{
		$ret['message'] = _('Identifiants incorrects');
		return $ret;
	}
		
	if($writeConn)
	{
		$go = false;
		
		if(is_file($connexion))
			unlink($connexion);

		$EOL = PHP_EOL;
		
		$connexionINI = '<?php'.$EOL;
		$connexionINI .= '$hostname = \''.str_replace("'","\'",$ghost).'\';'.$EOL;
		$connexionINI .= '$port = \''.str_replace("'","\'",$gport).'\';'.$EOL;
		$connexionINI .= '$user = \''.str_replace("'","\'",$guser).'\';'.$EOL;
		$connexionINI .= '$password = \''.str_replace("'","\'",$gpasswd).'\';'.$EOL;
		$connexionINI .= '$dbname = \''.str_replace("'","\'",$gdbname).'\';'.$EOL;
		
		
		if(file_put_contents($connexion, $connexionINI) !== FALSE)
		{
			
			if(function_exists('chmod'))
				chmod($connexion,0700);
			$go = true;
		}
		else
		{
			$ret['message'] = sprintf(_('Impossible d\'ecrire dans le dossier %s'),dirname(dirname(__FILE__))."/config/");
			return $ret;
		}
	}
	
	
	require dirname(__FILE__).'/../lib/classes/base.class.php';
	require dirname(__FILE__).'/../lib/classes/appbox.class.php';
	require dirname(__FILE__).'/../lib/classes/connection.class.php';
	
	
	
	if($go===true && ($base = new appbox()) !== false && ($base->conn !== false))
	{
		
		if($base->create($gdbname) !== false)
		{
			$ret['error'] = false;
			$ret['message'] = sprintf(_('Creation de la base avec succes'));
		}
		else
		{
			$ret['message'] = _('setup::la base de donnees existe deja ou vous n\'avez pas les droits de la creer');
		}
	}
	else
	{
		$ret['message'] = sprintf(_('Identifiants incorrects'));
	}
	return $ret;
}


function createAdmin($password,$email, $databox, $tasks, $indexer, $template, $pathweb, $pathnoweb, $baseurl,$convert, $composite, $php_cli, $exiftool)
{
	require_once dirname(__FILE__).'/../lib/classes/user.class.php';
	require_once dirname(__FILE__).'/../lib/classes/cache.class.php';
	require_once dirname(__FILE__).'/../lib/classes/cache/user.class.php';
	require_once dirname(__FILE__).'/../lib/classes/p4string.class.php';
	require_once dirname(__FILE__).'/../lib/classes/p4.class.php';
	require_once dirname(__FILE__).'/../lib/classes/connection.class.php';
	require_once dirname(__FILE__).'/../lib/classes/phrasea.class.php';
	require_once dirname(__FILE__).'/../lib/classes/base.class.php';
	require_once dirname(__FILE__).'/../lib/classes/databox.class.php';
	require_once dirname(__FILE__).'/../lib/classes/collection.class.php';
	require_once dirname(__FILE__).'/../lib/classes/cache/appbox.class.php';
	require_once dirname(__FILE__).'/../lib/classes/cache/databox.class.php';
	require_once dirname(__FILE__).'/../lib/classes/cache/basket.class.php';
	
	define('GV_ServerName','INSTALL');
	define('GV_RootPath',dirname(dirname(__FILE__)).'/');
	$session = session::getInstance();
	
	$conn = connection::getInstance();
	
	$ret = array('error'=>true,'message'=>'', 'usr_id'=>0);
	
	if($indexer !== 'false')
	{
		if(!is_file($indexer))
		{
			$ret['message'] = _('Le fichier indexeur specifie n\'existe pas');
			return $ret;
		}
		elseif(!is_executable($indexer))
		{
			$ret['message'] = _('Le fichier indexeur specifie n\'est pas executable');
			return $ret;
		}
	}
	$id = false;
	try
	{
		$user = new user();
		
		if(!defined('GV_sit'))
			define('GV_sit','install');
		
		$user->password = $password;
		$user->login = 'admin';
		$user->email = $email;
		$user->superu = true;
		$user->is_admin = true;
		
		$id=$user->save();
	}
	catch(Exception $e)
	{
		$ret['message'] = $e->getMessage();
	}
	
	if($id !== false)
	{
		$pathnoweb 	= p4string::addEndSlash($pathnoweb);
		$pathweb 	= p4string::addEndSlash($pathweb);
		$baseurl 	= p4string::addEndSlash($baseurl);
		
		$datas = array('GV_base_datapath_noweb'=>$pathnoweb,
						'GV_base_datapath_web'=>$pathweb,
						'GV_base_dataurl'=>$baseurl,
						'GV_exiftool'=>$exiftool,
						'GV_imagick'=>$convert,
						'GV_pathcomposite'=>$composite,
						'GV_cli'=>$php_cli
		);
		
		if(setup::create_global_values($datas))
		{
			$ret['usr_id'] = $id;
			
			phrasea::start();
			$ses_id = phrasea_create_session($id);
			if($ses_id !== false)
			{
				$session->usr_id 	= $id;
				$session->ses_id 	= $ses_id;
				$session->admin 	= true;
				$session->invite 	= false;
				
				$ret['error'] = false;
				if($databox)
				{
					$ret['error'] = true;
					if(!p4string::hasAccent($databox))
					{
						if( ($base = new databox()) !== false )
						{
							if($base->create($databox) !== false)
							{
								$data_template = GV_RootPath.'lib/conf.d/data_templates/'.$template.'.xml';
								
								if(is_file($data_template) && $base->setNewStructure( $data_template , $pathweb , $pathnoweb , $baseurl ))
								{
									try
									{
										$sbas_id = $base->save($session->usr_id);
										$base->registerAdmin($session->usr_id, true);
										$base->registerAdminStruct($session->usr_id, true);
										$base->registerAdminThesaurus($session->usr_id, true);
										$base->registerPublication($session->usr_id, true);
										
										collection::create_collection($sbas_id, 'test');
										
										$ret['error'] = false;
									}
									catch(Exception $e)
									{
										$ret['message'] = $e->getMessage();
									}

									if(is_array($tasks) && count($tasks) > 0)
									{
										foreach($tasks as $task)
										{
											$id = $conn->getId('task');
											
											switch($task)
											{
												case 'readmeta';
													$sql = 'INSERT INTO `task2` (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`, `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`) VALUES
													('.$id.', 0, 0, "stopped", 0, 1, "Metadatas Reading", "0000-00-00 00:00:0", "task_readmeta", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<period>10</period><flush>10</flush><autodie>1</autodie><maxrecs></maxrecs><maxmegs></maxmegs></tasksettings>\n", -1)';
													break;
												case 'writemeta';
													$sql = 'INSERT INTO `task2` (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`, `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`) VALUES
													('.$id.', 0, 0, "stopped", 0, 1, "Metadatas Writing", "0000-00-00 00:00:0", "task_writemeta", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n	<period>10</period>\n	<autodie>1</autodie>\n	<maxrecs></maxrecs>\n	<maxmegs></maxmegs>\n	<cleardoc>0</cleardoc>\n</tasksettings>\n", -1)';
													break;
												case 'subdefs';
													$sql = 'INSERT INTO `task2` (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`, `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`) VALUES
													('.$id.', 0, 0, "stopped", 0, 1, "Subdefs", "0000-00-00 00:00:0", "task_subdef", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<period>10</period><flush>10</flush><autodie>1</autodie><maxrecs></maxrecs><maxmegs></maxmegs></tasksettings>\n", -1)';
													break;
												case 'indexer';
													require GV_RootPath.'config/connexion.inc';
													$sql = 'INSERT INTO `task2` (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`, `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`) VALUES
													('.$id.', 0, 0, "stopped", 0, 1, "Indexer", "0000-00-00 00:00:0", "task_cindexer", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>'.$conn->escape_string(str_replace('/phraseanet_indexer','',$indexer)).'</binpath><host>'.$conn->escape_string($hostname).'</host><port>'.$conn->escape_string($port).'</port><base>'.$conn->escape_string($dbname).'</base><user>'.$conn->escape_string($user).'</user><password>'.$conn->escape_string($password).'</password><socket>25200</socket><use_sbas>1</use_sbas><nolog>0</nolog><clng></clng><winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>\n", -1)';
													break;
													
											}
											$conn->query($sql);
										}
									}
								}
								else
								{
									$ret['message'] = _('Impossible de charger les templates de base');
								}

							}	
							else
							{
								$ret['message'] = _('Impossible de creer la base de donnee');
							}							
						}
						else
						{
							$ret['message'] = _('Impossible d instancier la base');
						}
					}
					else
					{
						$ret['message'] = _('Le nom de base ne doit contenir ni espace ni caractere special');
					}
					
					if($ret['error'])
					{
						$sql = 'TRUNCATE bas';
						$conn->query($sql);
						$sql = 'TRUNCATE sbas';
						$conn->query($sql);
						$sql = 'TRUNCATE basusr';
						$conn->query($sql);
						$sql = 'TRUNCATE sbasusr';
						$conn->query($sql);
						$sql = 'DROP DATABASE `'.$databox.'`';
						$conn->query($sql);
					}
				}
				if($ret['error'])
					p4::logout();
			}
			else
			{
				$ret['error'] = true;
				$ret['message'] = _('Impossible d\'ouvrir une session');
			}
		}
		else
		{
			$ret['message'] = _('Impossible de creer le fichier de configuration');
		}
	}
	if($ret['error'])
	{
		$sql = 'DELETE FROM usr WHERE usr_login="admin"';
		$conn->query($sql);
		@unlink(GV_RootPath.'config/_GV.php');
	}
	return $ret;
}
