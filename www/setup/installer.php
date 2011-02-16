<?php
define('GV_googleAnalytics',false);
require dirname(__FILE__).'/../../lib/classes/p4.class.php';
require dirname(__FILE__).'/../../lib/version.inc';
require dirname(__FILE__).'/../../lib/conf.d/_GV_template.inc';
require dirname(__FILE__).'/../../lib/classes/phrasea.class.php';
require dirname(__FILE__).'/../../lib/classes/p4string.class.php';
require dirname(__FILE__).'/../../lib/classes/p4utils.class.php';
require dirname(__FILE__).'/../../lib/classes/setup.class.php';
require dirname(__FILE__).'/../../lib/classes/session.class.php';
require dirname(__FILE__).'/../../lib/classes/httpRequest.class.php';
require dirname(__FILE__).'/../../lib/classes/login.class.php';
require dirname(__FILE__).'/../../lib/classes/skins.class.php';


$rootpath = str_replace('\\','/',dirname(dirname(dirname(__FILE__)))).'/';
$system = p4utils::getSystem();
$session = session::getInstance();
	
//ini_set('display_errors','off');

if(!is_writeable(dirname(__FILE__).'/../skins/'))
{
	exit('You must set your directories writeable by apache');
}

skins::merge();

define('GV_default_lng','en_GB');
$avLanguages = user::detectLanguage();	
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'hostname', 'port', 'user', 'password', 'abname',
				'databox','indexer','create_task','convert','composite',
				'php_cli','exiftool','pathweb','pathnoweb','baseurl',
				'template','password','email','check','value');

				
phrasea::use_i18n();

	$output = '';
	if($parm['action'])
	{
		
		$action = $parm['action'];
		
		switch($action)
		{
			case 'CREATE_BASE':
				include dirname(__FILE__).'/../../lib/adminUtils.php';
				$output = p4string::jsonencode(createBase('application_box', $parm['hostname'], $parm['port'], $parm['user'], $parm['password'], $parm['abname'], true));
				break;
			
			case 'CREATE_ADMIN':
				include dirname(__FILE__).'/../../lib/adminUtils.php';
				require dirname(__FILE__).'/../../config/connexion.inc';
				
				$databox	= $parm['databox']		? $parm['databox']		: false;
				$indexer	= $parm['indexer'] 		? $parm['indexer']    : false;
				$tasks		= $parm['create_task']	? $parm['create_task']	: false;
				$convert 	= $parm['convert']		? $parm['convert']		: false;
				$composite 	= $parm['composite']	? $parm['composite']	: false;
				$php_cli 	= $parm['php_cli']		? $parm['php_cli']		: false;
				$exiftool 	= $parm['exiftool']		? $parm['exiftool']		: false;
				$pathweb 	= $parm['pathweb']		? $parm['pathweb']		: false;
				$pathnoweb 	= $parm['pathnoweb']	? $parm['pathnoweb']	: false;
				$baseurl 	= $parm['baseurl']		? $parm['baseurl']		: false;
				$template 	= $parm['template']		? $parm['template']		: false;
				
				$output = createAdmin($parm['password'],$parm['email'],$databox, $tasks, $indexer, $template, $pathweb, $pathnoweb, $baseurl
							, $convert, $composite, $php_cli, $exiftool);
				
				$output = p4string::jsonencode($output);
				
				break;
			
			case 'CHECK':
				$output = array('result'=>0);
				
				$check = 	$parm['check'] ? $parm['check'] : false;
				$file = 	$parm['value'] ? $parm['value'] : false;
				
				if($check == 'writable' && is_dir($file) && is_writable($file))
					$output['result'] = 1;
				if($check == 'executable' && is_file($file) && is_executable($file))
					$output['result'] = 1;
				$output = p4string::jsonencode($output);
				
				break;
			
		}
		echo $output;
		exit();
	}
	
	

