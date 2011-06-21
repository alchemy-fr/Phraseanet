<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require(GV_RootPath.'lib/geonames.php');
require_once(GV_RootPath.'lib/inscript.api.php');

$request = httpRequest::getInstance();
$parm = $request->get_parms("form_gender","form_lastname","form_firstname","form_job", "form_company" 
					, "form_function", "form_activity","form_phone","form_fax","form_address","form_zip","form_geonameid"
					,"form_destFTP","form_defaultdataFTP","form_prefixFTPfolder","notice", "form_bases" , "mail_notifications", 
					"request_notifications", 'demand', 'notifications'
					,"form_activeFTP","form_addrFTP","form_loginFTP","form_pwdFTP","form_passifFTP","form_retryFTP");

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}

if(!isset($session->account_editor) || !$session->account_editor)
{
	phrasea::headers(403);
}
phrasea::headers();

$usrCoord = null ;

$conn = connection::getInstance();
if(!$conn)
{
	die();
}

$lastMonth = time() - (4 * 7 * 24 * 60 * 60);

$sql = "delete from demand where date_modif <'".date('Y-m-d', $lastMonth)."'";
$conn->query($sql);

if($request->has_post_datas())
{
	$accountFields = array(
		'form_gender',
		'form_firstname',
		'form_lastname',
		'form_address',
		'form_zip',
		'form_phone',
		'form_fax',
		'form_function',
		'form_company',
		'form_activity',
		'form_geonameid',
		'form_addrFTP',
		'form_loginFTP',
		'form_pwdFTP',
		'form_destFTP',
		'form_prefixFTPfolder'
	);
	
	$demandFields = array(
		'demand'
	);
	
	$parm['notice'] = 'account-update-bad';
	
	if(count(array_diff($demandFields,array_keys($request->get_post_datas())))==0)
	{
		
		foreach ($parm["demand"] as $unebase)
		{
			$sql = "INSERT INTO demand (date_modif, usr_id, base_id, en_cours, refuser) VALUES (now(), '".$conn->escape_string($usr_id)."' , '".$conn->escape_string($unebase)."', 1, 0)";
			if($conn->query($sql))
				$parm['notice'] = 'demand-ok';
		}
		
	}
	if(count(array_diff($accountFields,array_keys($request->get_post_datas())))==0)
	{

		$defaultDatas = 0;
		if($parm["form_defaultdataFTP"])
		{
			if(in_array('document',$parm["form_defaultdataFTP"]))
				$defaultDatas += 4;
			if(in_array('preview',$parm["form_defaultdataFTP"]))
				$defaultDatas += 2;
			if(in_array('caption',$parm["form_defaultdataFTP"]))
				$defaultDatas += 1;
		}
		
		$sql = "UPDATE usr SET
			usr_sexe	= ".$conn->escape_string($parm["form_gender"]).", 
			usr_prenom	='".$conn->escape_string($parm["form_firstname"])."', 
			usr_nom		='".$conn->escape_string($parm["form_lastname"])."', 
			adresse		='".$conn->escape_string($parm["form_address"])."', 
			cpostal		='".$conn->escape_string($parm["form_zip"])."', 
			tel			='".$conn->escape_string($parm["form_phone"])."', 
			fax			='".$conn->escape_string($parm["form_fax"])."',
			fonction	='".$conn->escape_string($parm["form_function"])."', 
			societe		='".$conn->escape_string($parm["form_company"])."',
			mail_notifications		='".$conn->escape_string($parm["mail_notifications"]=='1' ? '1' : '0')."',
			request_notifications	='".$conn->escape_string($parm["request_notifications"]=='1' ? '1' : '0')."',
			activite	='".$conn->escape_string($parm["form_activity"])."', 
			geonameid		='".$conn->escape_string($parm["form_geonameid"])."',
			pays		='".$conn->escape_string(geonames::get_country_code($parm["form_geonameid"]))."',
			
			activeFTP	='".($parm["form_activeFTP"]?"1":"0")."',
			addrFTP		='".$conn->escape_string($parm["form_addrFTP"])."',
			loginFTP	='".$conn->escape_string($parm["form_loginFTP"])."',
			pwdFTP		='".$conn->escape_string($parm["form_pwdFTP"])."',
			passifFTP	='".($parm["form_passifFTP"]?"1":"0")."',
			retryFTP	='5',
			destFTP		='".$conn->escape_string($parm["form_destFTP"])."',
			prefixFTPfolder		='".$conn->escape_string($parm["form_prefixFTPfolder"])."',
			defaultftpdatasent='".$conn->escape_string($defaultDatas)."',
			
			usr_modificationdate=now()
			WHERE usr_id=".$usr_id."";		
		
		if($conn->query($sql))
			$parm['notice'] = 'account-update-ok';
		
	}
}
if($request->has_post_datas())
{
	$evt_mngr = eventsmanager::getInstance();
	$notifications = $evt_mngr->list_notifications_avalaible($session->usr_id);
	
	$datas = array();
	
	foreach($notifications as $notification=>$nots)
	{
		
		foreach($nots as $notification)
		{
			$current_notif = user::getPrefs('notification_'.$notification['id']);
			
			if(!is_null($parm['notifications']) && isset($parm['notifications'][$notification['id']]))
				$datas[$notification['id']] = '1';
			else
				$datas[$notification['id']] = '0';
		}
	}
	
	foreach($datas as $k=>$v)
	{
		user::setPrefs('notification_'.$k,$v);
	}

}
$user = user::getInstance($session->usr_id);

