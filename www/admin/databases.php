<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$lng = isset($session->locale)?$session->locale:GV_default_lng;

$session = session::getInstance();
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	phrasea::headers(403);	
}

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	phrasea::headers(403);	

$conn = connection::getInstance();

if(!$conn)
	phrasea::headers(500);	

$request = httpRequest::getInstance();

$user = user::getInstance($usr_id);

$createBase = $mountBase = false;
$error = array();

if($request->has_post_datas() && $user->is_admin === true)
{
	$parm = $request->get_parms('upgrade'); 
	if(!is_null($parm['upgrade']))
	{
		$checks = p4::checkBeforeUpgrade();
		if($checks === true)
		{
			$parm['upgrade'] = p4::forceUpgrade();
			?>
			<div style="color:black;font-weight:bold;background-color:green;">
				<?php echo _('N\'oubliez pas de redemarrer le planificateur de taches');?>
			</div>
			<?php 
		}
		else
		{
			?>
			<div style="color:black;font-weight:bold;background-color:red;"><?php echo implode('<br/>',$checks);?></div>
			<?php 
		}
			
	}
	$parm = $request->get_parms('mount_base', 'new_settings','new_dbname','new_data_template', 'new_hostname', 
		'new_port', 'new_user', 'new_user', 'new_password', 'new_dbname', 'new_data_template'); 
	if(!$parm['mount_base'])
	{
		if(!$parm['new_settings'] && $parm['new_dbname'] && $parm['new_data_template'])
		{	
	
			if(p4string::hasAccent($parm['new_dbname']))
				$error['new_dbname'] = 'No special chars in dbname'; 
			
			if(count($error) === 0)
			{
				
				if( ($base = new databox()) !== false )
				{
							if($base->create($parm['new_dbname']) !== false)
							{
								$data_template = GV_RootPath.'lib/conf.d/data_templates/'.$parm['new_data_template'].'.xml';
								
								
								if(is_file($data_template) && $base->setNewStructure(
											$data_template , GV_base_datapath_web , GV_base_datapath_noweb , GV_base_dataurl ))
								{
									
									$sbas_id = $base->save($usr_id);
									
									if($sbas_id !== false)
									{
										$base->registerAdmin($usr_id, true);
										$base->registerAdminStruct($usr_id, true);
										$base->registerAdminThesaurus($usr_id, true);
										$base->registerPublication($usr_id, true);
										
										$createBase = $sbas_id;
									}
								}else
									echo 'error';
								
						}
				}
			}
			
		}
		elseif($parm['new_settings'] && $parm['new_hostname'] && $parm['new_port'] && $parm['new_user'] && $parm['new_password'] 
					&& $parm['new_dbname'] && $parm['new_data_template'])
		{
		
			if(p4string::hasAccent($parm['new_dbname']))
				$error['new_dbname'] = 'No special chars in dbname'; 
			
			if(count($error) === 0)
			{
				
				if( ($base = new databox(false,$parm['new_hostname'],$parm['new_port'],$parm['new_user'],$parm['new_password'])) !== false )
				{
							if($base->create($parm['new_dbname']) !== false)
							{
								$data_template = GV_RootPath.'lib/conf.d/data_templates/'.$parm['new_data_template'].'.xml';
								
								
								if(is_file($data_template) && $base->setNewStructure( 
											$data_template , GV_base_datapath_web , GV_base_datapath_noweb , GV_base_dataurl ))
								{
									
									$sbas_id = $base->save($usr_id);
									
									if($sbas_id !== false)
									{
										$base->registerAdmin($usr_id, true);
										$base->registerAdminStruct($usr_id, true);
										$base->registerAdminThesaurus($usr_id, true);
										$base->registerPublication($usr_id, true);
										
										
										$createBase = $sbas_id;
									}
								}else
									echo 'error';
								
						}
				}
			}
		}
	}
	elseif($parm['mount_base'] && $parm['new_hostname'] && $parm['new_port'] && $parm['new_user'] 
			&& $parm['new_password'] && $parm['new_dbname'])
	{
		if(!$parm['new_settings'] && $parm['new_dbname'])
		{	
	
			if(p4string::hasAccent($parm['new_dbname']))
				$error['new_dbname'] = 'No special chars in dbname'; 
			
			if(count($error) === 0)
			{
				if( ($base = new databox()) !== false )
				{
					if(($sbas_id = $base->mount($parm['new_dbname'], $usr_id)) !== false)
					{
							$base->registerAdmin($usr_id, true);
							$base->registerAdminStruct($usr_id, true);
							$base->registerAdminThesaurus($usr_id, true);
							$base->registerPublication($usr_id, true);
							$mountBase = true;
					}
				}
			}
		}
		elseif($parm['new_settings'] && $parm['new_hostname'] && $parm['new_port'] && $parm['new_user'] 
				&& $parm['new_password'] && $parm['new_dbname'] && $parm['new_data_template'])
		{
		
			if(p4string::hasAccent($parm['new_dbname']))
				$error['new_dbname'] = 'No special chars in dbname'; 
			
			if(count($error) === 0)
			{
				if( ($base = new databox(false,$parm['new_hostname'],$parm['new_port'],$parm['new_user'],$parm['new_password'])) !== false )
				{
					if($base->mount($parm['new_dbname']))
					{
						$sbas_id = $base->save($usr_id);
						
						if($sbas_id !== false)
						{
							$base->registerAdmin($usr_id, true);
							$base->registerAdminStruct($usr_id, true);
							$base->registerAdminThesaurus($usr_id, true);
							$base->registerPublication($usr_id, true);
							$mountBase = true;
						}
					}
				}
			}
		}
	}
}
	
	
	
	
	
	
phrasea::headers();
		

