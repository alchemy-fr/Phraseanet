<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

require_once(GV_RootPath.'lib/inscript.api.php');
if(GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '')
	include(GV_RootPath.'lib/recaptcha/recaptchalib.php');

skins::merge();

$request = httpRequest::getInstance();
$parm = $request->get_parms('lng', 'error', 'confirm','badlog','postlog', 'app', 'usr', 'logged_out');
	
if($parm['postlog'])
{
	$session->postlog = true;
	
	header("Location: /login/index.php?app=".$parm['app']);
	exit();
}
	
if(!isset($session->postlog) && isset($session->usr_id) && isset($session->ses_id) && $parm['error']!='no-connection')
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;

	header("Location: /".$parm['app']."/");
	exit();
}

$conn = connection::getInstance();

if(!$conn)
{
	$parm['error'] = 'no-connection';
}

phrasea::headers();
			
				
$client = browser::getInstance();

$errorWarning = $confirmWarning = '';

if(GV_maintenance)
	$parm['error'] = 'maintenance';

if($parm['error'] !== null)
{
	switch($parm['error'])
	{
		case 'auth':
			$errorWarning = '<div class="notice">'._('login::erreur: Erreur d\'authentification').'</div>';
		break;
		case 'captcha':
			$errorWarning = '<div class="notice">'._('login::erreur: Erreur de captcha').'</div>';
		break;
		case 'mailNotConfirm' :
			$errorWarning = '<div class="notice">'._('login::erreur: Vous n\'avez pas confirme votre email').'</div>';
			if(is_numeric((int)$parm['usr']))
				$errorWarning .= '<div class="notice"><a href="/login/sendmail-confirm.php?usr_id='.$parm['usr'].'" target ="_self" style="color:black;text-decoration:none;">'._('login:: Envoyer a nouveau le mail de confirmation').'</a></div>';
		break;
		case 'no-base' :
			$errorWarning = '<div class="notice">'._('login::erreur: Aucune base n\'est actuellment accessible').'</div>';
		break;
		case 'no-connection':
			$errorWarning = '<div class="notice">'._('login::erreur: No avalaible connection - Please contact sys-admin').'</div>';
		break;
		case 'maintenance':
			$errorWarning = '<div class="notice">'._('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee').'</div>';
		break;
	}
}
if($parm['confirm'] !== null)
{
	switch($parm['confirm'])
	{
		case 'ok':
			$confirmWarning = '<div class="notice">'._('login::register: sujet email : confirmation de votre adresse email').'</div>';
		break;
		case 'already':
			$confirmWarning = '<div class="notice">'._('login::notification: cette email est deja confirmee').'</div>';
		break;
		case 'mail-sent':
			$confirmWarning = '<div class="notice">'._('login::notification: demande de confirmation par mail envoyee').'</div>';
		break;
		case 'register-ok':
			$confirmWarning = '<div class="notice">'._('login::notification: votre email est desormais confirme').'</div>';
		break;
		case 'register-ok-wait':
			$confirmWarning = '<div class="notice">'._('login::notification: votre email est desormais confirme').'</div>';
			$confirmWarning .= '<div class="notice">'._('login::register : vous serez avertis par email lorsque vos demandes seront traitees').'</div>';
		break;
		case 'password-update-ok':
			$confirmWarning = '<div class="notice">'._('login::notification: Mise a jour du mot de passe avec succes').'</div>';
			break;
	}
}
$captchaSys = '';
		if(!GV_maintenance && GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '' && $parm['error'] == 'captcha')
		{
				$captchaSys = '<div style="margin:0;float: left;width:330px;"><div id="recaptcha_image" style="float: left;margin:10px 15px 5px"></div>
														<div style="text-align:center;float: left;margin:0 15px 5px;width:300px;">
														<a href="javascript:Recaptcha.reload()" class="link">'._('login::captcha: obtenir une autre captcha').'</a>
														</div>
														<div style="text-align:center;float: left;width:300px;margin:0 15px 0px;">
															<span class="recaptcha_only_if_image">'._('login::captcha: recopier les mots ci dessous').' : </span>
															<input name="recaptcha_response_field" id="recaptcha_response_field" value="" type="text" style="width:180px;"/>
														</div>'.recaptcha_get_html(GV_captcha_public_key).'</div>';
		}

$twig = new supertwig();
	
$twig->display('login/index.twig', array(
				'module_name'		=> _('Accueil'),
				'confirmWarning'	=> $confirmWarning,
				'errorWarning'		=> $errorWarning,
				'module'			=> $parm['app'],
				'logged_out'			=> $parm['logged_out'],
				'captcha_system'	=> $captchaSys,
				'login'				=> new login(),
				'sso'				=> new sso(),
				'display_layout'	=> GV_home_publi
					)
			);


?>