$sql = "SELECT *,bin(defaultftpdatasent) as bindefaultftpdatasent FROM usr WHERE usr_id='".$conn->escape_string($usr_id)."'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
		$usrCoord = $row;
	$conn->free_result($rs);
}

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<title><?php echo GV_homeTitle?> <?php echo _('login:: Mon compte')?></title>
	<link REL="stylesheet" TYPE="text/css" HREF="/include/minify/f=login/home.css,login/geonames.css" />
	<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
	<script type="text/javascript" src="/login/geonames.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){

			initialize_geoname_field($('#form_geonameid'));
		});
	</script>
	<style type="text/css">
		.tab-content{
			height:auto;
		}
	</style>
	</head>
		<body>
			<div style="width:950px;margin-left:auto;margin-right:auto;">
				<div style="margin-top:70px;height:35px;">
					<table style="width:100%;">
						<tr style="height:35px;">
							<td style="width:580px;"><span class="title-name"><?php echo GV_homeTitle?></span><span class="title-desc"><?php echo _('login:: Mon compte')?></span></td>
							<td style="color:#b1b1b1;text-align:right;">

							</td>
						</tr>
					</table>
				</div>
				<div class="tab-pane">
					<div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
						<table>
							<tr valign="top">
								<td style="width:49%:">
									
									<?php
									$notice = '';
									if(!is_null($parm['notice']))
									{
										switch($parm['notice'])
										{
											case 'password-update-ok':
												$notice = _('login::notification: Mise a jour du mot de passe avec succes');
												break;
											case 'account-update-ok':
												$notice = _('login::notification: Changements enregistres');
												break;
											case 'account-update-bad':
												$notice = _('forms::erreurs lors de l\'enregistrement des modifications');
												break;
											case 'demand-ok':
												$notice = _('login::notification: Vos demandes ont ete prises en compte');
												break;
										}
									}
									if($notice != '')
									{
										?>
										<div class="notice"><?php echo $notice?></div>
										<?php
									}
									?>
									<form name="account" id="account" action="/login/account.php" method="post">
									<table style="margin:20px auto;">NAME
										<tr>
											<td></td>
											<td><a href="/login/reset-password.php" class="link" target="_self"><?php echo _('admin::compte-utilisateur changer mon mot de passe');?></a></td>
											<td></td>
										</tr>
										<tr>
											<td colspan="3"></td>
										</tr>
										<tr>
											<td class="form_label"><label for="form_login"><?php echo _('admin::compte-utilisateur identifiant');?></label></td>
											<td class="form_input"><?php echo $session->login?></td>
											<td class="form_alert"></td>
										</tr>
										<tr>
											<td class="form_label"><label for="form_gender"><?php echo _('admin::compte-utilisateur sexe')?></label></td>
											<td class="form_input">
												<select class="input_element" name="form_gender" id="form_gender"  >
						   							 <option <?php echo ($usrCoord["usr_sexe"]=="0"?"selected":"")?> value="0" ><?php echo _('admin::compte-utilisateur:sexe: mademoiselle'); ?></option>
						   							 <option <?php echo ($usrCoord["usr_sexe"]=="1"?"selected":"")?> value="1" ><?php echo _('admin::compte-utilisateur:sexe: madame'); ?></option>
						   							 <option <?php echo ($usrCoord["usr_sexe"]=="2"?"selected":"")?> value="2" ><?php echo _('admin::compte-utilisateur:sexe: monsieur'); ?></option>
						   						</select>	 
											</td>
											<td class="form_alert"></td>
										</tr>
										<tr>
											<td class="form_label"><label for="form_lastname"><?php echo _('admin::compte-utilisateur nom');?></label></td>
											<td class="form_input">
												<input class="input_element" type="text" name="form_lastname" id="form_lastname" value="<?php echo $usrCoord["usr_nom"]?>" >
											</td>
											<td class="form_alert"></td>
										</tr>
										<tr>
											<td class="form_label"><label for="form_firstname"><?php echo _('admin::compte-utilisateur prenom');?></label></td>
											<td class="form_input">
												<input  class="input_element"  type="text" name="form_firstname" id="form_firstname" value="<?php echo $usrCoord["usr_prenom"]?>" >
											</td>
											<td class="form_alert"></td>
										</tr>
										<tr>
											<td colspan="3">
										</tr>
									 	<tr>
											<td class="form_label"><label for=""><?php echo _('admin::compte-utilisateur email')?></label></td>
											<td class="form_input" colspan="2">
												<?php echo $usrCoord["usr_mail"]?> <a class="link" href="/login/reset-email.php" target="_self"><?php echo _('login:: Changer mon adresse email')?></a>
											</td>
										</tr>
									 	<!-- <tr>
											<td class="form_label"><label for="mail_notifications"><?php echo _('login:: Recevoir des notifications par email')?></label></td>
											<td class="form_input" colspan="2">
												<input type="checkbox" id="mail_notifications" name="mail_notifications" <?php echo $usrCoord["mail_notifications"]== '1' ? 'checked' : '' ; ?> value="1"/>
											</td>
										</tr>
										<?php 
										if($user->_global_rights['modifyrecord'] === true)
										{
										?>
									 	<tr>
											<td class="form_label"><label for="request_notifications"><?php echo _('login:: Recevoir les demandes de recherche des utilisateurs')?></label></td>
											<td class="form_input" colspan="2">
												<input type="checkbox" id="request_notifications" name="request_notifications" <?php echo $usrCoord["request_notifications"]== '1' ? 'checked' : '' ; ?> value="1"/>
											</td>
										</tr>
										<?php 
										}
										else
										{
											?>
											<tr>
												<td>
													<input type="hidden" id="request_notifications" name="request_notifications" value="1"/>
												</td>
											</tr>
											<?php 
										}
										?>-->
										<tr>
											<td colspan="3"></td>
										</tr>
										<tr>
											<td colspan="3">Notification par email</td>
										</tr>
										<?php 
										$evt_mngr = eventsmanager::getInstance();
										$notifications = $evt_mngr->list_notifications_avalaible($session->usr_id);

										foreach($notifications as $notification_group=>$nots)
										{
											?>
										 	<tr>
												<td style="font-weight:bold;" colspan="3"><?php echo $notification_group;?></td>
											</tr>
											<?php 
											foreach($nots as $notification)
											{
										?>
									 	<tr>
											<td class="form_label" colspan="2"><label for="notif_<?php echo $notification['id']?>"><?php echo $notification['description']?></label></td>
											<td class="form_input">
												<input type="checkbox" id="notif_<?php echo $notification['id']?>" name="notifications[<?php echo $notification['id']?>]" <?php echo user::getPrefs('notification_'.$notification['id']) == '0' ? '' : 'checked'; ?> value="1"/>
											</td>
										</tr>
										<?php
											} 
										}
										?>
										<tr>
											<td colspan="3"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_address"><?php echo _('admin::compte-utilisateur adresse')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_address" id="form_address" value="<?php echo $usrCoord["adresse"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_zip"><?php echo _('admin::compte-utilisateur code postal')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_zip", id="form_zip" value="<?php echo $usrCoord["cpostal"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_city"><?php echo _('admin::compte-utilisateur ville')?></label></td>
											<td class="form_input">
												<input id="form_geonameid" type="text" geonameid="<?php echo $usrCoord["geonameid"]?>" value="<?php echo geonames::name_from_id($usrCoord["geonameid"])?>" class="input_element geoname_field" name="form_geonameid">
											</td>
											<td class="form_alert"></td>
										</tr>
											<tr>
												<td class="form_label"></td>
												<td class="form_input"><div id="test_city" style="position:absolute;width:200px;max-height:200px;overflow-y:auto;z-index:99999;"></div></td>
												<td class="form_alert"></td>
											</tr>
										<tr>
											<td colspan="3">
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_function"><?php echo _('admin::compte-utilisateur poste')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_function" id="form_function" value="<?php echo $usrCoord["fonction"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_company"><?php echo _('admin::compte-utilisateur societe')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_company" id="form_company" value="<?php echo $usrCoord["societe"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_activity"><?php echo _('admin::compte-utilisateur activite')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_activity" id="form_activity" value="<?php echo $usrCoord["activite"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_phone"><?php echo _('admin::compte-utilisateur telephone')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_phone" id="form_phone" value="<?php echo $usrCoord["tel"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
									 	<tr>
											<td class="form_label"><label for="form_fax"><?php echo _('admin::compte-utilisateur fax')?></label></td>
											<td class="form_input">
												<input  class="input_element" type="text" name="form_fax" id="form_fax" value="<?php echo $usrCoord["fax"]?>"/>
											</td>
											<td class="form_alert"></td>
										</tr>
										<tr>
											<td colspan="3">
										</tr>
										
										<?php
										if($usrCoord['canchgftpprofil']=='1' )
										{
										?>
											
										 	<tr>
												<td class="form_label"><label for="form_activeFTP"><?php echo _('admin::compte-utilisateur:ftp: Activer le compte FTP'); ?></label></td>
												<td class="form_input">
													<input onchange="if(this.checked){$('#ftpinfos').slideDown();}else{$('#ftpinfos').slideUp();}" style=""  type="checkbox" class="checkbox" <?php echo ($usrCoord["activeFTP"]=="1"?"checked":"")?> name="form_activeFTP" id="form_activeFTP">
												</td>
												<td class="form_alert"></td>
											</tr>
											<tr>
												<td colspan="3">
													<div id="ftpinfos" style="display:<?php echo ($usrCoord["activeFTP"]=="1"?"block":"none")?>;">
														<table>
													 	<tr>
															<td class="form_label"><label for="form_addrFTP"><?php echo _('phraseanet:: adresse') ?></label></td>
															<td class="form_input">
																<input  class="input_element" type="text" name="form_addrFTP" id="form_addrFTP" value="<?php echo $usrCoord["addrFTP"]?>"/>
															</td>
															<td class="form_alert"></td>
														</tr>
													 	<tr>
															<td class="form_label"><label for="form_loginFTP"><?php echo _('admin::compte-utilisateur identifiant') ?></label></td>
															<td class="form_input">
																<input  class="input_element" type="text" name="form_loginFTP" id="form_loginFTP" value="<?php echo $usrCoord["loginFTP"]?>"/>
															</td>
															<td class="form_alert"></td>
														</tr>
														
													 	<tr>
															<td class="form_label"><label for="form_pwdFTP"><?php echo _('admin::compte-utilisateur mot de passe') ?></label></td>
															<td class="form_input">
																<input class="input_element" type="password" name="form_pwdFTP" id="form_pwdFTP" value="<?php echo $usrCoord["pwdFTP"]?>"/>
															</td>
														<td class="form_alert"></td>
														</tr>
														
													 	<tr>
															<td class="form_label"><label for="form_destFTP"><?php echo _('admin::compte-utilisateur:ftp:  repertoire de destination ftp') ?></label></td>
															<td class="form_input">
																<input class="input_element" type="text" name="form_destFTP" id="form_destFTP" value="<?php echo $usrCoord["destFTP"]?>"/>
															</td>
															<td class="form_alert"></td>
														</tr>
													 	<tr>
															<td class="form_label"><label for="form_prefixFTPfolder"><?php echo _('admin::compte-utilisateur:ftp: prefixe des noms de dossier ftp') ?></label></td>
															<td class="form_input">
																<input class="input_element" type="text" name="form_prefixFTPfolder" id="form_prefixFTPfolder" value="<?php echo $usrCoord["prefixFTPfolder"]?>"/>
															</td>
														<td class="form_alert"></td>
														</tr>
													 	<tr>
															<td class="form_label"><label for="form_passifFTP"><?php echo _('admin::compte-utilisateur:ftp: Utiliser le mode passif') ?></label></td>
															<td class="form_input">
																<input type="checkbox" <?php echo ($usrCoord["passifFTP"]=="1"?"checked":"")?> name="form_passifFTP" id="form_passifFTP"/>
															</td>
														<td class="form_alert"></td>
														</tr>
													 	<tr style="display:none;">
															<td class="form_label"><label for="form_retryFTP"><?php echo _('admin::compte-utilisateur:ftp: Nombre d\'essais max') ?></label></td>
															<td class="form_input">
																<input class="input_element" type="text" name="form_retryFTP" id="form_retryFTP" value="5"/>
															</td>
															<td class="form_alert"></td>
														</tr>
														<tr style="display:none;">
															<td class="form_label"><label for="form_defaultdataFTP"><?php echo _('admin::compte-utilisateur:ftp: Donnees envoyees automatiquement par ftp')?></label></td>
															<td class="form_input">
																<input class="checkbox" type="checkbox" <?php echo ((($usrCoord["defaultftpdatasent"]>>2) & 1)==1?"checked":"")?> name="form_defaultdataFTP[]" value="document" id="form_defaultSendDocument"><label for="form_defaultSendDocument"><?php echo _('phraseanet:: original'); ?></label>
																<input class="checkbox" type="checkbox" <?php echo ((($usrCoord["defaultftpdatasent"]>>1) & 1)==1?"checked":"")?> name="form_defaultdataFTP[]" value="preview" id="form_defaultSendPreview"><label for="form_defaultSendPreview"><?php echo _('phraseanet:: preview'); ?></label>
																<input class="checkbox" type="checkbox" <?php echo (($usrCoord["defaultftpdatasent"]&1) ==1?"checked":"")?> name="form_defaultdataFTP[]" value="caption" id="form_defaultSendCaption"><label for="form_defaultSendCaption"><?php echo _('phraseanet:: imagette'); ?></label>
															</td>
															<td class="form_alert"></td>
														</tr>
														</table>
													</div>
												</td>
											</tr>
										<?php
										}
										?>					
									</table>
					<div style="text-align:center;margin:5px 0;">
						<input type="submit" value="<?php echo _('boutton::valider');?>">
					</div>
								</form>
							</td>
							<td style="width:49%:">
								<form name="updatingDemand" id="updatingDemand" action="/login/account.php" method="post">
									<?php
									$demandes = giveMeBaseUsr($usr_id,$lng);
									echo $demandes['tab'];
									?>
									<input type="submit" value="<?php echo _('boutton::valider');?>"/>
								</form>
							</td>
						</tr>
					</table>
				</div>
				<div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y')?></span></div>
			</div>
		</div>					
	</body>
</html>