$abox = new appbox();

$upgrade_avalaible = false;

if($abox->upgradeAvalaible())
	$upgrade_avalaible = true;

$hasRightsMountDB = FALSE ;


$sql = "SELECT bas.sbas_id, sbasusr.bas_manage,sbasusr.bas_modify_struct
		FROM (bas INNER JOIN sbasusr ON ( bas.sbas_id=sbasusr.sbas_id ) ) 
		WHERE sbasusr.usr_id='".$conn->escape_string($usr_id)."' group by sbas_id";	
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		if($row["bas_manage"]=='1' || $row["bas_modify_struct"]=='1' )
			$hasRightsMountDB = TRUE ;
	}	
	$conn->free_result($rs);
}  


$sbas = array();
$sql = "SELECT sbas.sbas_id FROM sbas, sbasusr WHERE sbasusr.usr_id='".$conn->escape_string($usr_id)."' AND (sbasusr.bas_manage=1 or sbasusr.bas_modify_struct=1) AND sbasusr.sbas_id = sbas.sbas_id ORDER BY ord ASC";
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		$version = 'unknown';
		if($base = new databox($row['sbas_id']))
			$version = $base->getVersion();
		$connbas = connection::getInstance($row['sbas_id']);
		
		if($connbas && $base->upgradeAvalaible())
			$upgrade_avalaible = true;
		$sbas[$row['sbas_id']] = ($connbas ? '<img src="/skins/icons/foldph20close_0.gif"> ':'<img src="/skins/icons/db-remove.png"/> ').phrasea::sbas_names($row['sbas_id']) .($connbas ?' (version '.$version.') MySQL '.$connbas->server_info() : ' (Unreachable server)');
	}
	$conn->free_result($rs);
}