$base_created = false;
if(is_file(dirname(__FILE__).'/../../config/connexion.inc'))
{
	$base_created = true;
	include dirname(__FILE__).'/../../config/connexion.inc';
}

if(!extension_loaded('gettext'))
{
	?>
	YOU HAVE TO LOAD PHP GETTEXT EXTENSION
	<?php 
	exit(0);
}
	
foreach($PHP_CONF as $k=>$v)
	ini_set($k,$v);


phrasea::headers();

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<script src="/include/jslibs/jquery-1.4.4.js" type="text/javascript"></script>
		<script src="/include/jslibs/jquery-ui-1.7.2.js" type="text/javascript"></script>
		<script src="/include/jslibs/jquery.validate.js" type="text/javascript"></script>
		<script src="/include/jslibs/jquery.validate.password.js" type="text/javascript"></script>
		<script type="text/javascript">
			var language = {
						ajaxError				: "<?php echo _('Erreur lors du contact avec le serveur WEB')?>",
						ajaxTimeout				: "<?php echo _('Delai depasse lors du contact avec le serveur WEB')?>",
						validateEmail			: "<?php echo str_replace('"','\"',_('forms::merci d\'entrer une adresse e-mail valide'))?>",
						validatePassword		: "<?php echo str_replace('"','\"',_('forms::ce champ est requis'))?>",
						validatePasswordConfirm	: "<?php echo str_replace('"','\"',_('forms::ce champ est requis'))?>",
						validatePasswordEqual	: "<?php echo str_replace('"','\"',_('forms::les mots de passe ne correspondent pas'))?>",
						wrongCredentials		: "<?php echo str_replace('"','\"',_('Vous devez specifier une adresse email et un mot de passe valides'))?>",
						wrongDatabasename		: "<?php echo str_replace('"','\"',_('Le nom de base de donnee est incorrect'))?>",
						someErrors				: "<?php echo str_replace('"','\"',_('Il y a des erreurs, merci de les corriger avant de continuer'))?>"
						}
	
			$.validator.passwordRating.messages = {
					"similar-to-username": "<?php echo str_replace('"','\"',_('forms::le mot de passe est trop similaire a l\'identifiant'))?>",
					"too-short": "<?php echo str_replace('"','\"',_('forms::la valeur donnee est trop courte'))?>",
					"very-weak": "<?php echo str_replace('"','\"',_('forms::le mot de passe est trop simple'))?>",
					"weak": "<?php echo str_replace('"','\"',_('forms::le mot de passe est trop simple'))?>",
					"good": "<?php echo str_replace('"','\"',_('forms::le mot de passe est bon'))?>",
					"strong": "<?php echo str_replace('"','\"',_('forms::le mot de passe est tres bon'))?>"
				};
		</script>
		<script src="/include/jslibs/jquery.cookie.js" type="text/javascript"></script>
		<script src="/setup/setup.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="/setup/setup.css" />
	</head>
	<body>
	
		<?php 
		if($base_created === false)
		{
		?>
		<div class="steps">
			<div style="float:left;width:100%;margin:20px 0;">
				<h1>
					<?php echo _('Installation de Phraseanet IV');?>
					
					<?php 
					$login = new login();
					echo $login->get_language_selector();
					?>
				</h1>
				<h2><?php echo _('setup::Votre configuration')?></h2>
				<div>
					<div style="position:relative;float:left;width:400px;">
						<?php 
						setup::check_php_version();
						?>
						<?php 
						setup::check_writability();
						?>
						<?php 
						setup::check_php_extension();
						?>
					</div>
					<div style="position:relative;float:right;width:400px;">
						<?php 
						setup::check_cache_opcode();
						?>
						<?php 
						setup::check_php_configuration();
						?>
						<?php 
						setup::check_system_locales();
						?>
					</div>
				</div>
				<div style="float:left;width:100%;text-align:right;">
					<button style="display:none;"  class="verify_exts"><?php echo _('wizard:: next step');?></button>
					<h1 class="verify_exts" style="display:none;"><img src="/skins/icons/delete.png"/> <?php echo _('Vous devez corriger les defauts majeurs avant de poursuivre'); ?></h1>
				</div>
			</div>
		</div>
		<div class="steps">
			<h1><?php echo _('Installation de Phraseanet IV')?></h1>
			<div style="position:relative;float:left;width:100%;">
				<form id="create_base" method="post" onsubmit="return false;" action="index.php">
					<table border="0" cellspacing="0" cellpadding="2" style="width:100%;">
						<tr>
							<td colspan="2">
								<h2>
									<?php echo _('setup::Configuration de la base de donnee')?>
								</h2>
							</td>
						</tr>
						<tr>
							<td style="width:200px;"><label><?php echo _('phraseanet:: adresse')?></label></td>
							<td><input type="text" name="hostname" value="localhost" autocomplete="off" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('admin::compte-utilisateur identifiant database')?></label></td>
							<td><input type="text" name="user" value="" autocomplete="off" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('admin::compte-utilisateur mot de passe')?></label></td>
							<td><input type="password" name="password" value="" autocomplete="off" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('phraseanet:: port')?></label></td>
							<td><input type="text" name="port" value="3306" autocomplete="off" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('phraseanet:: base')?></label></td>
							<td><input type="text" name="abname" value="" autocomplete="off" /></td>
						</tr>
					</table>
				</form>
			</div>
			<div style="width:100%;text-align:right;position:relative;float:left;">
				<img src="/skins/icons/loader-black.gif" class="create_base_loader" style="display:none;vertical-align:middle;margin:0 10px;"/>
				<button class="create_base"><?php echo _('wizard:: next step');?></button>
			</div>
		</div>
		<?php 
		}
		?>
		<div class="steps">
			<h1><?php echo _('Installation de Phraseanet IV')?></h1>
			<div>
				<div><img src="/skins/icons/ok.png" style="vertical-align:middle;"/><?php echo _('Votre base de comptes utilisateurs a correctement ete creee'); ?></div>
				<h2><?php echo _('setup::param:: Choisissez une email et un mot de passe pour l\'utilisateur admin')?></h2>
				<form id="create_admin" method="post" onsubmit="return false;" action="index.php">
					<input type="hidden" name="user" value="admin" autocomplete="off" />
					
					<table>
						<tr>
							<td><label><?php echo _('admin::compte-utilisateur mot de passe')?></label></td>
							<td><input type="password" name="password" value="" autocomplete="off" /><span></span></td>
							<td>
								<div class="password-meter">
									<div class="password-meter-message">&nbsp;</div>
									<div class="password-meter-bg">
										<div class="password-meter-bar"></div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td><label><?php echo _('admin::compte-utilisateur confirmer le mot de passe')?></label></td>
							<td colspan="2"><input type="password" name="password_confirm" value="" autocomplete="off" /><span></span></td>
						</tr>
						<tr>
							<td><label><?php echo _('admin::compte-utilisateur email')?></label></td>
							<td colspan="2"><input type="text" name="email" value="" autocomplete="off" /><span></span></td>
						</tr>
						<tr>
							<td colspan="3">
								<h2>
									<?php echo _('setup::Repertoires de stockage')?>
								</h2>
							</td>
						</tr>
						<tr>
							<td><label><?php echo _('reglages:: dossier de stockage des vignettes publiees en acces direct')?></label></td>
							<td colspan="2"><input type="text" class="writable_check" style="width:450px;" name="pathweb" value="<?php echo $rootpath;?>datas/web" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('reglages:: dossier de stockage des fichiers proteges de l\'acces direct')?></label></td>
							<td colspan="2"><input type="text" class="writable_check" style="width:450px;" name="pathnoweb" value="<?php echo $rootpath;?>datas/noweb" /></td>
						</tr>
						<tr>
							<td><label><?php echo _('reglages:: point de montage des vignettes publiees en acces direct')?></label></td>
							<td colspan="2"><input type="text" class="url_check" name="baseurl" value="web/" /></td>
						</tr>
						<tr>
							<td colspan="3">
								<h2 style="margin-bottom:0;padding-bottom:0;"><?php echo _('Creation d\'une base de stockage d\'enregistrement'); ?></h2><br/>
								<i><?php echo _('Cette base est distincte de la base de comptes utilisateurs precedemment creee')?></i>
							</td>
						</tr>
						<tr>
							<td>
								<label><?php echo _('Creer une base de stockage des enregistrements')?></label>
							</td>
							<td>
								<input type="checkbox" class="databox_creator" checked/>
								<input class="databox_creator_dependant" type="text" name="databox" value="" autocomplete="off" />
							</td>
							<td>
							
								<label><?php echo _('phraseanet:: Modele de donnees');?></label>
								<select name="template">
									<?php 
									if ($handle = opendir($rootpath . 'lib/conf.d/data_templates'))
									{
										while (false !== ($file = readdir($handle))) {
											if(is_file($rootpath.'lib/conf.d/data_templates/'.$file))
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
								
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<table border="0" cellspacing="0" cellpadding="2" style="margin:10px 30px;">
									<td colspan="3">
										<?php echo _('Executables externes'); ?>
									</td>
									<tr>
										<?php 
										$php_cli = false;
										
										if($system != 'WINDOWS')
										{
											if(is_file('/usr/bin/php'))
												$php_cli = '/usr/bin/php';
											elseif(is_file('/usr/local/bin/php'))
												$php_cli = '/usr/local/bin/php';
											elseif(is_file('/opt/local/bin/php'))
												$php_cli = '/opt/local/bin/php';
										}
										if(!$php_cli || !is_executable($php_cli))
											$php_cli = false;
										?>
										<td><label for="exec_php"><?php echo _('reglages:: executable PHP CLI')?></label></td>
										<td><input id="exec_php" type="text" name="php_cli" value="<?php echo $php_cli;?>" class="executable_check databox_creator_dependant" /></td>
										<td></td>
									</tr>
									<tr>
										<?php 
										$exiftool = false;

										if($system != 'WINDOWS')
										{
											$exiftool = $rootpath."lib/exiftool/exiftool";
										}
										else
										{
											$exiftool = $rootpath."lib/exiftool/exiftool.exe";
										}
//										if(!$exiftool || !is_executable($exiftool))
//											$exiftool = false;
										?>
										<td><label for="exec_exiftool"><?php echo _('reglages:: chemin de l\'executable exiftool')?></label></td>
										<td colspan="2">
											<input id="exec_exiftool" type="text" name="exiftool" value="<?php echo $exiftool?>" class="executable_check databox_creator_dependant" />
										</td>
									</tr>
									<tr>
										<?php 
										$composite = false;

										if($system != 'WINDOWS')
										{
											if(file_exists('/usr/bin/composite') && is_executable('/usr/bin/composite'))
												$composite = '/usr/bin/composite';
											if(file_exists('/usr/local/bin/composite') && is_executable('/usr/local/bin/composite'))
												$composite = '/usr/bin/composite';
											if(file_exists('/opt/local/bin/composite') && is_executable('/opt/local/bin/composite'))
												$composite = '/opt/local/bin/composite';
										}
										?>
										<td><label for="exec_composite"><?php echo _('reglages:: chemin de l\'executable composite')?></label></td>
										<td colspan="2">
											<input id="exec_composite" type="text" name="composite" value="<?php echo $composite;?>" class="executable_check databox_creator_dependant" />
										</td>
									</tr>
									<tr>
										<?php 
										$convert = false;

										if($system != 'WINDOWS')
										{
											if(file_exists('/usr/bin/convert') && is_executable('/usr/bin/convert'))
												$convert = '/usr/bin/convert';
											if(file_exists('/usr/local/bin/convert') && is_executable('/usr/local/bin/convert'))
												$convert = '/usr/bin/convert';
											if(file_exists('/opt/local/bin/convert') && is_executable('/opt/local/bin/convert'))
												$convert = '/opt/local/bin/convert';
										}
										?>
										<td><label for="exec_convert"><?php echo _('reglages:: chemin de l\'executable convert')?></label></td>
										<td colspan="2">
											<input id="exec_convert" type="text" name="convert" value="<?php echo $convert?>" class="executable_check databox_creator_dependant" />
										</td>
									</tr>
									<td colspan="3">
										<?php echo _('Phraseanet embarque un moteur de taches pour la lecture / ecriture des metadonnes, et autre operations'); ?>
									</td>
									<tr>
										<td> --> <label for="create_task_read"><?php echo _('Creer la tache de lecture des metadonnees')?></label></td>
										<td><input id="create_task_read" type="checkbox" name="create_task[]" value="readmeta" checked class="databox_creator_dependant" /></td>
										<td></td>
									</tr>
									<tr>
										<td> --> <label for="create_task_write"><?php echo _('Creer la tache d\'ecriture des metadonnees')?></label></td>
										<td><input id="create_task_write" type="checkbox" name="create_task[]" value="writemeta" checked class="databox_creator_dependant" /></td>
										<td></td>
									</tr>
									<tr>
										<td> --> <label for="create_task_subdefs"><?php echo _('Creer la tache de creation des sous-definitions')?></label></td>
										<td><input id="create_task_subdefs" type="checkbox" name="create_task[]" value="subdefs" checked class="databox_creator_dependant" /></td>
										<td></td>
									</tr>
									<tr>
										<td> --> <label for="create_task_index"><?php echo _('Creer la tache d\'indexation')?></label></td>
										<td>
											<input id="create_task_index" type="checkbox" name="create_task[]" value="indexer" checked class="databox_creator_dependant" />
											<?php 
											$indexer = false;
											
											if($system == 'WINDOWS')
											{
												if(is_file($rootpath.'bin/phraseanet_indexer.exe'))
													$indexer = $rootpath.'bin/phraseanet_indexer.exe';
											}
											else
											{
												if(is_file($rootpath.'bin/phraseanet_indexer'))
													$indexer = $rootpath.'bin/phraseanet_indexer';
												elseif(is_file('/usr/bin/phraseanet_indexer'))
													$indexer = '/usr/bin/phraseanet_indexer';
												elseif(is_file('/usr/local/bin/phraseanet_indexer'))
													$indexer = '/usr/local/bin/phraseanet_indexer';
												elseif(is_file('/opt/local/bin/phraseanet_indexer'))
													$indexer = '/opt/local/bin/phraseanet_indexer';
											}
											if(!$indexer || !is_executable($indexer))
												$indexer = false;
											?>
											<label><?php echo _('Chemin de l\'indexeur');?></label>
											<input id="indexer_path" type="text" name="indexer" value="<?php echo $indexer?>" class="databox_creator_dependant executable_check"/>
										</td>
										<td></td>
									</tr>
								</table>
							
							</td>
						</tr>
					</table>
				</form>
			</div>
			<div style="width:100%;text-align:right;">
				<img src="/skins/icons/loader-black.gif" class="create_admin_loader" style="display:none;vertical-align:middle;margin:0 10px;"/>
				<button class="create_admin"><?php echo _('wizard:: next step');?></button>
			</div>
		</div>
		<div class="steps">
			<div>
				<?php echo _('setup::param:: La base de donnee et l\'utilisateur admin ont correctement ete crees')?>
				<?php echo _('setup::param:: Vous allez etre rediriger vers la zone d\'administartion pour finaliser l\'installation et creer une base de stockage')?>
			</div>
			<div>
				<button class="finish_it"><?php echo _('wizard:: terminer');?></button>
			</div>
		</div>
	</body>

</html>