?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
		<script type="text/javascript">
		<?php 
		if($createBase || $mountBase)
		{
			$cache_user = cache_user::getInstance();
			$cache_user->delete($session->usr_id);
			?>
			parent.reloadTree('bases:bases');
			<?php 
			if($createBase)
			{
				?>
				document.location.replace('/admin/newcoll.php?act=GETNAME&p0=<?php echo $sbas_id;?>');
				<?php
			}
		}
		?>
		
		</script>
	</head>
	<body>
		<div style="position:relative;float:left;width:100%;">
			<h2>Bases actuelles :</h2>
			<ul>
				<?php 
				if(count($sbas) > 0)
				{
					foreach($sbas as $k=>$v)
					{
						?>
						<li>
							<a href='database.php?p0=<?php echo $k?>' target='_self'>
								<span><?php echo $v?></span>
							</a>
						</li>
						<?php 
					}
				}
				else
				{
					?>
					<li>None</li>
					<?php 
				}
					?>
			
			</ul>
		</div>
		<?php 
		if($user->is_admin === true)
		{
			?>
			
			<div style="position:relative;float:left;width:100%;">
				<h2><?php echo _('admin::base: Version')?></h2>
				<?php if($upgrade_avalaible)
				{
				?>
				<div><?php echo _('update::Votre application necessite une mise a jour vers : '),' ',GV_version?></div>
				<?php 
				}
				else
				{
				?>
				<div><?php echo _('update::Votre version est a jour : '),' ',GV_version?></div>
				<?php 
				}
				?>
				<form action="databases.php" method="post" >
					<input type="hidden" value="" name="upgrade" />
					<input type="submit" value="<?php echo _('update::Verifier els tables')?>"/>
				</form>
			</div>
			
			<div style="position:relative;float:left;width:100%;">
				<h2><?php echo _('admin::base: creer une base')?></h2>
				<div id="create_base">
					<form method="post" action="databases.php">
						<div>
							<input type="checkbox" name="new_settings" onchange="if(this.checked == true)$('#server_opts').slideDown();else $('#server_opts').slideUp();"/><label><?php echo _('phraseanet:: Creer une base sur un serveur different de l\'application box');?></label>
						</div>
						<div id="server_opts" style="display:none;">
							<div>
								<label><?php echo _('phraseanet:: hostname');?></label><input name="new_hostname" value="" type="text"/><span class="error"><?php echo isset($error['new_hostname']) ? $error['new_hostname'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: port');?></label><input name="new_port" value="3306" type="text"/><span class="error"><?php echo isset($error['new_port']) ? $error['new_port'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: user');?></label><input name="new_user" value="" type="text"/><span class="error"><?php echo isset($error['new_user']) ? $error['new_user'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: password');?></label><input name="new_password" value="" type="password"/><span class="error"><?php echo isset($error['new_password']) ? $error['new_password'] : ''; ?></span>
							</div>
						</div>
						<div>
							<label><?php echo _('phraseanet:: dbname');?></label><input name="new_dbname" value="" type="text"/><span class="error"><?php echo isset($error['new_dbname']) ? $error['new_dbname'] : ''; ?></span>
						</div>
						<div>
							<label><?php echo _('phraseanet:: Modele de donnees');?></label>
							<select name="new_data_template">
								<?php 
								if ($handle = opendir(GV_RootPath . 'lib/conf.d/data_templates'))
								{
									while (false !== ($file = readdir($handle))) {
										if(is_file(GV_RootPath.'lib/conf.d/data_templates/'.$file))
										{
											$file = substr($file,0,(strlen($file)-4));
											?>
											<option value="<?php echo $file;?>"><?php echo $file;?></option>
											<?php 	
										}
									}
								
								    closedir($handle);
								}
								
								?>
							</select>
						</div>
						<div>
							<input value="<?php echo _('boutton::creer');?>" type="submit"/>
						</div>
					</form>
				</div>
			</div>
			<div style="position:relative;float:left;width:100%;">
				<h2><?php echo _('admin::base: Monter une base')?></h2>
				<div id="mount_base">
					<form method="post" action="databases.php">
						<div>
							<input type="checkbox" name="new_settings" onchange="if(this.checked == true)$('#servermount_opts').slideDown();else $('#servermount_opts').slideUp();"/><label><?php echo _('phraseanet:: Monter une base provenant d\'un serveur different de l\'application box');?></label>
						</div>
						<div id="servermount_opts" style="display:none;">
							<div>
								<label><?php echo _('phraseanet:: hostname');?></label><input name="new_hostname" value="" type="text"/><span class="error"><?php echo isset($error['new_hostname']) ? $error['new_hostname'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: port');?></label><input name="new_port" value="3306" type="text"/><span class="error"><?php echo isset($error['new_port']) ? $error['new_port'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: user');?></label><input name="new_user" value="" type="text"/><span class="error"><?php echo isset($error['new_user']) ? $error['new_user'] : ''; ?></span>
							</div>
							<div>
								<label><?php echo _('phraseanet:: password');?></label><input name="new_password" value="" type="password"/><span class="error"><?php echo isset($error['new_password']) ? $error['new_password'] : ''; ?></span>
							</div>
						</div>
						<div>
							<label><?php echo _('phraseanet:: dbname');?></label><input name="new_dbname" value="" type="text"/><span class="error"><?php echo isset($error['new_dbname']) ? $error['new_dbname'] : ''; ?></span>
						</div>
						<div>
							<input type="hidden" name="mount_base" value="yes"/>
							<input value="<?php echo _('boutton::monter')?>" type="submit"/>
						</div>
					</form>
				</div>
			</div>
		<?php 
		}
		?>
	</body>
</html>
